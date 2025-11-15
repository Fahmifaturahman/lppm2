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
        $table->string('verifikasi_status')->nullable()->after('status');
        $table->text('verifikasi_catatan')->nullable()->after('verifikasi_status');
    });
}

public function down(): void
{
    Schema::table('proposal_luaran', function (Blueprint $table) {
        $table->dropColumn(['verifikasi_status', 'verifikasi_catatan']);
    });
}
};
