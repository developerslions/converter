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


Example
-------

The following code is an example on how to convert a Microsoft Sql Server file (.MDF) to a ZIP-archive of CSV files. You can replace 'k=token' with the Customer Token that you purchased.

```php
use Devlion\Converter\Client;

$client = new Client('token');

$inputFiles = ['/tmp/access.accdb'];

$outputFile = $client->convertAndReceiveZip($inputFiles);

echo "Conversion successful, check out $outputFile!\n";
```


License
-------

This code is licensed under the [MIT license](https://opensource.org/licenses/MIT).
