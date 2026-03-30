<?php

namespace App\Filament\Resources\Incidents\Schemas;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Enums\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncidentForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make()
          ->schema([
            Section::make('Detalles del Reporte')
              ->schema([
                TextInput::make('title')
                  ->label('Título de la Incidencia')
                  ->placeholder('Ej: El monitor no enciende')
                  ->required()
                  ->minLength(10)
                  ->maxLength(40)
                  ->columnSpanFull(),
                RichEditor::make('description')
                  ->label('Descripción Detallada')
                  ->required()
                  ->columnSpanFull()
                  ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                FileUpload::make('attachments')
                  ->disk('public')
                  ->label('Evidencias (Fotos/Capturas)')
                  ->multiple()
                  ->reorderable()
                  ->image()
                  ->imageEditor()
                  ->directory('incident-attachments')
                  ->visibility('public')
                  ->columnSpanFull()
                  ->maxFiles(5),
            ]),
            //->disabled(fn (string $operation) => $operation === 'edit'),
          ])
          ->columnSpan(['lg' => 2]),
        Group::make()
          ->schema([
            Section::make('Clasificación')
              ->schema([
                Select::make('department_id')
                  ->relationship('department', 'name')
                  ->label('Departamento')
                  ->searchable()
                  ->preload()
                  ->required(),
                Select::make('priority')
                  ->options(IncidentPriority::class)
                  ->label('Prioridad')
                  ->required()
                  ->default(IncidentPriority::LOW)
                  ->native(false),
              ]),
          ])
          ->columnSpan(['lg' => 1]),
          //->disabled(fn (string $operation) => $operation === 'edit'),
        ])
        ->columns(3);
  }
}

