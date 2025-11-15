<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'file_tambahan',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function namaLengkap(): Attribute
    {
        return Attribute::make(
            get: fn ()=> $this->user->name ?? $this->name ?? 'N/A',
        );
    }

    public function nomorInduk(): Attribute
    {
        return Attribute::make(
            get: fn ()=> $this->user->nim_nidn ?? $this->nim_nidn ?? 'N/A',
        );
    }
    protected function prodiAnggota(): Attribute
    {
        return Attribute::make(
            get: fn ()=> $this->user->prodi ?? $this->prodi ?? 'N/A',
        );
    }
    

}

