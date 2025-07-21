<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use App\Services\MetadataService;
use App\Services\BlockchainService;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::latest()->paginate(10);
        return view('dashboard', compact('certificates'));
    }

    public function uploadForm()
    {
        return view('certificates.upload');
    }

    public function uploadCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $header = array_map('strtolower', $data[0]);
        unset($data[0]); // Remove header row

        foreach ($data as $row) {
            $row = array_combine($header, $row);

            Certificate::create([
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'program' => $row['program'],
                'date_awarded' => date('Y-m-d', strtotime($row['date_awarded'])),
                'status' => 'pending',
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Certificates uploaded successfully.');
    }

    public function issue($id)
    {
        $certificate = Certificate::findOrFail($id);

        $certData = [
            'full_name' => $certificate->full_name,
            'email' => $certificate->email,
            'student_id' => $certificate->id,
            'type' => 'Diploma',
            'program' => $certificate->program,
            'date_awarded' => $certificate->date_awarded,
            'serial_number' => 'UDD-' . date('Y') . '-' . str_pad($certificate->id, 5, '0', STR_PAD_LEFT)
        ];

        try {
            // 1. Create metadata JSON
            $result = MetadataService::createJson($certData);

            // 2. Generate hash
            $certHash = MetadataService::getCertHash($result['json']);

            // 3. Upload to IPFS
            $ipfsURI = MetadataService::uploadToIPFS($result['file_path']);

            // 4. Call smart contract to issue cert and get certId
            $certId = BlockchainService::issueCertificate($certHash, $ipfsURI);

            // 5. Save to DB
            $certificate->update([
                'cert_hash' => $certHash,
                'metadata_uri' => $ipfsURI,
                'cert_id_on_chain' => $certId,
                'status' => 'issued',
            ]);

            return redirect()->route('dashboard')->with('status', '✅ Certificate issued on blockchain!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', '❌ Error issuing certificate: ' . $e->getMessage());
        }
    }

    public function verify($id)
    {
        $certificate = Certificate::findOrFail($id);

        try {
            $json = json_decode(file_get_contents(
                str_replace('ipfs://', 'https://ipfs.io/ipfs/', $certificate->metadata_uri)
            ), true);

            $raw = $json['recipient']['student_id_hash'] .
                   $json['credential']['serial_number'] .
                   $json['credential']['date_awarded'];

            $recomputedHash = '0x' . hash('sha256', $raw);

            if ($certificate->cert_hash === $recomputedHash) {
                $status = $certificate->status === 'revoked' ? 'revoked' : 'valid';
            } else {
                $status = 'tampered';
            }

            return view('certificates.verify', compact('certificate', 'json', 'status'));
        } catch (\Exception $e) {
            return view('certificates.verify', [
                'certificate' => $certificate,
                'status' => 'error',
                'error' => $e->getMessage()
            ]);
        }
    }
}