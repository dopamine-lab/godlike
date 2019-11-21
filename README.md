# Godlike
#### PHP debug tool for controlling time and randomness while testing. Predictability is all.

Godlike is a collection of debug/help PHP classes intended to be prepended on every CGI or CLI PHP script.
It allows time control and time travel, as well as seeding the RNG for complete predictability of every request even for random based software.

Godlike also has the *experimental* ability to seed the RNG and system time on linux if libfaketime is installed. It will use /etc/faketimerc to set the timestamp.

**IMPORTANT:** Godlike is made for **debug** and **testing** purposes only! Do not use it in production unless you know what you're doing.


## Usage

Just require the ```prepend.php``` file in the beginning of the entry point PHP script (usually index.php).
If PDO is compiled for override (PDO class name is changed to PDO_original), the main script will also override PDO
to track queries and log stats about time and query/transaction count.

**PDO override restrictions:**
- MySQL autocommit OFF is not supported. It will just not work as expected.
- Transactions in SQL statements are not supported (START TRANSACTION). Use only PDO::beginTransaction and such.
- Only EXCEPTION error mode is supported. Exception will be thrown otherwise.

### Manipulating single requests

One way to manipulate time and RNG is to add special headers to all requests that hit your API/website.

| Header               | Allowed values | Description                                                                |
|----------------------|----------------|----------------------------------------------------------------------------|
| GODLIKE-NO-PREPEND   | true/false     | Disable all Godlike functions for current request                          |
| GODLIKE-NO-SEED      | true/false     | Disable time & RNG seed for current request                                |
| GODLIKE-NO-LOG       | true/false     | Disable logging for current request                                        |
| GODLIKE-NAME         | string         | Used as label in logs                                                      |
| GODLIKE-SEED-RNG     | string         | Use custom RNG seed for current request                                    |
| GODLIKE-SEED-TIME    | timestamp      | Use custom timestamp for current request                                   |

### Response headers

If enabled, Godlike will add additional response headers, that could be useful for debug:

| Header                    |  Description                                                                          |
|---------------------------|---------------------------------------------------------------------------------------|
| X-Godlike-R-Duration      |  Total duration of the request                                                        |
| X-Godlike-R-Id            |  Request ID (process sequential number and name, if provided via GODLIKE-NAME header) |
| X-Godlike-R-Queries       |  Total count and duration of all SQL queries executed in this request                 |
| X-Godlike-R-Seed          |  RNG and time seeds used in this request                                              |
| X-Godlike-R-Time          |  Server time & timestamp of this request                                              |
| X-Godlike-R-Transactions  |  Number of SQL transactions and total time spent in transaction                       |

### API

Godlike exposes API endpoint which enables you to configure and reset your entire environment so no additional headers need to be used.
Postman collection, containing all available api requests exists in `postman` directory.
Additionally API documentation can be found [here](https://documenter.getpostman.com/view/9531489/SW7Z48hm?version=latest)

### Configuration

Just set the `bin/prepend.php` as your php prepend script in `php.ini` or require it in `index.php`.
*Optionally*, copy the `config.tpl.ini` to the same directory with the name `config.ini`.

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


## Logging

If enabled in `config.ini`, Godlike will log:
 - Type of request (CLI/CGI) together with it's full ID (process sequential number and name, if provided via GODLIKE-NAME header).
 - Current date and timestamp.
 - RNG seed - initial seed value and number of requests after initial seed.
 - Time seed - Initial timestamp, time scale, min and max time steps and real timestamp when last used.
 - Total execution time.
 - Every query together with it's params and execution time. 
 
Example:

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


## Debug functions

Regardless of the way you are using Godlike (compiled it on top of PHP or just injected it as prepend script), you have access to the following classes:

### Timer

Useful for identifying slow parts of your application. All time values are in microseconds.

#### tick

```php
\godlike\Timer::tick(string $key, bool $silent, bool $real)
```

Parameters:
 - key    -  Name of the event that took place. Default 'MAIN'.
 - silent - Weather or not to log current tick in the Godlike's log file. Default false.
 - real   -  Indicates if real or seeded time should be used. Default true.

Log format:
```
<Current timestamp> [Timer] <key> <sequential tick with this key> <time elapsed from previous tick with this key> <time elapsed from first tick with the this key> <time elapsed from beggining of request>  
```

Example log entry:
```
[2019-11-19 14:41:55.000] [TIMER] [MAIN] #1: 12, 12, 40397
[2019-11-19 14:41:55.000] [TIMER] [Validation] #1: 12, 12, 41496
[2019-11-19 14:41:55.000] [TIMER] [Validation] #2: 568, 580, 42064
```

#### getMessages

```php
\godlike\Timer::getMessages()
```

Returns array containing information about all ticks:

Response format:
```
Timer-<key>-<sequential tick with this key>: <time elapsed from previous tick with this key>, <time elapsed from first tick with the this key>, <time elapsed from beggining of request>  
```

Example response:
```
[
    'Timer-MAIN-1: 12, 12, 40397',
    'Timer-Validation-1: 12, 12, 41496',
    'Timer-Validation-2: 568, 580, 42064',
]
```

#### clear

```php
\godlike\Timer::clear()
```

Clears all ticks.


#### Logger

Useful for identifying the flow of the application. Note, that message will be truncated to 150 symbols.
 
#### header

```php
\godlike\Logger::header(string $title, bool $silent, bool $real)
```

Parameters:
 - title  - Self explanatory.
 - silent - Weather or not to log in the Godlike's log file. Default false.
 - real   - Indicates if real or seeded time should be used. Default true.

#### log

```php
\godlike\Logger::log(mixed $data, bool $silent, bool $real)
```

Parameters:
 - data   - Self explanatory. Accepts any type of data as long as it can by json_encoded. Note: strings and numeric data will not be json encoded. 
 - silent - Weather or not to log in the Godlike's log file. Default false.
 - real   - Indicates if real or seeded time should be used. Default true.

#### getMessages

```php
\godlike\Logger::getMessages()
```

Returns array containing all headers and messages.


#### clear

```php
\godlike\Timer::clear()
```

Clears all headers and messages.
