<?php

namespace App\Filament\Resources\Incidents\RelationManagers;

use App\Enums\IncidentStatus;
use App\Notifications\Employee\IncidentUpdated;
use App\Notifications\IncidentClosed;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class UpdatesRelationManager extends RelationManager
{
  protected static string $relationship = 'updates';

  protected static string|BackedEnum|null $icon = Heroicon::OutlinedArrowPath;

  protected static ?string $title = 'Seguimiento de la Incidencia';

  public static function isLazy(): bool
  {
    return false;
  }

  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('new_status')
          ->label('Actualizar estado a:')
          ->required()
          ->native(false)
          ->options(fn (RelationManager $livewire) => match ($livewire->getOwnerRecord()->status) {
            IncidentStatus::NEW, IncidentStatus::ASSIGNED => [
              IncidentStatus::IN_PROGRESS->value => IncidentStatus::IN_PROGRESS->getLabel(),
              IncidentStatus::RESOLVED->value => IncidentStatus::RESOLVED->getLabel(),
            ],

            IncidentStatus::IN_PROGRESS => [
              IncidentStatus::IN_PROGRESS->value => IncidentStatus::IN_PROGRESS->getLabel(),
              IncidentStatus::RESOLVED->value => IncidentStatus::RESOLVED->getLabel(),
            ],

            IncidentStatus::RESOLVED => [
              IncidentStatus::RESOLVED->value => IncidentStatus::RESOLVED->getLabel(),
              IncidentStatus::CLOSED->value => IncidentStatus::CLOSED->getLabel(),
            ],

            IncidentStatus::CLOSED => [
              IncidentStatus::CLOSED->value => IncidentStatus::CLOSED->getLabel(),
            ],

            default => [],
          }),
        RichEditor::make('comment')
          ->label('Comentario / Justificación')
          ->required()
          ->columnSpanFull()
          ->toolbarButtons(['bold', 'italic', 'bulletList']),
        FileUpload::make('attachments')
          ->label('Evidencias (Fotos/Capturas)')
          ->disk('public')
          ->multiple()
          ->reorderable()
          ->image()
          ->imageEditor()
          ->directory('incident-updates-attachments')
          ->columnSpanFull()
          ->maxFiles(5),
      ]);
  }

  public function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Detalle del Avance')
          ->icon(Heroicon::OutlinedInformationCircle)
          ->schema([
            TextEntry::make('user.name')
              ->label('Registrado por')
              ->weight('bold')
              ->formatStateUsing(fn (Model $record) =>
                user_label($record->user->name, $record->user->email)
              ),
            TextEntry::make('created_at')
              ->label('Fecha')
              ->dateTime('d/m/Y h:i A'),
            TextEntry::make('status_change')
              ->label('Cambio de Estado')
              ->state(fn (Model $record) =>
                "{$record->previous_status->getLabel()} → {$record->new_status->getLabel()}"
              )
              ->badge()
              ->color('info'),
          ])
          ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
          ->columnSpanFull(),
        Section::make('Comentario')
          ->schema([
            TextEntry::make('comment')
              ->hiddenLabel()
              ->markdown()
              ->prose(),
          ])
          ->columnSpanFull(),
        Section::make('Evidencias Adjuntas')
          ->schema([
            ImageEntry::make('attachments')
              ->label('')
              ->disk('public')
              ->hiddenLabel()
              ->imageSize(300)
              ->square(),
            ])
          ->hidden(fn (Model $record) => empty($record->attachments))
          ->collapsible()
          ->collapsed()
          ->columnSpanFull(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('user.name')
          ->label('Registrado por')
          ->weight('bold')
          ->description(fn (Model $record): string => $record->user->email),
        TextColumn::make('created_at')
          ->label('Fecha')
          ->dateTime('d/m/Y h:i A')
          ->sortable(),
        TextColumn::make('status_change')
          ->label('Cambio de Estado')
          ->state(fn (Model $record) =>
            "{$record->previous_status->getLabel()} → {$record->new_status->getLabel()}"
          )
          ->badge()
          ->color('info'),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        //
      ])
      ->headerActions([
        CreateAction::make()
          ->label('Añadir Avance')
          ->modalHeading('Registrar Seguimiento Técnico')
          ->hidden(fn (RelationManager $livewire) =>
            $livewire->getOwnerRecord()->status === IncidentStatus::CLOSED
          )
          ->mutateDataUsing(function (array $data): array {
            $data['user_id'] = Auth::id();
            $data['previous_status'] = $this->getOwnerRecord()->status;
            return $data;
          })
          ->after(function (Model $record) {
            $incident = $record->incident;
            $currentUserId = Auth::id();

            $incident->update(['status' => $record->new_status]);

            if ($record->new_status === IncidentStatus::CLOSED) {
              $incident->reporter->notify(new IncidentClosed($incident, isForTeam: false));

              $otherModerators = $incident->moderators->where('id', '!=', $currentUserId);
              if ($otherModerators->isNotEmpty()) {
                Notification::send($otherModerators, new IncidentClosed($incident, isForTeam: true));
              }
            } else if ($currentUserId !== $incident->user_id) {
              $incident->reporter->notify(new IncidentUpdated($record));
            }
          }),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('Detalle del Avance Técnico'),
        ])
      ]);
  }
}

