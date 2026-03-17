<?php

namespace App\Filament\Resources\Incidents\RelationManagers;

use App\Enums\IncidentStatus;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Notifications\Moderator\IncidentAssigned;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ModeratorsRelationManager extends RelationManager
{
  protected static string $relationship = 'moderators';

  protected static ?string $inverseRelationship = 'assignedIncidents';

  protected static string|BackedEnum|null $icon = Heroicon::OutlinedWrenchScrewdriver;

  protected static ?string $recordTitleAttribute = 'name';

  protected static ?string $title = 'Moderadores';

  public function table(Table $table): Table
  {
    $hasUpdates = $this->getOwnerRecord()->updates()->exists();
    $canOverride = Auth::user()->hasRole(RoleEnum::SUPER_ADMIN);

    return $table
      ->extraAttributes([
        'x-data' => '{}',
        'x-init' => "
          if (new URLSearchParams(window.location.search).get('open_modal') === 'true') {
            setTimeout(() => {
              \$wire.mountTableAction('attach');
              const url = new URL(window.location);
              url.searchParams.delete('open_modal');
              window.history.replaceState({}, '', url);
            }, 300);
          }
        ",
      ])
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
        TextColumn::make('pivot.assigned_at')
          ->label('Asignado el')
          ->dateTime('d/m/Y h:i A')
          ->sortable(),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        AttachAction::make()
          ->label('Asignar Moderador')
          ->modalHeading('Asignar Moderador a la Incidencia')
          ->preloadRecordSelect()
          ->disabled($hasUpdates && !$canOverride)
          ->tooltip(fn() =>
            $hasUpdates ? 'No se pueden asignar más moderadores a una incidencia con actividad.' : null
          )
          ->recordSelectOptionsQuery(fn (Builder $query) =>
            $query->whereHas('roles', fn ($q) => $q->where('name', RoleEnum::MODERATOR))
          )
          ->after(function (AttachAction $action) {
            $incident = $this->getOwnerRecord();
            $formData = $action->getFormData();
            $attachedIds = $formData['recordId'] ?? [];

            $attachedIds = is_array($attachedIds) ? $attachedIds : [$attachedIds];

            if ($incident->status === IncidentStatus::NEW) {
              $incident->update(['status' => IncidentStatus::ASSIGNED]);
            }

            if (!empty($attachedIds)) {
              Notification::send(User::whereIn('id', $attachedIds)->get(), new IncidentAssigned($incident));
            }
          }),
      ])
      ->recordActions([
        ActionGroup::make([
          DetachAction::make()
            ->disabled($hasUpdates && !$canOverride),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DetachBulkAction::make()
            ->disabled($hasUpdates && !$canOverride),
        ]),
      ]);
  }
}

