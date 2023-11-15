<?php

# PHP-RKSV
# Example class used by the test script and the test database
# If you create your own application you have to adjust this class to your specific environment
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

include_once '../RKSV/php-rksv-atrust.php';
include_once 'mysqli.php';
include_once 'phpqrcode.php';

class Kasse extends \rksvaustria\RKSVATrust
{
  protected $AESkey = 'Tyztuo8QJ56aS35yiRjw8Kt4cwEiPdw33IvqCgevBZM=';
  protected $testmode = true;
  protected $DB;

  public function setDB($db) { $this->DB = $db; }

  public function create_start_receipt($data = null)
  {
    // sign start receipt
    $this->init_cashBox($data);
    return true;
  }

  // get the turnover counter (Umsatzzähler) from all receipts created until now
  protected function get_sum()
  {
    $sql = "select sum(l.amount*l.pieceprice*(1+l.tax/100)) from invoiceline l, invoice i, rksvreceipt r 
            where l.invoice=i.id and i.id=r.invoice and i.type<>'TR'";    // i.type='TR' means trainings receipt (Trainingsbuchung) in this test example
    return $this->DB->query_value($sql);
  }

  // get the data from a receipt with a given rnr (laufende Nummer) in the format that the RKSVAustria class needs it
  protected function get_data($rnr)
  {
    $invoice = $this->DB->query_list("select i.*, r.rdate from invoice i, rksvreceipt r where r.invoice=i.id and r.rnr='$rnr'");
    if($invoice === null || sizeof($invoice) == 0) return ['rnr' => $rnr];

    $amounts = $this->DB->query_index_array("select tax, sum(amount*pieceprice*(1+tax/100)) as brutto from invoiceline
                                             where invoice='{$invoice['id']}' group by tax");

    $result = ['IID' => $invoice['id'], 'amount_normal' => floatval($amounts[20]), 'amount_reduced1' => floatval($amounts[10]),
               'amount_reduced2' => floatval($amounts[7]), 'amount_null' => floatval($amounts[0]), 'amount_special' => floatval($amounts[13]),
               'invoicedate' => date('Y-m-d\TH:i:s', strtotime($invoice['created'])), 'receiptdate' => date('Y-m-d\TH:i:s', strtotime($invoice['rdate'])),
               'rnr' => $rnr];
    return $result;
  }

  protected function get_next_rnr()
  {
    return $this->DB->query_value("select max(rnr) + 1 from rksvreceipt");
  }

  // save signature and other data to our test database after signature is received
  protected function after_sign($data, $signature, $certSerial = null)
  {
    $id = $data['IID'];
    if($id == '') $id = 'null';

    $rdate = $data['receiptdate'] ?? date('Y-m-d H:i:s');
    $rnr = $data['rnr'] ?? $this->rnr;
    $certSerial = $certSerial ?? $data['certSerial'] ?? $this->certSerial ?? $this->get_certSerial($rnr);
    $this->DB->query("insert into rksvreceipt (invoice, rdate, signature, certSerial, rnr) values ($id, '$rdate', '$signature', '$certSerial', '$rnr')");
    #print "insert into rksvreceipt (invoice, rdate, signature, certSerial, rnr) values ($id, '$rdate', '$signature', '$certSerial', '$rnr')";
  }

  protected function generate_QRcode($code, $data = null)
  {
    $rnr = $data['rnr'] ?? $this->rnr;

    if(is_array($data) && array_key_exists('IID', $data)) $file = "tmp/qrcode_invoice_{$data['IID']}.png";
    elseif($rnr) $file = "tmp/qrcode_nullreceipt_$rnr.png";
    else $file = 'tmp/qrcode.png';

    // Generate the QR code
    QRcode::png($code, $file);
    return $file;
  }

  protected function get_existing_signature($rnr)
  {
    $result = $this->DB->query_value("select signature from rksvreceipt where rnr='$rnr'");
    if($result == '') return null;
    return $result;
  }

  protected function get_existing_certSerial($rnr)
  {
    $result = $this->DB->query_value("select certSerial from rksvreceipt where rnr='$rnr'");
    if($result == '') return null;
    return $result;
  }

  protected function get_existing_chainValue($rnr)
  {
    $result = $this->DB->query_value("select chainValue from rksvreceipt where rnr='$rnr'");
    if($result == '') return null;
    return $result;
  }

  protected function update_chainValue($rnr, $value)
  {
    $this->DB->query("update rksvreceipt set chainValue='$value' where rnr='$rnr'");
  }

  protected function handle_error($msg = null)
  {
    if($msg === null) $msg = $this->error;

    print $msg;
    exit;
  }

  protected function store_in_DEP($value, $data = null)
  {
    $rnr = $data['rnr'] ?? $this->rnr;
    $clause = $data['IID'] ? "invoice='{$data['IID']}'" : "rnr='$rnr'";

    $iid = $data['IID'];
    $this->DB->query("update rksvreceipt set dep='$value' where $clause");

    return true;
  }

  protected function get_data_from_DEP($rnr)
  {
    $rnr = intval($rnr);
    $result = $this->DB->query_value("select dep from rksvreceipt where rnr='$rnr'");

    if($result == '') return null;
    return $result;
  }
}
?>
