<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User;
use App\Models\ProposalAnggota;
use App\Models\ProposalLuaran;

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
    'judul', 
    'ringkasan', 
    'status', 
    'catatan', 
    'user_id',
    'file', 
    'kategori', 
    'tahun_pelaksanaan', 
    'semester',
    'prodi'
    ];

    protected $with = ['user'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(ProposalAnggota::class);
    }

    public function anggotaDosen()
    {
        return $this->anggota()->where('tipe', 'dosen')->with('user');
    }

    public function anggotaMahasiswa()
    {
        return $this->anggota()->where('tipe', 'mahasiswa')->with('user');
    }

    public function isComplete():Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (empty($this->file)) {
                    return false;
                }
                $inCompleteDosen = $this->anggota()
                    ->where('tipe', 'dosen')
                    ->whereNull('file_tambahan')
                    ->exists();
                
                if ($inCompleteDosen) {
                    return false;
                }
                return true;
            },
        );
    }
    public function luaran(): HasMany
    {
        return $this->hasMany(ProposalLuaran::class);
    }

}
