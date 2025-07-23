<?php
namespace App\Services;

class MetadataService
{
    public static function createJson(array $certData): array
    {
        $json = [
            "schema_version" => "1.0",
            "institution" => [
                "name" => "Universidad de Dagupan",
                "contract" => env('CERT_CONTRACT_ADDRESS'),
            ],
            "recipient" => [
                "full_name"       => $certData['full_name'],
                "student_id_hash" => hash('sha256', $certData['student_id']),
                "email"           => $certData['email'],
            ],
            "credential" => [
                "type"          => $certData['type'],
                "program"       => $certData['program'],
                "date_awarded"  => $certData['date_awarded'],
                "serial_number" => $certData['serial_number'],
            ],
            "revocation" => ["status" => "valid"],
        ];

        $filePath = storage_path("app/tmp/{$certData['serial_number']}.json");
        file_put_contents($filePath, json_encode($json, JSON_PRETTY_PRINT));

        return ['json' => $json, 'file_path' => $filePath];
    }

    public static function uploadToIPFS(string $filePath): string
    {
        $script = base_path("node/uploadPinata.js");
        $cmd    = "node {$script} " . escapeshellarg($filePath) . " 2>&1";
        $out    = shell_exec($cmd);

        if (!preg_match('/ipfs:\/\/[^\s]+/', $out, $m)) {
            throw new \Exception("Pinata upload failed: {$out}");
        }
        return trim($m[0]);
    }

    public static function getCertHash(array $json): string
    {
        $raw = $json['recipient']['student_id_hash']
             . $json['credential']['serial_number']
             . $json['credential']['date_awarded'];
        return '0x' . hash('sha256', $raw);
    }
}
