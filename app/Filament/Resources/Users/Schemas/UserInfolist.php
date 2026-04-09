<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('full_document')
          ->label('Documento de Identidad'),
        TextEntry::make('name')
          ->label('Nombre'),
        TextEntry::make('email')
          ->label('Correo electrónico'),
        TextEntry::make('roles.name')
          ->label('Rol')
          ->badge()
          ->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->getLabel() ?? $state)
          ->color(fn (string $state): string => Role::tryFrom($state)?->getColor() ?? 'gray')
          ->icon(fn (string $state): string => Role::tryFrom($state)?->getIcon() ?? 'heroicon-m-user'),
        TextEntry::make('created_at')
          ->label('Fecha de registro')
          ->date('d/m/Y - g:i A'),
        TextEntry::make('updated_at')
          ->label('Última actualización')
          ->date('d/m/Y - g:i A'),
        TextEntry::make('deleted_at')
          ->label('Fecha de baja')
          ->visible(fn (User $record): bool => $record->trashed())
          ->date('d/m/Y - g:i A'),
      ]);
  }
}

