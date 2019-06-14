<?php

/*
 * This file is part of biosense project
 * (c) Devlion Team
 * @link https://devlion.co
 */

namespace Devlion\Converter;

use GuzzleHttp\Client as GuzzleClient;
use Devlion\Converter\Exception\ConversionException;
use Devlion\Converter\Exception\InvalidArgumentException;
use ZipArchive;

class Client
{
    private $token;
    private $guzzleClient;

    public function __construct($token)
    {
        $this->token = $token;

        $this->guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.convertor.devlion.co/',
        ]);
    }

    public function getCacheDir()
    {
        $cacheDir = sys_get_temp_dir() . '/.devlion-converter-cache';

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        return $cacheDir;
    }

    public function getInputFilesHash(array $inputFiles)
    {
        $this->validateInputFiles($inputFiles);

        $dataToHash = '';

        foreach ($inputFiles as $inputFile) {
            $dataToHash .= basename($inputFile) . '=' . sha1_file($inputFile) . "\n";
        }

        return sha1($dataToHash);
    }

    public function convertAndReceiveZip(array $inputFiles, array $options = [])
    {
        $this->validateInputFiles($inputFiles);

        $parts = [];

        foreach ($inputFiles as $inputFile) {
            $parts[] = [
                'name' => 'files[]',
                'filename' => basename($inputFile),
                'contents' => fopen($inputFile, 'r'),
            ];
        }

        $queryString = '';
        if (count($options) > 0) {
            $queryString = '?' . http_build_query($options);
        }

        $zipFile = $this->getCacheDir() . '/' . $this->getInputFilesHash($inputFiles) . '.zip';

        if (file_exists($zipFile)) {
            unlink($zipFile);
        }

        $response = $this->guzzleClient->request('POST', 'converter/upload' . $queryString, [
            'multipart' => $parts,
            'sink' => $zipFile,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/zip',
            ],
        ]);

        if ('application/json' === $response->getHeader('Content-Type')[0]) {
            $json = json_decode(file_get_contents($zipFile), true);
            unlink($zipFile);

            throw new ConversionException($json['error']);
        }

        return $zipFile;
    }

    public function convertAndReceiveCsvDirectory(array $inputFiles)
    {
        $this->validateInputFiles($inputFiles);

        $csvDirectory = $this->getCacheDir() . '/' . $this->getInputFilesHash($inputFiles) . '-csv';
        $csvDirectoryDoneMarker = $csvDirectory . '/done.marker';

        if (file_exists($csvDirectory)) {
            if (file_exists($csvDirectoryDoneMarker)) {
                return $csvDirectory;
            }

            exec('rm -r ' . escapeshellarg($csvDirectory));
        }

        mkdir($csvDirectory);

        $zipFile = $this->convertAndReceiveZip($inputFiles);

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFile);
        $zipArchive->extractTo($csvDirectory);

        unlink($zipFile);

        file_put_contents($csvDirectoryDoneMarker, '1');

        return $csvDirectory;
    }

    public function getDatabaseTables(array $inputFiles)
    {
        $this->validateInputFiles($inputFiles);

        $csvDirectory = $this->convertAndReceiveCsvDirectory($inputFiles);

        $tables = [];

        foreach ($this->getCsvFilesOfDirectory($csvDirectory) as $file) {
            $tables[] = pathinfo($file, PATHINFO_FILENAME);
        }

        return $tables;
    }

    public function getDatabaseTableRows(array $inputFiles, $table)
    {
        $this->validateInputFiles($inputFiles);

        $csvDirectory = $this->convertAndReceiveCsvDirectory($inputFiles);

        $tableFile = $csvDirectory . '/' . $table . '.csv';

        if (!file_exists($tableFile)) {
            throw new InvalidArgumentException('Table does not exist: ' . $table);
        }

        $h = fopen($tableFile, 'r');

        if (false === $h) {
            throw new InvalidArgumentException('Could not open table file: ' . $tableFile);
        }

        $rows = [];

        while (false !== ($row = fgetcsv($h, 0, ','))) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function validateInputFiles(array $files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException('Input file does not exist: ' . $file);
            }
        }
    }

    private function getCsvFilesOfDirectory($dir = '.')
    {
        $files = [];

        $dh = opendir($dir);
        if (false === $dh) {
            throw new InvalidArgumentException('Could not open directory');
        }

        while (false !== ($file = readdir($dh))) {
            if ('.' == $file or '..' == $file or '.csv' !== substr($file, -4)) {
                continue;
            }

            $files[] = $file;
        }

        closedir($dh);

        return $files;
    }
}
