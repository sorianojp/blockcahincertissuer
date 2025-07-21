<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('program');
            $table->date('date_awarded');
            $table->string('cert_hash')->nullable();       // Blockchain hash
            $table->string('metadata_uri')->nullable();    // IPFS/Storage link
            $table->unsignedBigInteger('cert_id_on_chain')->nullable();
            $table->string('tx_hash')->nullable();
            $table->enum('status', ['pending', 'issued', 'revoked'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
