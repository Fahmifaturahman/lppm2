<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProposalDownloadController extends Controller
{
    // GANTI type hint di sini
    public function download(Proposal $proposal): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isOwner = $proposal->user_id === $user->id;
        $isMember = $proposal->anggota()->where('user_id', $user->id)->exists();

        if (!$isOwner && !$isMember) {
            abort(403, 'Anda tidak punya hak akses.');
        }

        // Cek apakah path file-nya ada di database
        if (empty($proposal->file)) {
            abort(404, 'Data file tidak ditemukan.');
        }

        // Cek apakah file fisik-nya ada di storage
        if (!Storage::disk('public')->exists($proposal->file)) {
            abort(404, 'File fisik tidak ditemukan di server.');
        }
        
        $path = Storage::disk('public')->path($proposal->file);
        
        // Fungsi ini mengembalikan BinaryFileResponse, jadi type hint-nya harus cocok
        return response()->download($path);
    }
}