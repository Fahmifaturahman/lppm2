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
        Schema::table('proposal_luaran', function (Blueprint $table) {
            // Tambahin setelah kolom 'deskripsi'
            $table->text('tujuan')->nullable()->after('deskripsi');
            $table->string('status')->default('belum_dimulai')->after('tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_luaran', function (Blueprint $table) {
            $table->dropColumn(['tujuan', 'status']);
        });
    }
};