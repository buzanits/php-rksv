<?php

# PHP-RKSV
# Example script that reads invoices from a mysql database and signs them
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

include_once 'kasse.php';

$DB = new Mysql_access('localhost', 'phprksv', 'MnJzDBuuo7j7dEXH', 'phprksv');

$kasse = new Kasse('u123456789', '123456789', '1');
$kasse->setDB($DB);

// Startbeleg mit Datum 1. 10. 2017 anlegen
if($DB->query_value("select count(*) from rksvreceipt") == 0) {
  $result = $kasse->create_start_receipt(['receiptdate' => '2017-10-01']);
  if($result !== true) {
    print "ERROR: $result";
    exit;
  }
}


$invoices = $DB->query_arrays("select * from invoice where id not in (select invoice from rksvreceipt where invoice is not null)");
foreach($invoices as $invoice) {
  print "\n\n********* signing invoice {$invoice['id']} ********************\n";

  $amounts = $DB->query_index_array("select tax, sum(amount*pieceprice*(1+tax/100)) as brutto from invoiceline
                                           where invoice='{$invoice['id']}' group by tax");

  $data = ['IID' => $invoice['id'], 'amount_normal' => floatval($amounts[20]), 'amount_reduced1' => floatval($amounts[10]),
           'amount_reduced2' => floatval($amounts[7]), 'amount_null' => floatval($amounts[0]), 'amount_special' => floatval($amounts[13]),
           'receiptdate' => date('Y-m-d\TH:i:s', strtotime($invoice['invoicedate']))];

  // Beim Monatswechsel einen Nullbeleg erstellen
  if($last_month && date('Ym', strtotime($invoice['invoicedate'])) != $last_month) 
    $kasse->process_nullreceipt(['receiptdate' => date('Y-m-01', strtotime($invoice['invoicedate']))]);
  $last_month = date('Ym', strtotime($invoice['invoicedate']));

  if($invoice['type'] == 'ER') {
    $kasse->set_failure(true);    // Signatureinheit ausgefallen
    $kassenfehler = true;         // merken!
  } else {
    if($kassenfehler) $kasse->process_nullreceipt(['receiptdate' => $invoice['invoicedate']]);   // nach Ausfall ein Nullbeleg!
    $kassenfehler = false;
    $kasse->set_failure(false);
  }

  if($invoice['type'] == 'TR') $data['training'] = true;   // Trainingsbeleg
  elseif($invoice['type'] == 'ST') $data['storno'] = true;   // Stornobeleg

  $kasse->process_receipt($data);
}

print 'Fertig!' . PHP_EOL;
?>
