<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalLuaran extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika tidak jamak
    protected $table = 'proposal_luaran';

    protected $fillable = [
        'proposal_id',
        'jenis_luaran',
        'deskripsi',
        'file',
        'tujuan',
        'status',
        'verifikasi_status',
        'verifikasi_catatan',
    ];

    /**
     * Relasi ke Proposal
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}