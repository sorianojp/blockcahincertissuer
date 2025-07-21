<?php
namespace App\Services;


class BlockchainService
{
    public static function issueCertificate($certHash, $ipfsURI)
    {
        $script = base_path('node/issueCertificate.js');
        $command = "node {$script} {$certHash} {$ipfsURI}";

        $output = shell_exec($command);

        // Filter out only the number (cert ID)
        preg_match('/\b\d+\b/', $output, $matches);

        if (!$matches || !is_numeric($matches[0])) {
            throw new \Exception('Invalid cert ID returned: ' . $output);
        }

        return (int) $matches[0];
    }
}