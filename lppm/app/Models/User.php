<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\ProposalAnggota;


    /**
     * @method bool hasRole(string $role)
     * @method bool hasAnyRole(array|string $roles)
     * @method bool can(string $permission)
     * @method bool canAny(array $permissions)
     */
class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nim_nidn',
        'prodi',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
        public function anggota()
    {
        return $this->hasMany(ProposalAnggota::class);
    }

    protected $guard_name = 'web';

    public function mahasiswa()
    {
        return $this->hasOne(Mahasiswa::class,); 
    }

    public function dosen()
    {
        return $this->hasOne(Dosen::class,); 
    }
    public function getRoleAttribute()
    {
        if ($this->hasRole('mahasiswa')) {
            return 'mahasiswa';
        } elseif ($this->hasRole('dosen')) {
            return 'dosen';
        } else {
            return 'admin';
        }
    }
}
