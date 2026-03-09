<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\Permission as PermissionEnum;
use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LatestActivityLogs extends TableWidget
{
  protected static ?string $heading = 'Actividad reciente';

  protected int|string|array $columnSpan = 'full';

  protected static ?int $sort = 5;

  public static function canView(): bool
  {
    return Auth::user()->hasPermissionTo(PermissionEnum::VIEW_ANY_ACTIVITY_LOG->value);
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(fn (): Builder =>
        ActivityLog::query()
          ->with(['causer'])
          ->latest()
          ->limit(5)
      )
      ->columns([
        TextColumn::make('created_at')
          ->label('Fecha')
          ->date('d/m/Y - g:i A')
          ->timezone('America/Caracas')
          ->sortable(),
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
      ->paginated(false)
      ->filters([
        //
      ])
      ->headerActions([
        //
      ])
      ->recordActions([
        //
      ]);
  }
}

