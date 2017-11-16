<?php

# PHP-RKSV
# Test script that reads the DEP (Datenerfassungprotokoll) from the example database
#
# Author: DI Peter Buzanits
# Licence: GPL 3.0

include_once 'kasse.php';

$kasse = Kasse('u123456789', '123456789', '1');

$dep = $kasse->export_DEP();

// make it more readable in the browser - delete this lines if you do not run this in a browser
$dep = str_replace('{', '<br>{', $dep);
$dep = str_replace('}', '}<br>', $dep);
$dep = str_replace('[', '<br>[', $dep);
$dep = str_replace(']', ']<br>', $dep);
$dep = str_replace(',', ',<br>', $dep);

print $dep;
?>
