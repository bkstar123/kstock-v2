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

        Gate::define('financial.statements.destroy', function ($user, $financial_statement) {
            return $user->hasRole(Role::SUPERADMINS) || $user->id == $financial_statement->admin_id;
        });

        Gate::define('financial.statements.show', function ($user, $financial_statement) {
            return $user->hasRole(Role::SUPERADMINS) || $user->id == $financial_statement->admin_id;
        });

        Gate::define('settings.index', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });

        Gate::define('settings.update', function ($user) {
            return $user->hasRole(Role::SUPERADMINS);
        });
    }
}
