<?php

/*
 * This file is part of biosense project
 * (c) Devlion Team
 * @link https://devlion.co
 */

namespace Devlion\Converter;

use Devlion\Converter\Exception\ConversionException;
use Devlion\Converter\Exception\InvalidArgumentException;
use GuzzleHttp\Client as GuzzleClient;
use ZipArchive;

class Client
{
    private $token;
    private $guzzleClient;
    private $zipFile;
    private $extractedDirectory;

    public function __construct($token)
    {
        $this->token = $token;
        $this->guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.convertor.devlion.co/',
        ]);
    }

    public function __destruct()
    {
        if (file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }
    }

    /**
     * @param array $inputFiles
     * @param array $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return Client
     */
    public function convert(array $inputFiles, array $options = []): Client
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

        $this->extractedDirectory = $this->getCacheDir() . '/' . $this->getInputFilesHash($inputFiles);
        $this->zipFile = $this->extractedDirectory . '.zip';

        if (file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }

        $response = $this->guzzleClient->request('POST', 'converter/upload' . $queryString, [
            'multipart' => $parts,
            'sink' => $this->zipFile,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/zip',
            ],
        ]);

        if ('application/json' === $response->getHeader('Content-Type')[0]) {
            $json = json_decode(file_get_contents($this->zipFile), true);
            unlink($this->zipFile);

            throw new ConversionException($json['error']);
        }

        return $this;
    }

    /**
     * @param null|string $destination
     *
     * @return Client
     */
    public function extract(string $destination = null): Client
    {
        $this->extractedDirectory = $destination ?? $this->extractedDirectory;
        $directoryDoneMarker = $this->extractedDirectory . '/done.marker';

        if (file_exists($this->extractedDirectory)) {
            if (file_exists($directoryDoneMarker)) {
                return $this;
            }

            exec('rm -r ' . escapeshellarg($this->extractedDirectory));
        }

        mkdir($this->extractedDirectory);

        $this->extractFiles($this->zipFile, $this->extractedDirectory);

        file_put_contents($directoryDoneMarker, '1');

        return $this;
    }

    /**
     * @return string
     */
    public function getZipFilePath(): string
    {
        return $this->zipFile;
    }

    /**
     * @return string
     */
    public function getExtractedDirectory(): string
    {
        return $this->extractedDirectory;
    }

    /**
     * @param bool $fullPath
     *
     * @return array
     */
    public function getDatabases($fullPath = false): array
    {
        $databases = [];

        if ($dirs = glob($this->extractedDirectory . '/*', GLOB_ONLYDIR)) {
            foreach ($dirs as $nestedDirectory) {
                if ($fullPath) {
                    $databases[basename($nestedDirectory)] = $nestedDirectory;
                } else {
                    $databases[basename($nestedDirectory)] = basename($nestedDirectory);
                }
            }
        }

        return $databases;
    }

    /**
     * @param string $database
     * @param bool   $fullPath
     *
     * @return array
     */
    public function getTables(string $database, $fullPath = false): array
    {
        $databases = $this->getDatabases(true);
        $tables = [];

        if (array_key_exists($database, $databases)) {
            foreach ($this->getCsvFilesOfDirectory($databases[$database]) as $file) {
                $fileInfo = $fileKey = pathinfo($file, PATHINFO_FILENAME);

                if ($fullPath) {
                    $fileInfo = $databases[$database] . '/' . $file;
                }

                $tables[$fileKey] = $fileInfo;
            }
        }

        return $tables;
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @return array
     */
    public function getTableRows(string $database, string $table): array
    {
        $tableFile = null;
        $tables = $this->getTables($database, true);

        if (empty($tables)) {
            throw new InvalidArgumentException('Database does not exist: ' . $table);
        }

        if (array_key_exists($table, $tables) || array_key_exists(strtoupper($table), $tables)) {
            $tableFile = $tables[$table];
        }

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

    private function getCacheDir()
    {
        $cacheDir = sys_get_temp_dir() . '/.devlion-converter-cache';

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        return $cacheDir;
    }

    private function getInputFilesHash(array $inputFiles)
    {
        $this->validateInputFiles($inputFiles);

        $dataToHash = '';

        foreach ($inputFiles as $inputFile) {
            $dataToHash .= basename($inputFile) . '=' . sha1_file($inputFile) . "\n";
        }

        return sha1($dataToHash);
    }

    public function getDatabasesTables($fullPath = false)
    {
        $tables = [];

        if ($dirs = glob($this->extractedDirectory . '/*', GLOB_ONLYDIR)) {
            foreach ($dirs as $nestedDirectory) {
                $tables[basename($nestedDirectory)] = [];

                foreach ($this->getCsvFilesOfDirectory($nestedDirectory) as $file) {
                    $fileInfo = $fileKey = pathinfo($file, PATHINFO_FILENAME);

                    if ($fullPath) {
                        $fileInfo = $nestedDirectory . '/' . $file;
                    }

                    $tables[basename($nestedDirectory)][$fileKey] = $fileInfo;
                }
            }
        }

        return $tables;
    }

    public function getDatabasesTableRows($table)
    {
        $rows = [];
        $tableFile = null;
        $databases = $this->getDatabasesTables(true);

        foreach ($databases as $database => $tables) {
            if (array_key_exists($table, $tables)) {
                $rows[$database] = [];


                if (!file_exists($tables[$table])) {
                    continue;
                }

                $h = fopen($tables[$table], 'r');

                if (false === $h) {
                    continue;
                }

                while (false !== ($row = fgetcsv($h, 0, ','))) {
                    $rows[$database][] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @param $zipFile
     * @param $destination
     */
    private function extractFiles($zipFile, $destination)
    {
        if (!file_exists($destination)) {
            mkdir($destination);
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFile);
        $zipArchive->extractTo($destination);

        unlink($zipFile);

        if ($nestedArchives = glob($destination . '/*.zip')) {
            foreach ($nestedArchives as $archive) {
                $nested = $this->baseName($this->strAfter($archive, '_'), '.zip');
                $this->extractFiles($archive, $destination . '/' . $nested);
            }
        }
    }

    /**
     * @param $filePath
     * @param string $ext
     *
     * @return string
     */
    private function baseName($filePath, $ext = '.'): string
    {
        return strstr($filePath, $ext, true);
    }

    /**
     * @param $subject
     * @param $search
     *
     * @return string
     */
    private function strAfter($subject, $search): string
    {
        if ('' == $search) {
            return $subject;
        }

        $pos = strpos($subject, $search);

        if (false === $pos) {
            return $subject;
        }

        return substr($subject, $pos + strlen($search));
    }

    /**
     * @param array $files
     */
    private function validateInputFiles(array $files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new InvalidArgumentException('Input file does not exist: ' . $file);
            }
        }
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    private function getCsvFilesOfDirectory($dir = '.'): array
    {
        $files = [];

        $dh = opendir($dir);
        if (false === $dh) {
            throw new InvalidArgumentException('Could not open directory');
        }

        while (false !== ($file = readdir($dh))) {
            if ('.' == $file || '..' == $file || '.csv' !== substr($file, -4)) {
                continue;
            }

            $files[] = $file;
        }

        closedir($dh);

        return $files;
    }
}