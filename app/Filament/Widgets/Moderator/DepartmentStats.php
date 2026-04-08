<?php

namespace App\Filament\Widgets\Moderator;

use App\Enums\IncidentStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Incident;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DepartmentStats extends StatsOverviewWidget
{
  protected ?string $heading = 'Estado Operativo de mis Departamentos';

  protected static ?int $sort = 1;

  public static function canView(): bool
  {
    return Auth::user()->hasRole(RoleEnum::MODERATOR->value);
  }

  protected function getStats(): array
  {
    $user = Auth::user();

    $moderatorIncidents = Incident::query()
      ->where(function ($query) use ($user) {
        $query->whereIn('department_id', $user->departments->pluck('id'))
          ->orWhereHas('moderators', fn ($q) => $q->where('user_id', $user->id));
      });

    return [
      Stat::make('Por Atender', (clone $moderatorIncidents)->where('status', IncidentStatus::NEW)->count())
        ->description('Incidencias nuevas en tus áreas')
        ->descriptionIcon(Heroicon::OutlinedBellAlert)
        ->color('danger'),
      Stat::make('Asignadas a Mí', (clone $moderatorIncidents)->where('status', '!=', IncidentStatus::CLOSED)->count())
        ->description('Incidencias bajo tu responsabilidad')
        ->descriptionIcon(Heroicon::OutlinedBriefcase)
        ->color('warning'),
      Stat::make(
        'Resueltas',
        (clone $moderatorIncidents)->whereIn('status', [IncidentStatus::RESOLVED, IncidentStatus::CLOSED])->count()
      )
        ->description('Total de incidencias resueltas')
        ->descriptionIcon(Heroicon::OutlinedCheckBadge)
        ->color('success'),
    ];
  }
}

