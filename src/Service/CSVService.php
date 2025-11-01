<?php

namespace App\Service;

class CSVService {
    private string $csvStoragePath;

    public function __construct(string $csvStoragePath) {
        $this->csvStoragePath = $csvStoragePath;

        if (!is_dir($this->csvStoragePath))
            mkdir($this->csvStoragePath, 0755, true);
    }

    public function readCSV(string $filename): array {
        $filepath = $this->csvStoragePath.'/'.$filename;

        if (!file_exists($filepath)) {
            throw new \RuntimeException("File $filename does not exists!");
        }

        $handle = fopen($filepath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Error during opening file $filename!");
        }

        $headers = fgetcsv($handle);
        $data = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $data;
    }

    public function writeCSV(string $filename, array $data): void {
        $filepath = $this->csvStoragePath.'/'.$filename;

        $handle = fopen($filepath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Error during opening file $filename!");
        }

        if (!empty($data)) {
            fputcsv($handle, array_keys($data[0]));
            
            foreach($data as $row) {
                fputcsv($handle, $row);
            }
        } else {
            fputcsv($handle, ['id', 'house_id', 'phone', 'comment', 'created_at', 'status']);
        }

        fclose($handle);
    }

    public function appendCSV(string $filename, array $data): void {
        $filepath = $this->csvStoragePath.'/'.$filename;

        if (!file_exists($filepath)) {
            $this->writeCSV($filename, []);
        }

        $handle = fopen($filepath, 'a');

        if ($handle === false) {
            throw new \RuntimeException("Error during opening file $filename!");
        }

        $fileSize = filesize($filepath);

        foreach($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }
}