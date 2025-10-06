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
        Schema::create('proposals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->string('judul');
        $table->text('ringkasan');
        $table->string('file')->nullable(); 
        $table->enum('kategori', ['Penelitian', 'Pengabdian']);
        $table->integer('tahun_pelaksanaan');
        $table->enum('status', ['menunggu','revisi', 'diterima', 'ditolak'])->default('menunggu');
        $table->text('catatan')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
