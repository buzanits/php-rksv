<?php

# PHP-RKSV
# provides RKSV functions using the A-Trust service for signatures
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

namespace rksvaustria;

include_once 'rest.php';
include_once 'php-rksv.php';

// rest application for use with A-Trust
class ATrustREST extends REST_Application
{
  protected $url = 'https://hs-abnahme.a-trust.at/asignrkonline/v2/';
  protected $headers = ['Content-Type: application/json'];
  protected $password;

  public function __construct($username, $password)
  {
    $this->url .= "$username/";
    $this->password = $password;
  }

  public function get_signature($data)
  {
    return $this->post('Sign/JWS', ['password' => $this->password, 'jws_payload' => $data]);
  }

  public function get_certificate()
  {
    return $this->get('Certificate');
  }

  public function get_ZDA()
  {
    return $this->get('ZDA');
  }
}


class RKSVATrust extends RKSVAustria
{
  protected $username;
  protected $password;
  protected $error;
  protected $certificate_cache;


  public function __construct($username = null, $password = null, $cashBoxID = null, $AESkey = null, $certSerial = null)
  {
    if($username) $this->username = $username;
    if($password) $this->password = $password;

    parent::__construct($cashBoxID, $AESkey, $certSerial);

    $this->set_prefix('_R1-' . $this->get_ZDA());
  }

  protected function sign($data)   //  override from superclass
  {
    $rest = new ATrustREST($this->username, $this->password);
    $result = $rest->get_signature($data);

    $error = $rest->get_error();
    if(strstr(print_r($error, true), 'statuscode 401')) return 'ERROR: Username or password for A-Trust wrong!';

    if(strstr(print_r($result, true), 'ERROR')) {
      $this->error = $result;
      return false;
    }

    if(!array_key_exists('result', $result)) {
      $this->error = '"result" has not been returned in: ' . print_r($result, true);
      return false;
    }

    list($dummy1, $dummy2, $signature) = explode('.', $result['result']);
    return $signature;
  }

  // get the certificate informations from A-Trust
  // used by the next 3 functions
  protected function get_certificate($key)
  {
    if($this->certificate_cache !== null) {
      $result = $this->certificate_cache;
    } else {
      $rest = new ATrustREST($this->username, $this->password);
      $result = $rest->get_certificate();
    }

    if(strstr(print_r($result, true), 'ERROR')) {
      $this->error = $result;
      return null;
    }

    if(!array_key_exists($key, $result)) {
      $this->error = "'$key' has not been returned in: " . print_r($result, true);
      return null;
    }

    $this->certificate_cache = $result;
    return $result[$key];
  }

  protected function get_certSerial_from_signer() { return $this->get_certificate('ZertifikatsseriennummerHex'); }
  protected function get_certificate_from_signer() { return $this->get_certificate('Signaturzertifikat'); }
  protected function get_CAs_from_signer() { return $this->get_certificate('Zertifizierungsstellen'); }

  // check, if the signature device (i. e. the A-Trust REST-API) ist working (after a previous failure)
  protected function check_signer()
  {
    $this->error = '';
    $serial = $this->get_certSerial_from_signer();
    if($this->error) return false;
    if($serial == '') return false;

    return true;
  }

  protected function get_ZDA()
  {
    $rest = new ATrustREST($this->username, $this->password);
    $result = $rest->get_ZDA();

    if(strstr(print_r($result, true), 'ERROR')) {
      $this->error = $result;
      return 'AT1';   // default value
    }

    if(!array_key_exists('zdaid', $result)) {
      $this->error = '"zdaid" has not been returned in: ' . print_r($result, true);
      return 'AT1';   // default value
    }

    return $result['zdaid'];
  }

  public function get_error() { return $this->error; }
}
?>
