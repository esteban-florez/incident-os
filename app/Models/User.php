<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DocumentType;
use App\Traits\HasActivityLog;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasActivityLog, HasFactory, HasRoles, HasUuids, LogsActivity, Notifiable, SoftDeletes;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'document_type',
    'document_number',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The accessors to append to the model's array form.
   *
   * @var list<string>
   */
  protected $appends = [
    'full_document',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'document_type' => DocumentType::class,
    ];
  }

  protected function fullDocument(): Attribute
  {
    return Attribute::make(
      get: function (mixed $value, array $attributes) {
        if (!$attributes['document_type'] || !$attributes['document_number']) return null;
        $type = DocumentType::tryFrom($attributes['document_type']);
        return "{$type?->getLabel()}-{$attributes['document_number']}";
      },
    );
  }

  public function departments(): BelongsToMany
  {
    return $this->belongsToMany(Department::class, 'department_user');
  }

  // Incidents assigned to me (as a moderator)
  public function assignedIncidents(): BelongsToMany
  {
    return $this->belongsToMany(Incident::class, 'incident_user');
  }

  // Incidents created by me (as an employee)
  public function reportedIncidents(): HasMany
  {
    return $this->hasMany(Incident::class, 'user_id');
  }

  public function incidentUpdates(): HasMany
  {
    return $this->hasMany(IncidentUpdate::class);
  }

  public function canAccessPanel(Panel $panel): bool
  {
    return true;
  }
}

