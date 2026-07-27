<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Bkstar123\BksCMS\AdminPanel\Role;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('financial.statements.massiveDestroy', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });

        // `financial_statements` rows are SHARED between admins (one row per
        // symbol/year/quarter); ownership lives in the `financial_statement_entries`
        // pivot, never on the row. Sharing is a storage optimisation, not a
        // visibility change — an admin still only sees the periods they pulled.
        Gate::define('financial.statements.destroy', function ($user, $financial_statement) {
            return $user->hasRole(Role::SUPERADMINS) || $financial_statement->isHeldBy($user->id);
        });

        Gate::define('financial.statements.show', function ($user, $financial_statement) {
            return $user->hasRole(Role::SUPERADMINS) || $financial_statement->isHeldBy($user->id);
        });

        // Who last refreshed a shared statement is metadata about another admin's
        // activity, so only superadmins see the identity. Everyone still sees WHEN it
        // was refreshed — that is not optional: the content is a rolling window
        // snapshot, and any holder's re-pull rewrites what every other holder reads,
        // so the timestamp is how you tell your numbers moved under you.
        Gate::define('financial.statements.viewPuller', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });

        Gate::define('settings.index', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });

        Gate::define('settings.update', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });
    }
}
