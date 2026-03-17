<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewIncident extends ViewRecord
{
  protected static string $resource = IncidentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('download_pdf')
        ->label('Descargar PDF')
        ->color('danger')
        ->icon(Heroicon::OutlinedDocumentArrowDown)
        ->action(fn () =>
          ReportService::download(
            ['incident' => $this->record],
            'reports.incident',
            "incidencia-{$this->record->id}"
          )
        ),
      Action::make('quick_assign')
        ->label('Asignar Moderador')
        ->color('success')
        ->icon(Heroicon::OutlinedUserPlus)
        ->url(fn (): string => IncidentResource::getUrl('edit', [
          'record' => $this->record,
          'relation' => 'moderators',
          'open_modal' => 'true',
        ]))
        ->visible(fn () => Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)),
      EditAction::make(),
    ];
  }
}

