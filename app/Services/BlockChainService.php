<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class BlockchainService
{
    /**
     * Issue on‑chain and return both certId and txHash.
     *
     * @return array ['certId'=>int, 'txHash'=>string]
     */
    public static function issueCertificate(string $certHash, string $ipfsURI): array
    {
        $script  = base_path('node/issueCertificate.js');
        $hashArg = escapeshellarg($certHash);
        $uriArg  = escapeshellarg($ipfsURI);

        $cmd = "node {$script} {$hashArg} {$uriArg} 2>&1";
        Log::debug("Issuance cmd: {$cmd}");
        $out = shell_exec($cmd);
        Log::debug("Issuance raw output: {$out}");

        // Grab all {...} JSON blocks
        if (!preg_match_all('/\{.*?\}/s', $out, $all)) {
            throw new \Exception("No JSON found in output: {$out}");
        }
        $payload = end($all[0]);
        $data    = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON parse error: " . json_last_error_msg());
        }
        if (!isset($data['certId'], $data['txHash'])) {
            throw new \Exception("Missing keys in payload: {$payload}");
        }
        return ['certId' => (int)$data['certId'], 'txHash' => $data['txHash']];
    }
}
