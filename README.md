# php-rksv
PHP classes for dealing with the Austrian Registrierkassensicherheitsverordnung

This project contains classes for generating the data for the mandatory QR code on each receipt according to the "Registrierkassensicherheitsverordnung". These codes are mandatory on each receipt handed out for cash turnovers.

For further details of the Registrierkassensicherheitsverordnung please refer to the informations found by your favorite search engine.

Be aware, that you use this code at your own risk! I do not give any guaranties, that the output of these classes comply with the Austrian Registrierkassensicherheitsverordnung!

If you want help the project by contributing code that makes the current code better or adds additional features, do it with pull requests on Github. Espacially if you want to add additional signing methods! Currently, only signing via the A-Trust REST-API is supported.

This software contains 3rd party open source software:

* PHP QR Code encoder (http://phpqrcode.sourceforge.net), LGPL 3