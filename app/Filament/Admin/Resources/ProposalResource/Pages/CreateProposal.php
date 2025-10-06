<?php

namespace App\Filament\Admin\Resources\ProposalResource\Pages;

use App\Filament\Admin\Resources\ProposalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

protected function mutateFormDataBeforeCreate(array $data): array
{
    if (is_array($data['file'])) {
        $data['file'] = collect($data['file'])->first(); // Ambil value pertama (file path)
    }

    $data['user_id'] = Auth::id();

    return $data;
}

}
