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
    Schema::table('proposals', function (Blueprint $table) {
        // Kita taruh setelah tahun_pelaksanaan biar rapi
        $table->string('semester')->nullable()->after('tahun_pelaksanaan');
    });
}

public function down(): void
{
    Schema::table('proposals', function (Blueprint $table) {
        $table->dropColumn('semester');
    });
}
};
