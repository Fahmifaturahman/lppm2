<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ProposalAnggota;

/**
 * @property int $id
 * @property string $judul
 * @property string $ringkasan
 * @property string $status
 * @property string|null $catatan
 * @property int $user_id
 * @property-read \App\Models\User $user
 * @mixin \Eloquent
 */

class Proposal extends Model
{
    protected $fillable = [
    'judul', 'ringkasan', 'status', 'catatan', 'user_id','file', 'kategori', 'tahun_pelaksanaan'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anggota()
    {
        return $this->hasMany(ProposalAnggota::class);
    }

}
