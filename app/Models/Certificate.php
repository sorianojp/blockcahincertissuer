<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'full_name', 'email', 'program', 'date_awarded',
        'cert_hash', 'metadata_uri', 'cert_id_on_chain', 'tx_hash', 'status'
    ];
}
