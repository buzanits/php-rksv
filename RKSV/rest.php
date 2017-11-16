<?php

# PHP-RKSV
# Helper class for REST-API usage
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

namespace rksvaustria;

class REST
{ 
  protected $url, $data, $method;
  protected $username, $password;
  protected $headers;
  protected $error;

  public function __construct($url = null, $data = null, $method = 'GET')
  {
    if($this->disabled) return;

    $this->url = $url;
    $this->data = $data;
    $this->method = $method;
  }

  public function __invoke($url = null, $data = null, $method = null)
  {
    if($this->disabled) return true;

    if($url === null) $url = $this->url;
    if($data === null) $data = $this->data;
    if($method === null) $method = $this->method;

    $curl = curl_init();

    switch ($method):
    case 'POST':
      curl_setopt($curl, CURLOPT_POST, 1);
      if($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    break;
    case 'PUT':
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
      if($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    break;
    case 'DELETE':
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    break;
    default:
      if($data) $url = sprintf('%s?%s', $url, http_build_query($data));
    endswitch;

    // Optional Authentication:
    if($this->username):
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_USERPWD, "$this->username:$this->password");
    endif;

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_VERBOSE, 1);

    if($this->headers) curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

    $result = curl_exec($curl);
    $statuscode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($statuscode != '200') $this->error = "statuscode $statuscode: $result";

    curl_close($curl);

    // Hook after_call
    $this->after_call($result);

    return $result;
  }

  protected function after_call(&$result) {}
  public function get_error() { return $this->error; }
  public function set_headers($headers) { $this->headers = $headers; }

  public function set_ceredentials($username, $password)
  {
    $this->username = $username;
    $this->password = $password;
  }
}


class REST_Application
{
  protected $url;
  protected $headers;
  protected $error;

  public function __construct($url = null)
  {
    if($url) $this->url = $url;
  }

  protected function post($uri, $data)
  {
    $headers = $this->headers;

    $rest = new REST("$this->url$uri", json_encode($data), 'POST');
    $rest->set_headers($headers);
    $result = $rest();

    if($this->error = $rest->get_error()) return "ERROR in post to '$this->url$uri': $this->error";
    return json_decode($result, true);
  }

  protected function put($uri, $data)
  {
    $headers = $this->headers;

    $rest = new REST("$this->url$uri", json_encode($data), 'PUT');
    $rest->set_headers($headers);
    $result = $rest();

    if($this->error = $rest->get_error()) return "ERROR in put to '$this->url$uri': $this->error";
    return json_decode($result, true);
  }

  protected function get($uri)
  {
    $headers = $this->headers;

    $rest = new REST("$this->url$uri", null, 'GET');
    $rest->set_headers($headers);
    $result = $rest();

    if($this->error = $rest->get_error()) return "ERROR in get to '$this->url$uri': $this->error";
    return json_decode($result, true);
  }

  protected function delete($uri)
  {
    $headers = $this->headers;

    $rest = new REST("$this->url$uri", null, 'DELETE');
    $rest->set_headers($headers);
    $result = $rest();

    if($this->error = $rest->get_error()) return "ERROR in delete to '$this->url$uri': $this->error";
    return json_decode($result, true);
  }

  public function get_error() { return $this->error; }
}
?>
