# Godlike
#### A cool PHP debug tool

---

This is a collection of debug/help PHP classes,
intended to be prepended on every CGI or CLI PHP script.

Just require the ```prepend.php``` file in the beginning of the entrypoint PHP script (usually index.php).
If PDO is compiled for override (PDO class name is changed to PDO_original), the main script will also override PDO
to track queries and log stats about time and query/transaction count.

Godlike also has the ability to seed the RNG and system time on linux if libfaketime is installed.

**PDO override restrictions:**
- MySQL autocommit OFF is not supported. It will just not work as expected.
- Transactions in SQL statements are not supported (START TRANSACTION). Use only PDO::beginTransaction and such.
- Only EXCEPTION error mode is supported. Exception will be thrown otherwise.


### Usage

If you want to configure godlike, copy the config.tpl.ini to the same directory with the name `config.ini`.

Besides that, just set the bin/prepend.php as your php prepend script in php.ini or require it in index.php.
