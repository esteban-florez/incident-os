<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DepartmentsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Nombre')
          ->searchable(),
        TextColumn::make('moderators_count')
          ->counts('moderators')
          ->label('Moderadores')
          ->badge()
          ->default(0),
        TextColumn::make('created_at')
          ->label('Fecha de creación')
          ->sortable()
          ->date('d/m/Y - g:i A'),
        TextColumn::make('updated_at')
          ->label('Última actualización')
          ->sortable()
          ->date('d/m/Y - g:i A'),
        TextColumn::make('deleted_at')
          ->label('Fecha de eliminación')
          ->sortable()
          ->placeholder('Activo')
          ->toggleable(isToggledHiddenByDefault: true)
          ->date('d/m/Y - g:i A'),
      ])
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          Action::make('quick_assign')
            ->label('Asignar Moderador')
            ->color('success')
            ->icon(Heroicon::OutlinedUserPlus)
            ->url(fn ($record): string => DepartmentResource::getUrl('edit', [
              'record' => $record,
              'relation' => 'moderators',
              'open_modal' => 'true',
            ]))
            ->visible(fn () => Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)),
          EditAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}

