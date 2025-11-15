<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Jangan lupa tambahin ini

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {

            DB::statement('ALTER TABLE proposals DROP CONSTRAINT proposals_kategori_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_kategori_check CHECK (((kategori)::text = ANY (ARRAY[('Penelitian'::character varying)::text, ('Pengabdian'::character varying)::text])))");
        });
    }
};