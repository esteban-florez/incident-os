<?php

namespace App\Filament\Resources\Incidents\Schemas;

use App\Enums\Role as RoleEnum;
use App\Models\Incident;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class IncidentInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Información General')
          ->schema([
            TextEntry::make('title')
              ->label('Título'),
            TextEntry::make('status')
              ->label('Status')
              ->badge(),
            TextEntry::make('priority')
              ->label('Prioridad')
              ->badge(),
            TextEntry::make('department.name')
              ->label('Departamento'),
            TextEntry::make('reporter.name')
              ->label('Reportado por')
              ->formatStateUsing(fn (Incident $record) =>
                user_label($record->reporter->name, $record->reporter->email)
              )
              ->hidden(fn () => Auth::user()->hasRole(RoleEnum::EMPLOYEE->value)),
            TextEntry::make('created_at')
              ->label('Fecha')
              ->dateTime('d/m/Y - g:i A'),
            TextEntry::make('updated_at')
              ->label('Última actualización')
              ->since()
              ->color('primary'),
            TextEntry::make('closed_at')
              ->label('Resuelta el')
              ->placeholder('No finalizada')
              ->dateTime('d/m/Y g:i A'),
          ])
          ->columns(['sm' => 1, 'md' => 2, 'lg' => 3])
          ->columnSpanFull(),
        Section::make('Descripción')
          ->schema([
            TextEntry::make('description')
              ->hiddenLabel()
              ->markdown()
              ->prose(),
          ])
          ->columnSpanFull(),
        Section::make('Evidencias Adjuntas')
          ->schema([
            ImageEntry::make('attachments')
              ->disk('public')
              ->label('')
              ->hiddenLabel()
              ->imageSize(300)
              ->square(),
            ])
          ->hidden(fn ($record) => empty($record->attachments))
          ->collapsible()
          ->collapsed()
          ->columnSpanFull(),
      ]);
  }
}

