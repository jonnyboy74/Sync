This module provides a way to download a csv file from a FTP server and create/update products within the Magento 2 database.

----------------------
Command Line
----------------------

php bin/magento onlinepromo_sync:syncall


----------------------
Example file
----------------------

An example of the pipe delimited file which we download would be,

```
sku|product_name|short_description|long_description|attribute_set_id|status|visibility|tax_class_id|price|qty|weight|package_contents
17sku|product_name12|short_description wwww|long_description|4|1|4|1|9.99|4|5|package_contents 1234
```