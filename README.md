# Godlike
#### A cool PHP debug tool

---

### Overview

This is a collection of debug/help PHP classes,
intended to be prepended on every CGI or CLI PHP script. 

*Disclaimer:* Godlike is **not safe or recommended** to be used on production. 

Just require the ```prepend.php``` file in the beginning of the entry point PHP script (usually index.php).
If PDO is compiled for override (PDO class name is changed to PDO_original), the main script will also override PDO
to track queries and log stats about time and query/transaction count.

Godlike also has the ability to seed the RNG and system time on linux if libfaketime is installed.

**PDO override restrictions:**
- MySQL autocommit OFF is not supported. It will just not work as expected.
- Transactions in SQL statements are not supported (START TRANSACTION). Use only PDO::beginTransaction and such.
- Only EXCEPTION error mode is supported. Exception will be thrown otherwise.

### Response headers

If enabled, Godlike will add additional response headers, that could be useful for debug.


### Usage

If you want to configure godlike, copy the config.tpl.ini to the same directory with the name `config.ini`.

Besides that, just set the bin/prepend.php as your php prepend script in php.ini or require it in index.php.

Additional ini configurations available if you are using compiled Godlike:

| Option               | Allowed values | Description                                                                |
|----------------------|----------------|----------------------------------------------------------------------------|
| strict_enabled       | 1/0            | Enable or disable strict_types for all files                               |
| strict_force_declare | 1/0            | Enable or disable strict_mode for files with `declare(strict_types=0)`     |
| log_enabled          | 1/0            | Enable or disable Godlike logs                                             |
| log_path             | string         | Set file to be used for Godlike logs                                       |
| headers_enabled      | 1/0            | Enable or disable Godlike all debug headers                                |
| stats_enabled        | 1/0            | Enable or disable duration, queries and transactions Godlike stats headers |
| stats_duration       | 1/0            | Enable or disable request duration info header                             |
| stats_queries        | 1/0            | Enable or disable queries info header                                      |
| stats_transactions   | 1/0            | Enable or disable MySQL transactions info header                           |



#### Manipulating single requests

One way to manipulate time and RNG is to add special headers to all requests that hit your API/website.

| Header               | Allowed values | Description                                                                |
|----------------------|----------------|----------------------------------------------------------------------------|
| GODLIKE-NO-PREPEND   | true/false     | Disable all Godlike functions for current request                          |
| GODLIKE-NO-SEED      | true/false     | Disable time & RNG seed for current request                                |
| GODLIKE-NO-LOG       | true/false     | Disable logging for current request                                        |
| GODLIKE-NAME         | string         | Used as label in logs                                                      |
| GODLIKE-SEED-RNG     | string         | Use custom RNG seed for current request                                    |
| GODLIKE-SEED-TIME    | timestamp      | Use custom timestamp for current request                                   |


#### API

Godlike exposes API endpoint which enables you to configure and reset your entire environment so no additional headers need to be used.
Postman collection, containing all available api requests exists in `postman` directory.
Additionally API documentation can be found [here](https://documenter.getpostman.com/view/9531489/SW7Z48hm?version=latest)


### Logging

If logs are enabled in config.ini, Godlike will log:
 - Type of request (CLI/CGI) together with it's full ID (process id number and name, if provided via GODLIKE-NAME header).
 - Current date and timestamp.
 - RNG seed - initial seed value and number of requests after initial seed.
 - Time seed - Initial timestamp, time scale, min and max time steps and real timestamp when last used.
 - Total execution time.
 - Every query together with it's params and execution time. 
 
Example log entry:

```
    ================================================================================
    | CGI #51
    ================================================================================
    
    Time: 2019-11-20 09:21:16 / 1574241676857
    Seed: {"seed":null,"count":0} / {"timestamp":1574173671652,"scale":2,"stepMin":null,"stepMax":null,"lastHit":1574241676857}
    Request: POST /foo/bar
    
    | Stats:
    ----------------------------------------
    Duration: 2237‬ us
    Queries:
        Total:
            Count: 3
            Duration: 1356‬ 
        List:
              SELECT `type`, `value` FROM settings
    
            Params: []
            Time:   549 us
            Count:   1
            ______________________________________________________________________
              SELECT fromCurrency, toCurrency, rate
              FROM currencies_exchange_rates
              WHERE fromCurrency = :fromCurrency;
    
            Params: {":fromCurrency":"GBP"}
            Time:   392 us
            Count:   1
            ______________________________________________________________________
              SELECT * FROM `sessions` WHERE `token` = :token AND timeExpires > :timeExpires AND cancelled = 0
    
            Params: {":token":"JMO1Jaarhv",":timeExpires":"2019-11-19 14:27:51"}
            Time:   415 us
            Count:   1
```