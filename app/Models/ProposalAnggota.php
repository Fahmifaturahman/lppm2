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
        /**
     * The "booted" method of the model.
     * Ini akan berjalan otomatis setiap kali model dioperasikan.
     */
    protected static function booted(): void
    {
        static::creating(function (ProposalAnggota $anggota) {
            
            if ($anggota->user_id && is_null($anggota->nama)) {
                $user = User::find($anggota->user_id);
                
                if ($user) {
                    $anggota->nama = $user->name;
                    $anggota->nim_nidn = $user->nim_nidn;
                }
            }
        });
    }
}

