<?php

# PHP-RKSV
# Basic class that provides functions for RKSV without a designated signature device
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

namespace rksvaustria;

class RKSVAustria
{
  protected $cashBoxID, $AESkey, $certSerial;
  protected $prefix = '_R1-AT1';
  protected $failure = false;
  protected $testmode = false;
  protected $error = '';

  public function __construct($cashBoxID = null, $AESkey = null, $certSerial = null)
  {
    $this->cashBoxID = $cashBoxID ?? $this->get_cashBoxID() ?? $this->cashBoxID;
    $this->AESkey = $AESkey ?? $this->get_AESkey() ?? $this->AESkey;
    $this->certSerial = $certSerial ?? $this->get_certSerial() ?? $this->certSerial;

    if($this->certSerial === null) $this->handle_error();
  }

  public function set_cashBoxID($value) { $this->cashBoxID = $value; }
  public function set_AESkey($value) { $this->AESkey = $value; }
  public function set_sum($value) { $this->sum = $value; }
  public function set_certSerial($value) { $this->certSerial = $value; }
  public function set_prefix($value) { $this->prefix = $value; }

  protected function get_cashBoxID() { return null; }
  protected function get_AESkey() { return null; }
  protected function get_sum() { return null; }  // to be overridden
  protected function get_existing_certSerial($rnr) { return null; }  // to be overridden
  protected function get_certSerial_from_signer() { return null; }  // to be overridden

  protected function get_certificate_from_signer() { return null; }  // to be overridden
  protected function get_CAs_from_signer() { return null; }  // to be overridden

  protected function get_data($rnr) { return null; }  // to be overridden, return false when data not found
  protected function get_next_rnr() { return 0; }  // to be overridden
  protected function get_existing_signature($rnr) { return null; }   // to be overridden
  protected function sign($data) { return 'SIGNATURE'; }  // to be overridden
  
  protected function get_existing_chainValue($rnr) { return null; }   // to be overridden
  protected function update_chainValue($rnr, $value) {}   // to be overridden
  protected function check_signer() { return true; }   // to be overridden
  protected function update_turnoverCounter($value) {}   // to be overridden
  protected function after_sign($data, $signature, $certSerial = null) {}   // to be overridden
  protected function generate_QRcode($code, $data = null) { return null; }   // to be overridden

  protected function init_DEP() {}   // to be overridden
  protected function store_in_DEP($value, $data = null) {}   // to be overridden
  protected function get_data_from_DEP($rnr) { return null; }   // to be overridden

  protected function handle_error($msg = null)
  {
    if($msg === null) $msg = $this->error;
    print "ERROR: $msg" . PHP_EOL;
  }

  // initialize cashBox, create start receipt
  public function init_cashBox($data = [])
  {
    $this->init_DEP();

    // create start receipt
    $data['rnr'] = 1;
    return $this->process_receipt($data);
  }

  // process a receipt
  // sign it and save relevant informations, create QR-Code
  public function process_receipt($data)
  {
    $this->rnr = null;  // do not remember rnr from last receipt!

    if($this->failure && $this->testmode == false)
      if($this->check_signer()) {  // signer is working again!
        $this->failure = false;
        $this->process_nullreceipt();
      }

    $this->update_DEP($data);
    return $this->get_QRcode($data);
  }

  public function process_nullreceipt($data = [])
  {
    return $this->process_receipt($data);
  }

  public function export_DEP($min_rnr = 1, $max_rnr = null)
  {
    $receipts = [];
    if($max_rnr === null) $max_rnr = $this->get_next_rnr() - 1;

    for($rnr = $min_rnr; $rnr <= $max_rnr; $rnr++) {
      $data = $this->get_data_from_DEP($rnr);
      if($data === null) $data = $this->get_compact_signed_data($rnr);   // get_data_from_DEP is not implemented, generate the data
      $receipts[] = $data;
    }

    $result = ['Belege-Gruppe' => [['Signaturzertifikat' => $this->get_certificate_from_signer(), 'Zertifizierungsstellen' => $this->get_CAs_from_signer(),
               'Belege-kompakt' => $receipts]]];

    return json_encode($result);
  }

  public function save_DEP($file = 'DEP.json', $min_rnr = 1, $max_rnr = null)
  {
    return file_put_contents($file, $this->export_DEP($min_rnr, $max_rnr));
  }

  public function get_AESCheckSum()
  {
    $hash = substr(hash('sha256', $this->AESkey, true), 0, 3);
    return rtrim(base64_encode($hash), '=');
  }

  public function generate_AESkey()
  {
    return base64_encode(openssl_random_pseudo_bytes(32));
  }

  public function set_failure($flag) { $this->failure = $flag; }
  public function get_failure() { return $this->failure; }

  protected function get_certSerial($rnr = null)
  {
    $certSerial = null;
    if($rnr !== null) $certSerial = $this->get_existing_certSerial($rnr);

    if($certSerial === null) {
      if($this->failure) return null;
      $certSerial = $this->get_certSerial_from_signer();
    }

    return $certSerial;
  }

  protected function get_signature($data, $url = false)
  {
    $signature = $this->get_existing_signature($data['rnr'] ?? $this->rnr);

    if($signature === null) {
      if($this->failure) $signature = false;
      else $signature = $this->sign($this->get_code($data));

      if(strstr($signature, 'ERROR')) $this->handle_error($signature);
      if($signature === false) $signature = $this->handle_sig_failure();
      $this->after_sign($data, $signature, $this->certSerial);
    }

    if($url) return $this->urlize($signature);
    return $this->unurlize($signature);
  }

  // if signature device fails, save signature according to the RKSV
  protected function handle_sig_failure()
  {
    $this->failure = true;
    $result = 'U2ljaGVyaGVpdHNlaW5yaWNodHVuZyBhdXNnZWZhbGxlbg';
    $this->after_sig_failure($result);
    return $result;
  }

  protected function after_sig_failure(&$signature) {}    // to be overridden

  protected function get_signed_data($data)
  {
    if(is_int($data)) $data = $this->get_data($data);   // $data is the rnr of an existing receipt
    if($data === false) return false;

    return $this->get_code($data) . '_' . $this->get_signature($data);
  }

  protected function get_compact_signed_data($data)
  {
    if(is_int($data)) $data = $this->get_data($data);
    if($data === false) return false;

    $code = 'eyJhbGciOiJFUzI1NiJ9.' . $this->base64url_encode($this->get_code($data));
    return "$code." . $this->get_signature($data, true);
  }

  protected function get_QRcode($data)
  {
    return $this->generate_QRcode($this->get_signed_data($data), $data);
  }

  protected function update_DEP($data)
  {
    if(is_int($data)) $data = $this->get_data($data);
    if($data === false) return false;

    $result = $this->store_in_DEP($this->get_compact_signed_data($data), $data);
    if(strstr($result, 'ERROR')) $this->handle_error($result);
  }

  // get the string, that is to be saved in the QR-Code - excluding signature
  protected function get_code($data = [])
  {
    $receiptdate = $data['receiptdate'] ?? date('Y-m-d\TH:i:s');
    if(!strstr($receiptdate, 'T')) $receiptdate = date('Y-m-d\TH:i:s', strtotime($receiptdate));  // convert 2019-12-11 20:15:11 -> 2019-12-11T20:15:11

    $amount_normal = $this->formatnum($data['amount_normal']);
    $amount_reduced1 = $this->formatnum($data['amount_reduced1']);
    $amount_reduced2 = $this->formatnum($data['amount_reduced2']);
    $amount_null = $this->formatnum($data['amount_null']);
    $amount_special = $this->formatnum($data['amount_special']);
    $amountstr = str_replace('.', ',', "{$amount_normal}_{$amount_reduced1}_{$amount_reduced2}_{$amount_null}_{$amount_special}");

    $amount_sum = floatval(str_replace(',', '.', $amount_normal)) + floatval(str_replace(',', '.', $amount_reduced1)) +
                  floatval(str_replace(',', '.', $amount_reduced2)) + floatval(str_replace(',', '.', $amount_null)) +
                  floatval(str_replace(',', '.', $amount_special));

    $rnr = $data['rnr'] ?? $this->rnr;    // override only if available in $data

    if($rnr == null) {
      $rnr = $this->get_next_rnr();

      if($rnr == 1) {   // start receipt has not been created!
        $this->init_cashBox();   // so create it
        $rnr = 2;
      }

      $this->rnr = $rnr;
    }

    if(!$data['training']) {
      $turnoverCounter = $data['turnoverCounter'] ?? $this->get_sum() + $amount_sum;
      $this->update_turnoverCounter($turnoverCounter);
    }

    if($data['storno']) $turnoverCounterstr = 'U1RP';
    elseif($data['training']) $turnoverCounterstr = 'VFJB';
    else $turnoverCounterstr = $this->encryptAES($turnoverCounter, $rnr);

    $chainValue = $this->get_chainValue($rnr - 1);
    return "{$this->prefix}_{$this->cashBoxID}_{$rnr}_{$receiptdate}_{$amountstr}_{$turnoverCounterstr}_{$this->certSerial}_{$chainValue}";
  }

  protected function get_chainValue($rnr)
  {
    $result = $this->get_existing_chainValue($rnr);
    if($result) return $result;

    if($rnr == 0) $data = $this->get_cashBoxID();   // start receipt
    else $data = $this->get_compact_signed_data($this->get_data($rnr));

    $hash = hash('sha256', $data, true);
    $result = base64_encode(substr($hash, 0, 8));

    $this->update_chainValue($rnr, $result);
    return $result;
  }

  protected function encryptAES($value, $rnr)
  {
    $bin = pack('J', intval($value * 100)); // pack integer into 64-bit big-endian binary string
    $iv = substr(hash('sha256', $this->get_cashBoxID() . $rnr, true), 0, 16);
    return openssl_encrypt($bin, 'AES-256-CTR', $this->AESkey, false, $iv);
  }

  // transform base64 encoding to base64url encoding
  protected function urlize($data)
  {
    return rtrim(str_replace(['+', '/'], ['-', '_'], $data), '=');
  }

  // transform base64url encoding to base64 encoding
  protected function unurlize($data)
  {
    return str_pad(str_replace(['-', '_'], ['+', '/'], $data), ceil(strlen($data)/4) * 4, '=', STR_PAD_RIGHT);
  }

  protected function base64url_encode($data)
  {
    return $this->urlize(base64_encode($data));
  }

  protected function base64url_decode($data)
  {
    return base64_decode($this->unurlize($data));
  }

  protected function formatnum($num)
  {
    return number_format(floatval(str_replace(',', '.', $num)), 2, ',', '');
  }
}
?>
