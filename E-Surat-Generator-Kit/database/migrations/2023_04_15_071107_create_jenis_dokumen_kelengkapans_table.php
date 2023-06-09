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
        Schema::create('jenis_dokumen_kelengkapans', function (Blueprint $table) {
            $table->bigIncrements('id_jenis');
            $table->string('nama_dokumen');
            $table->string('no_dokumen')->unique();
            $table->enum('dokumen_sistem', ['ya', 'tidak'])->default('tidak');
            $table->text('keterangan')->nullable();
  
            $table->timestamps();
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_dokumen_kelengkapans');
    }
};
