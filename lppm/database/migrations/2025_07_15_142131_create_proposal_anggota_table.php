<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_anggotas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');

            // Optional, jika user terdaftar di sistem
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->string('tipe')->comment('dosen atau mahasiswa');
            $table->string('peran')->comment('ketua atau anggota');

            $table->string('nim_nidn')->nullable();
            $table->string('nama');
            $table->string('prodi')->nullable();
            $table->string('bidang_fokus')->nullable();         // untuk Penelitian
            $table->string('rumpun_ilmu_lv2')->nullable();       // untuk Pengabdian Dosen
            $table->text('uraian_tugas')->nullable();            // untuk semua

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_anggota');
    }
};
