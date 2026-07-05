<?php

namespace App\Providers;

use App\Services\Symbols;
use App\Exceptions\Cms\Handler;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Services\Contracts\Symbols as SymbolsInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // App dùng AdminLTE 3 / Bootstrap 4 — buộc pagination render theo Bootstrap thay vì
        // view Tailwind mặc định của Laravel (vốn chứa <svg> chevron phình to khi không có Tailwind).
        Paginator::useBootstrapFour();

        if (preg_match('/^cms\/.+$/', request()->path())) {
            if (class_exists(Handler::class)) {
                app()->singleton(ExceptionHandler::class, Handler::class);
            }
        }
        $this->app->singleton(SymbolsInterface::class, Symbols::class);
    }
}
