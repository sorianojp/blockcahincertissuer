<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MetadataService
{
    public static function createJson(array $certData): array
    {
        $json = [
            "schema_version" => "1.0",
            "institution" => [
                "name" => "Universidad de Dagupan",
                "contract" => "0x9fc9D5982f87af6937cC9748ED1B04969Fc142b8"
            ],
            "recipient" => [
                "full_name" => $certData['full_name'],
                "student_id_hash" => hash('sha256', $certData['student_id']),
                "email" => $certData['email']
            ],
            "credential" => [
                "type" => $certData['type'],
                "program" => $certData['program'],
                "date_awarded" => $certData['date_awarded'],
                "serial_number" => $certData['serial_number']
            ],
            "revocation" => ["status" => "valid"]
        ];

        // Save JSON to file
        $filePath = storage_path("app/tmp/{$certData['serial_number']}.json");
        file_put_contents($filePath, json_encode($json, JSON_PRETTY_PRINT));

        return [
            'json' => $json,
            'file_path' => $filePath
        ];
    }

public static function uploadToIPFS($filePath): string
{
    $script = base_path("node/uploadPinata.js");
    $command = "node {$script} {$filePath}";
    $output = shell_exec($command);

    // Find actual IPFS URI (starts with ipfs://)
    preg_match('/ipfs:\/\/[^\s]+/', $output, $matches);

    if (!$matches) {
        throw new \Exception('Pinata upload failed. Output: ' . $output);
    }

    return trim($matches[0]);
}


    public static function getCertHash($json): string
    {
        // Hash selected fields (student_id_hash + serial_number + date_awarded)
        $raw = $json['recipient']['student_id_hash'] .
               $json['credential']['serial_number'] .
               $json['credential']['date_awarded'];

        return "0x" . hash('sha256', $raw);
    }
}
