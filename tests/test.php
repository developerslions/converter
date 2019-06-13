<?php

error_reporting(E_ALL | E_STRICT);

require_once dirname(__DIR__).'/vendor/autoload.php';

use Devlion\Converter\Client;
use Devlion\Converter\Exception\ConversionException;

$inputFiles = [dirname(__DIR__).'/samples/sample.mdf'];
$options = ['outputFormat' => 'csv'];

try {
    echo "Executing conversion process, this might take some time..\n";

    $client = new Client('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7Im5hbWUiOiJUZXN0IiwiZW1haWwiOiJtY2tsYXlpbkBnbWFpbC5jb20iLCJwbGFuIjp7Im5hbWUiOiJLaWQiLCJmaWxlX3NpemUiOiIzMCIsImZpbGVfbGltaXQiOiI1In19LCJpYXQiOjE1NTkyNDY0NDEsImV4cCI6MTkxOTI0NjQ0MX0.qsEH8BFEpBkAZ0WC56UJ-5owWoeudt1GGEwyQyPCGdc');
    $zipFile = $client->convertAndReceiveZip($inputFiles, $options);

    echo "Conversion successful!\n";
    echo "You can find the ZIP archive containing the CSV files inside $zipFile\n";
} catch (ConversionException $e) {
    echo "Conversion failed: ".$e->getMessage()."\n";
}
