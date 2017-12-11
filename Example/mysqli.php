<?php

# PHP-RKSV
# Helper class to access mysql database
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

class Mysql_access
{
  protected $host;
  protected $db;
  protected $user;
  protected $pass;
  public $link;
  public $debug;

  public $DB_RDBMS = 'mysql';
  public $DB_AUTOVAL = 'INT AUTO_INCREMENT';
  public $DB_PRIMARYKEY = 'PRIMARY KEY';


  public function __construct($host = null, $user = null, $pass = null, $database = null)
  {
    if(!class_exists("\\mysqli")):
      $msg = 'class mysqli not found. Is mysqli supported by this PHP installation?';
      print $msg;
      exit;
    endif;

    $this->host = $host;
    $this->db   = $database;
    $this->user = $user;
    $this->pass = $pass;

    #print "$this->host, $this->user, $this->pass, $this->db\n";

    if(is_object($host) && is_a($host, 'mysqli')) $this->link = $host;
    else $this->link = new \mysqli($this->host, $this->user, $this->pass, $this->db);

    if($this->link->connect_errno) {
      print $this->link->connect_error . PHP_EOL;
      exit;
    }

    $this->link->set_charset('utf8');
    $this->link->autocommit(true);
  }


  # query()
  # performs a query and returns errorcode (0=OK, -1=not OK)
  # also logs all actions if $do_log is not set to false

  public function query($sql, $do_log = true)
  {
    $this->link->query($sql);
    if($this->link->error):
      return -1;
    endif;

    return 0;
  }

  public function multi_query($sql, $do_log = true)
  {
    $this->link->multi_query($sql);
    if($this->link->error):
      return -1;
    endif;

    while(\mysqli_next_result($this->link));
    return 0;
  }

  public function prepare($sql)
  {
    $statement = $this->link->prepare($sql);
    return $statement;
  }
  
  public function execute($statement)
  {
    $statement->execute();
    if($this->link->error):
      return -1;
    endif;

    return 0;
  }

  public function get_error() { return $this->link->error; }

  # query_list()
  # performs a query and returns a list of values
  # representing all fields of the first record

  public function query_list($sql, $numindex = false)
  {
    if($numindex) $assoc = MYSQLI_BOTH; else $assoc = MYSQLI_ASSOC;

    $res = $this->link->query($sql);

    if(!is_object($res)) return [];
    $ret = $res->fetch_array($assoc);
    return $ret;
  }
  

  # last_insert_id()
  # returns the id of the last inserted row

  public function last_insert_id($table_ressource = null) 
  { 
    return $this->link->insert_id;
  }
  
  
  # query_value_set()
  # performs a query and returns a list of values
  # representing the first field of all records
  
  public function query_value_set($sql)
  {
    $ret = [];
  
    $result = $this->link->query($sql);
  
    for($i = 0; $i < $result->num_rows; $i++)
      $ret[] = array_shift($result->fetch_row());
  
    return $ret;
  }
  
  
  # query_result()
  # returns a resultset of the given query
  
  public function query_result($sql)
  {
    $result = $this->link->query($sql);
    return $result;
  }
  
  
  # result_fetch_array()
  # fetches row of resultset into array
  
  public function result_fetch_array($result) 
  { 
    if(!is_object($result)) return 'ERROR: result non object';
    return $result->fetch_array(MYSQLI_ASSOC); 
  }
  
  
  # query_arrays()
  # returns array of resultarrays of given query
  
  public function query_arrays($sql)
  {
    $ret = [];
    $result = $this->query_result($sql);
    if($result) while($res = $result->fetch_array(MYSQLI_ASSOC)) $ret[] = $res;
    
    return $ret;
  }

  # query_index_array()
  # returns array with first query result as key and second as value

  public function query_index_array($sql)
  {
    $ret = [];

    $result = $this->query_result($sql);
    if(!is_object($result)) return false;
    while($res = $result->fetch_array()) $ret[$res[0]] = $res[1];
    return $ret;
  }

  # query_index_valueset
  # returns array which is indexed with third parameter
  
  public function query_index_valueset($sql, $valuefield = 'name', $idfield = 'id')
  {
    $ret = [];
  
    $result = $this->query_result($sql);
    while($res = $result->fetch_array()) $ret[$res[$idfield]] = $res[$valuefield];
  
    return $ret;
  }


  # query_value()
  # returns the first field of the first record of given query
  
  public function query_value($sql)
  {
    $result = $this->query_result($sql);

    if ($result->num_rows == 0) return ''; 
    return array_shift($result->fetch_row());
  }
  
  
  # result_numrows()
  # returns the number of rows contained in a resultset
  
  public function result_numrows($result) { return $result->num_rows; }
  
  
  # result_fetch_row()
  # returns next fetched row from resultset
  
  public function result_fetch_row($result) { return $result->fetch_row(); }
  
  public function query_error() { return $this->get_error(); }
  
  public function transaction_start($tname = null) { $this->query('start transaction'); }
  public function transaction_commit($tname = null) { $this->query('commit'); }
  public function transaction_rollback($tname = null) { $this->query('rollback'); }
  
  public function escape($str) 
  {
    return $this->link->real_escape_string($str);
  }
}
?>
