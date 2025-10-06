<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserDataSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role tersedia
        Role::firstOrCreate(['name' => 'mahasiswa']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'dosen']);

        // ---------- MAHASISWA ----------
        $mahasiswa = Mahasiswa::create([
            'nim' => '23010001',
            'nama' => 'Fahmi Ramadhan',
            'prodi' => 'Teknik Informatika',
        ]);

        $userMahasiswa = User::create([
            'name' => $mahasiswa->nama,
            'email' => 'fahmi@mahasiswa.ac.id',
            'password' => Hash::make('password'),
            'nim_nidn' => $mahasiswa->nim,
        ]);
        $userMahasiswa->assignRole('mahasiswa');

        $mahasiswa->user_id = $userMahasiswa->id;
        $mahasiswa->save();

        // ---------- DOSEN ----------
        $dosen = Dosen::create([
            'nidn' => '1122334455',
            'nama' => 'Dr. Ari Setiawan',
            'prodi' => 'Teknik Informatika',
        ]);

        $userDosen = User::create([
            'name' => $dosen->nama,
            'email' => 'ari@dosen.ac.id',
            'password' => Hash::make('password'),
            'nim_nidn' => $dosen->nidn,
        ]);
        $userDosen->assignRole('dosen');

        $dosen->user_id = $userDosen->id;
        $dosen->save();

        // ---------- ADMIN (dosen juga) ----------
        $dosenAdmin = Dosen::create([
            'nidn' => '5566778899',
            'nama' => 'Prof. Rina Marlina',
            'prodi' => 'Teknik Informatika',
        ]);

        $userAdmin = User::create([
            'name' => $dosenAdmin->nama,
            'email' => 'rina@admin.ac.id',
            'password' => Hash::make('admin123'),
            'nim_nidn' => $dosenAdmin->nidn,
        ]);
        $userAdmin->assignRole(['admin', 'dosen']);

        $dosenAdmin->user_id = $userAdmin->id;
        $dosenAdmin->save();

        // ---------- ADMIN ------------
        $admin = User::create([
            'name' => 'Admin LPPM',
            'email' => 'admin@lppm.ac.id',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('admin');
    }
}
