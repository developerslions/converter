<?php

error_reporting(E_ALL | E_STRICT);

require_once dirname(__DIR__).'/vendor/autoload.php';

use Devlion\Converter\Client;
use Devlion\Converter\Exception\ConversionException;

$inputFiles = [dirname(__DIR__).'/vendor/devlion/converter-php-client/samples/sample.mdf'];
$options = ['outputFormat' => 'csv'];

try {
    echo "Executing conversion process, this might take some time..\n";

    $client = new Client('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7Im5hbWUiOiJUZXN0IiwiZW1haWwiOiJtY2tsYXlpbkBnbWFpbC5jb20iLCJwbGFuIjp7Im5hbWUiOiJLaWQiLCJmaWxlX3NpemUiOiIzMCIsImZpbGVfbGltaXQiOiI1In19LCJpYXQiOjE1NTkyNDY0NDEsImV4cCI6MTkxOTI0NjQ0MX0.qsEH8BFEpBkAZ0WC56UJ-5owWoeudt1GGEwyQyPCGdc');

    //$tables = $client->convert($inputFiles, $options)->extract()->getDatabases();
    // $tables = $client->convert($inputFiles, $options)->extract()->getTables('sample');
    //$tables = $client->convert($inputFiles, $options)->extract()->getTableRows('sample', 'POINTS_TABLE');
    // $tables = $client->convert($inputFiles, $options)->extract()->getDatabasesTables();
    $tables = $client->convert($inputFiles, $options)->extract()->getDatabasesTableRows('POINTS_TABLE');

    echo '<pre>';
    print_r($tables);

    echo "Conversion successful!\n";
} catch (ConversionException $e) {
    echo "Conversion failed: ".$e->getMessage()."\n";
}
