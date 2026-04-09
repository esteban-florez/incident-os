<?php

namespace App\Filament\Widgets\Moderator;

use App\Enums\IncidentStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Incident;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClosedIncidents extends TableWidget
{
  protected static ?string $heading = 'Mis Incidencias Resueltas';

  protected int|string|array $columnSpan = 'full';

  protected static ?int $sort = 2;

  public static function canView(): bool
  {
    return Auth::user()->hasRole(RoleEnum::MODERATOR->value);
  }

  private function getModeratorQuery(): Builder
  {
    $user = Auth::user();

    return Incident::query()
      ->whereIn('status', [IncidentStatus::RESOLVED, IncidentStatus::CLOSED])
      ->where(function (Builder $query) use ($user) {
        $query->whereIn('department_id', $user->departments->pluck('id'))
          ->orWhereHas('moderators', fn ($q) => $q->where('user_id', $user->id));
      })
      ->latest()
      ->limit(5);
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(fn (): Builder => $this->getModeratorQuery())
      ->columns([
        TextColumn::make('title')
          ->label('Título')
          ->searchable(),
        TextColumn::make('status')
          ->badge()
          ->sortable(),
        TextColumn::make('department.name')
          ->label('Departamento')
          ->sortable()
          ->toggleable(),
        TextColumn::make('created_at')
          ->label('Fecha')
          ->date('d/m/Y - g:i A')
          ->sortable(),
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

