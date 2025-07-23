<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certificate;
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
        $request->validate(['csv_file'=>'required|mimes:csv,txt']);
        $rows = array_slice(array_map('str_getcsv', file($request->file('csv_file')->getRealPath())), 1);
        $header = array_map('strtolower', array_map('trim', str_getcsv(file($request->file('csv_file')->getRealPath())[0])));
        foreach ($rows as $row) {
            $r = array_combine($header, $row);
            Certificate::create([
                'full_name'   => $r['full_name'],
                'email'       => $r['email'],
                'program'     => $r['program'],
                'date_awarded'=> date('Y-m-d', strtotime($r['date_awarded'])),
                'status'      => 'pending',
            ]);
        }
        return redirect()->route('dashboard')->with('status','Uploaded CSV');
    }

    public function issue($id)
    {
        $cert = Certificate::findOrFail($id);
        $data = [
            'full_name'   => $cert->full_name,
            'email'       => $cert->email,
            'student_id'  => $cert->id,
            'type'        => 'Diploma',
            'program'     => $cert->program,
            'date_awarded'=> $cert->date_awarded,
            'serial_number'=> 'UDD-'.date('Y').'-'.str_pad($cert->id,5,'0',STR_PAD_LEFT),
        ];

        try {
            $meta    = MetadataService::createJson($data);
            $certHash= MetadataService::getCertHash($meta['json']);
            $uri     = MetadataService::uploadToIPFS($meta['file_path']);
            $res     = BlockchainService::issueCertificate($certHash, $uri);

            $cert->update([
                'cert_hash'        => $certHash,
                'metadata_uri'     => $uri,
                'cert_id_on_chain' => $res['certId'],
                'tx_hash'          => $res['txHash'],
                'status'           => 'issued',
            ]);

            return redirect()->route('dashboard')->with('status','✅ Issued!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error','❌ '.$e->getMessage());
        }
    }

    public function verify($id)
    {
        $cert = Certificate::findOrFail($id);
        try {
            $url  = str_replace('ipfs://','https://ipfs.io/ipfs/',$cert->metadata_uri);
            $resp = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
            $json = $resp->json();
            $raw  = $json['recipient']['student_id_hash'].$json['credential']['serial_number'].$json['credential']['date_awarded'];
            $recomputed = '0x'.hash('sha256',$raw);

            $status = ($cert->cert_hash === $recomputed)
                        ? ($cert->status==='revoked'?'revoked':'valid')
                        : 'tampered';

            return view('certificates.verify', compact('cert','json','status'));
        } catch (\Exception $e) {
            return view('certificates.verify',['cert'=>$cert,'status'=>'error','error'=>$e->getMessage()]);
        }
    }
}
