<?php

namespace App\Filament\Admin\Resources\ProposalResource\Pages;

use App\Filament\Admin\Resources\ProposalResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProposal extends ViewRecord
{
    protected static string $resource = ProposalResource::class;

    public function getHeaderActions(): array
    {
        return [];
    }
}
