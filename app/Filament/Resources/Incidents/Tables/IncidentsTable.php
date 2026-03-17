<?php

namespace App\Filament\Resources\Incidents\Tables;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IncidentsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('title')
          ->label('Título')
          ->searchable(),
        TextColumn::make('status')
          ->badge()
          ->sortable(),
        TextColumn::make('priority')
          ->label('Prioridad')
          ->badge()
          ->sortable(),
        TextColumn::make('department.name')
          ->label('Departamento')
          ->sortable()
          ->toggleable(),
        TextColumn::make('reporter.name')
          ->label('Reportado por')
          ->toggleable()
          ->formatStateUsing(fn (Incident $record) =>
            user_label($record->reporter->name, $record->reporter->email)
          )
          ->hidden(fn () => Auth::user()->hasRole(RoleEnum::EMPLOYEE->value)),
        TextColumn::make('created_at')
          ->label('Fecha')
          ->date('d/m/Y - g:i A')
          ->sortable(),
        TextColumn::make('updated_at')
          ->label('Última actualización')
          ->since()
          ->toggleable(isToggledHiddenByDefault: true)
          ->color('primary'),
        TextColumn::make('closed_at')
          ->label('Resuelta el')
          ->placeholder('No finalizada')
          ->toggleable(isToggledHiddenByDefault: true)
          ->dateTime('d/m/Y g:i A')
          ->sortable(),
      ])
      ->filters([
        SelectFilter::make('status')
          ->options(IncidentStatus::class)
          ->label('Estado'),
        SelectFilter::make('priority')
          ->options(IncidentPriority::class)
          ->label('Prioridad'),
        TrashedFilter::make(),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          Action::make('quick_assign')
            ->label('Asignar Moderador')
            ->color('success')
            ->icon(Heroicon::OutlinedUserPlus)
            ->url(fn ($record): string => IncidentResource::getUrl('edit', [
              'record' => $record,
              'relation' => 'moderators',
              'open_modal' => 'true',
            ]))
            ->visible(fn () => Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)),
          EditAction::make(),
          Action::make('download_pdf')
            ->label('Descargar PDF')
            ->color('danger')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->action(fn (Incident $record) =>
              ReportService::download(
                ['incident' => $record],
                'reports.incident',
                "incidencia-{$record->id}"
              )
            ),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}

