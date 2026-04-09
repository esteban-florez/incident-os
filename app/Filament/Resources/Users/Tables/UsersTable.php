<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('full_document')
          ->label('Cédula')
          ->searchable(['document_number'])
          ->sortable(['document_number']),
        TextColumn::make('name')
          ->label('Nombre')
          ->searchable(),
        TextColumn::make('email')
          ->label('Correo electrónico')
          ->searchable(),
        TextColumn::make('roles.name')
          ->label('Rol')
          ->badge()
          ->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->getLabel() ?? $state)
          ->color(fn (string $state): string => Role::tryFrom($state)?->getColor() ?? 'gray')
          ->icon(fn (string $state): string => Role::tryFrom($state)?->getIcon() ?? 'heroicon-m-user')
          ->searchable(),
        TextColumn::make('created_at')
          ->label('Fecha de registro')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->date('d/m/Y - g:i A'),
        TextColumn::make('updated_at')
          ->label('Última actualización')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->date('d/m/Y - g:i A'),
        TextColumn::make('deleted_at')
          ->label('Fecha de baja')
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

