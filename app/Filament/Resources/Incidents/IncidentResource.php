<?php

namespace App\Filament\Resources\Incidents;

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Incidents\RelationManagers\ModeratorsRelationManager;
use App\Filament\Resources\Incidents\RelationManagers\UpdatesRelationManager;
use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Resources\Incidents\Pages\ViewIncident;
use App\Filament\Resources\Incidents\Schemas\IncidentForm;
use App\Filament\Resources\Incidents\Schemas\IncidentInfolist;
use App\Filament\Resources\Incidents\Tables\IncidentsTable;
use App\Models\Incident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class IncidentResource extends Resource
{
  protected static ?string $model = Incident::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

  protected static ?string $recordTitleAttribute = 'title';

  protected static ?string $modelLabel = 'incidencia';

  public static function getEloquentQuery(): Builder
  {
    $query = parent::getEloquentQuery();
    $user = Auth::user();

    if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) return $query;

    if ($user->hasRole(RoleEnum::MODERATOR->value)) {
      return $query->where(function (Builder $subQuery) use ($user) {
        $subQuery->whereIn('department_id', $user->departments->pluck('id'))
          ->orWhereHas('moderators', fn ($q) => $q->where('user_id', $user->id));
      });
    }

    return $query->where('user_id', $user->id);
  }

  public static function form(Schema $schema): Schema
  {
    return IncidentForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return IncidentInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return IncidentsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      'moderators' => ModeratorsRelationManager::make(['lazy' => false]),
      'updates' => UpdatesRelationManager::class::make(['lazy' => false]),
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListIncidents::route('/'),
      'create' => CreateIncident::route('/create'),
      'view' => ViewIncident::route('/{record}'),
      'edit' => EditIncident::route('/{record}/edit'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }
}

