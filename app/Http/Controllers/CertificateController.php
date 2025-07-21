<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Certificate;
use App\Services\MetadataService;
use App\Services\BlockchainService;

class CertificateController extends Controller
{
    /**
     * Show paginated list of certificates.
     */
    public function index()
    {
        $certificates = Certificate::latest()->paginate(10);
        return view('dashboard', compact('certificates'));
    }

    /**
     * Show the CSV upload form.
     */
    public function uploadForm()
    {
        return view('certificates.upload');
    }

    /**
     * Handle CSV upload and create pending certificates in the DB.
     */
    public function uploadCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('strtolower', array_shift($rows));

        foreach ($rows as $row) {
            $data = array_combine($header, $row);

            Certificate::create([
                'full_name'    => $data['full_name'],
                'email'        => $data['email'],
                'program'      => $data['program'],
                'date_awarded' => date('Y-m-d', strtotime($data['date_awarded'])),
                'status'       => 'pending',
            ]);
        }

        return redirect()->route('dashboard')
                         ->with('status', 'Certificates uploaded successfully.');
    }

    /**
     * Issue a single certificate on-chain:
     * 1. Build JSON & save locally
     * 2. Hash and upload to IPFS
     * 3. Call smart contract and get certId + txHash
     * 4. Save cert_hash, metadata_uri, cert_id_on_chain, tx_hash in DB
     */
    public function issue($id)
    {
        $certificate = Certificate::findOrFail($id);

        // Prepare data for metadata
        $serial = 'UDD-' . date('Y') . '-' . str_pad($certificate->id, 5, '0', STR_PAD_LEFT);
        $certData = [
            'full_name'     => $certificate->full_name,
            'email'         => $certificate->email,
            'student_id'    => $certificate->id,
            'type'          => 'Diploma',
            'program'       => $certificate->program,
            'date_awarded'  => $certificate->date_awarded,
            'serial_number' => $serial,
        ];

        try {
            // 1. Create JSON file
            $result   = MetadataService::createJson($certData);
            // 2. Compute hash
            $certHash = MetadataService::getCertHash($result['json']);
            // 3. Upload JSON to IPFS
            $ipfsURI  = MetadataService::uploadToIPFS($result['file_path']);
            // 4. Issue on-chain and capture IDs
            ['certId' => $certId, 'txHash' => $txHash] = BlockchainService::issueCertificate($certHash, $ipfsURI);

            // 5. Persist on-chain references
            $certificate->update([
                'cert_hash'        => $certHash,
                'metadata_uri'     => $ipfsURI,
                'cert_id_on_chain' => $certId,
                'tx_hash'          => $txHash,
                'status'           => 'issued',
            ]);

            return redirect()->route('dashboard')
                             ->with('status', '✅ Certificate issued on blockchain!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                             ->with('error', '❌ Error issuing certificate: ' . $e->getMessage());
        }
    }

    /**
     * Publicly verify a certificate:
     * 1. Fetch metadata JSON from IPFS
     * 2. Recompute hash
     * 3. Compare to stored cert_hash and check revoked status
     */
    public function verify($id)
    {
        $certificate = Certificate::findOrFail($id);

        try {
            $ipfsUrl  = str_replace('ipfs://', 'https://ipfs.io/ipfs/', $certificate->metadata_uri);
            $response = Http::timeout(10)->get($ipfsUrl);

            if (! $response->ok()) {
                throw new \Exception("Gateway error ({$response->status()}): {$response->body()}");
            }

            $json = $response->json();

            // Recompute the same SHA-256 hash
            $raw = $json['recipient']['student_id_hash']
                 . $json['credential']['serial_number']
                 . $json['credential']['date_awarded'];

            $recomputedHash = '0x' . hash('sha256', $raw);

            // Determine status
            if ($certificate->cert_hash === $recomputedHash) {
                $status = $certificate->status === 'revoked' ? 'revoked' : 'valid';
            } else {
                $status = 'tampered';
            }

            return view('certificates.verify', compact('certificate', 'json', 'status'));
        } catch (\Exception $e) {
            return view('certificates.verify', [
                'certificate' => $certificate,
                'status'      => 'error',
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
