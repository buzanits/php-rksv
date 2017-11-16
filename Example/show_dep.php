<?php

# PHP-RKSV
# Test script that reads the DEP (Datenerfassungprotokoll) from the example database
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

include_once 'kasse.php';

$DB = new Mysql_access('localhost', 'myusername', 'mypassword', 'mydb');

$kasse = new Kasse('u123456789', '123456789', '1');
$kasse->setDB($DB);


$dep = $kasse->export_DEP();

// make it more readable in the browser - delete this lines if you do not run this in a browser
$dep = str_replace('{', '<br>{', $dep);
$dep = str_replace('}', '}<br>', $dep);
$dep = str_replace('[', '<br>[', $dep);
$dep = str_replace(']', ']<br>', $dep);
$dep = str_replace(',', ',<br>', $dep);

print $dep;
?>
