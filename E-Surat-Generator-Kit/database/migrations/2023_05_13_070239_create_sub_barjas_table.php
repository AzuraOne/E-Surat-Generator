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
        Schema::create('sub_barjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_barjas')->constrained('bar_jas')->onDelete('cascade');;
            $table->string('uraian');
            $table->float('volume', 10, 2);
            $table->string('satuan');
            // $table->float('harga_satuan', 10, 2);
            // $table->float('jumlah', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_barjas');
    }
};
