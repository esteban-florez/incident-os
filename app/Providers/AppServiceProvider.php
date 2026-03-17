<?php

namespace App\Providers;

use App\Enums\Role as RoleEnum;
use App\Models\Incident;
use App\Observers\IncidentObserver;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // Observers
    Incident::observe(IncidentObserver::class);

    Gate::before(fn ($user, $ability) => $user->hasRole(RoleEnum::SUPER_ADMIN->value) ? true : null);

    FilamentTimezone::set('America/Caracas');

    Password::defaults(function () {
      return Password::min(8)
        ->max(20)
        ->symbols()
        ->numbers()
        ->mixedCase();
    });

    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
  }
}

