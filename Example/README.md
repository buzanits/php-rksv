# php-rksv Example

This is an example to demonstrate, how the classes in /RKSV are working. The goal is to sign the receipts (invoices) in the test database provided with this example with a test A-Trust account and store the signature details in the database.

To use this example follow these steps:

* Be sure to have a MySQL database server up and running

* Create a new database (for example call it "rksvtest")

* Import the provided phprksv.sql into your new database
  You will find three tables there: One holding the invoices to be signed, one the positions on these invoices and one (empty) that should hold the signature details after signing.

* Run sign.php on the shell with "php sign.php" or run it from a browser.
  Ignore the loads of warnings and outputs. Look into the table "rksvreceipt". There should be a row for every invoice and some additional rows for "Nullbelege".

* Run show_dep.php to export the DEP (Datenerfassungsprotokoll). If you do not run it from a browser delete the last lines in the code of show_dep.php for a proper output.

* To adjust the test example for your own infrastructure, you have to rewrite the file kasse.php. There you find all the methods to access and store all the data. You should not need to change any files in the directory RSKV. If you need to change anything there to meet your environment that you can't fix in kasse.php, something has gone wrong in the basic code. So do not hesitate to contact the author or (better) open an issue on Github.

* If you want help the project by contributing code that makes the current code better or adds additional features, do it with pull requests on Github. Espacially if you want to add additional signing methods! Currently, only signing via the A-Trust REST-API is supported.
