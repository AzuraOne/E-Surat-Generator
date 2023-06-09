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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id('id_vendor');
            $table->string('penyedia');
            $table->string('direktur');
            $table->string('alamat_jalan');
            $table->string('alamat_kota');
            $table->string('alamat_provinsi');
            $table->string('bank');
            $table->string('nomor_rek');
            $table->string('telepon')->nullable();
            $table->string('website')->nullable();
            $table->string('faksimili')->nullable();
            $table->string('email_perusahaan')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
