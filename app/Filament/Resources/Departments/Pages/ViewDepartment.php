<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewDepartment extends ViewRecord
{
  protected static string $resource = DepartmentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('quick_assign')
        ->label('Asignar Moderador')
        ->color('success')
        ->icon(Heroicon::OutlinedUserPlus)
        ->url(fn (): string => DepartmentResource::getUrl('edit', [
          'record' => $this->record,
          'relation' => 'moderators',
          'open_modal' => 'true',
        ]))
        ->visible(fn () => Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)),
      EditAction::make(),
    ];
  }
}

