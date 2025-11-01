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
    Schema::table('proposal_anggotas', function (Blueprint $table) {
        $table->string('file_tambahan')->nullable()->after('prodi');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_anggotas', function (Blueprint $table) {
            //
        });
    }
};
