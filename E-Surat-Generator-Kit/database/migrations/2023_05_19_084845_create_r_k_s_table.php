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
        Schema::create('r_k_s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kontrakkerja');
            $table->json('datarks')->nullable();
            $table->string('tandatangan_pengadaan')->nullable();
            $table->dateTime('tanggal_tandatangan_pengadaan')->nullable();
            $table->string('tandatangan_manager')->nullable();
            $table->dateTime('tanggal_tandatangan_manager')->nullable();
            $table->timestamps();
            $table->foreign('id_kontrakkerja')->references('id_kontrakkerja')->on('kontrak_kerjas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('r_k_s');
    }
};
