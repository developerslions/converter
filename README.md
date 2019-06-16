# devlion-converter-php-client


Installation via Composer
-------------------------

The recommended method to install is through [Composer](http://getcomposer.org).

1. Add `devlion/converter-php-client` as a dependency in your project's `composer.json`:

    ```json
    {
        "require": {
            "devlion/converter-php-client": "*"
        }
    }
    ```

2. Download and install Composer:

    ```bash
    curl -s http://getcomposer.org/installer | php
    ```

3. Install your dependencies:

    ```bash
    php composer.phar install
    ```

4. Require Composer's autoloader

    Composer also prepares an autoloader file that helps to autoload the libraries it downloads. To use it, just add the following line to your application:

    ```php
    <?php

    require 'vendor/autoload.php';

    use Devlion\Converter\Client;

    $client = new Client('token');
    ```
You can find out more about Composer at [getcomposer.org](http://getcomposer.org).

Methods:
-------
***Convert***: 

Put a database file and convert to specified database & save as zip file.
```php
$inputFiles = ['/samples/sample.mdf'];
$options = ['outputFormat' => 'csv'];

$client->convert($inputFiles, $options)
```


***Extract***:

Unzip converted file.

```php
$client->extract();
```

***getZipFilePath***:

Get converted zip file path.
```php
$client->getZipFilePath();
```

***getExtractedDirectory***:

Get extracted directory path.
```php
$client->getExtractedDirectory();
```

***Methods below work only for csv outputType***

***getDatabases***:

Get converted databases array.
```php
$databases = $client->extract()->getDatabases();
```

***getTables***:

Get list of tables for selected database.
```php
$tables = $client->getTables('sample');
```

***getTableRows***:

Get data of selected table.
```php
$rows = $client->getTableRows('POINTS_TABLE');
```

***getDatabasesTables***:

Get all converted databases and all tables for each database.
```php
$data = $client->getDatabasesTables();
```


***getDatabasesTableRows***:

Get rows of selected table for each database where table exists.
```php
$data = $client->getDatabasesTableRows('POINTS_TABLE');
```

Example
-------

The following code is an example on how to convert a Microsoft Sql Server file (.MDF) to a ZIP-archive of CSV files. You should replace 'token' with the Customer Token that you purchased.

```php
use Devlion\Converter\Client;

$client = new Client('token');

$inputFiles = ['/samples/sample.mdf'];

$databases = $client->convert($inputFiles, $options)->extract()->getDatabases();
$outputFile = $client->getZipFilePath();

echo "Conversion successful, check out $outputFile!\n";
echo "<pre>";
print_r($databases);
```


License
-------

This code is licensed under the [MIT license](https://opensource.org/licenses/MIT).
