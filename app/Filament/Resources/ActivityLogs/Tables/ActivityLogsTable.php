<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('created_at')
          ->label('Fecha')
          ->sortable()
          ->date('d/m/Y - g:i A'),
        TextColumn::make('log_name')
          ->label('Módulo')
          ->badge()
          ->searchable(),
        TextColumn::make('event')
          ->label('Evento')
          ->badge()
          ->formatStateUsing(fn(string $state): string => translate_activity_event($state))
          ->color(fn(string $state): string => get_activity_color($state))
          ->searchable(),
        TextColumn::make('description')
          ->label('Descripción'),
        TextColumn::make('causer.name')
          ->label('Causado por')
          ->default('Sistema')
          ->formatStateUsing(function (ActivityLog $record) {
            if (!$record->causer) return 'Sistema';
            return user_label($record->causer->name, $record->causer->email);
          }),
      ])
      ->filters([
        //
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
        ]),
      ]);
  }
}

