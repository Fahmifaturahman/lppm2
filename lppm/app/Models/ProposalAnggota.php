<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Proposal;

class ProposalAnggota extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'tipe',
        'peran',
        'nim_nidn',
        'nama',
        'prodi',
        'bidang_fokus',
        'rumpun_ilmu_lv2',
        'uraian_tugas',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

