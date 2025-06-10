<?php

namespace ShubhKansara\PhpQuickbooksConnector;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ShubhKansara\PhpQuickbooksConnector\Console\Commands\GenerateQwcCommand;
use ShubhKansara\PhpQuickbooksConnector\Services\SyncManager;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncLog;

class QuickBooksConnectorServiceProvider extends ServiceProvider {
    public function boot() {

        // 1 ) Load migrations
        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );

        // 2 ) Load views from package, namespace them as 'php-quickbooks'
        $this->loadViewsFrom( __DIR__ . '/../resources/views', 'php-quickbooks' );
        // Allow publishing views to host app
        $this->publishes( [
            __DIR__ . '/../resources/views' => resource_path( 'views/vendor/php-quickbooks' ),
        ], 'php-quickbooks-views' );

        // 3 ) Publish config file
        $this->publishes( [
            __DIR__ . '/Config/quickbooks.php' => config_path( 'php-quickbooks.php' ),
        ], 'php-quickbooks-config' );

        // 4 ) Load routes only in desktop mode
        if ( config( 'php-quickbooks.mode' ) === 'desktop' ) {
            $this->loadRoutesFrom( __DIR__ . '/Config/routes.php' );
        }



        Event::listen(QuickBooksLogEvent::class, function ($event) {
            QbSyncLog::create([
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ]);
        });
    }

    public function register() {
        // 1 ) Merge package config
        $this->mergeConfigFrom( __DIR__ . '/Config/quickbooks.php', 'php-quickbooks' );

        // 2 ) Bind SyncManager as singleton
        $this->app->singleton( SyncManager::class );

        // 4 ) Register console commands
        if ( $this->app->runningInConsole() ) {
            $this->commands( [
                GenerateQwcCommand::class,
            ] );

            // ( Optionally ) allow migration publishing
            $this->publishes( [
                __DIR__ . '/../database/migrations/' => database_path( 'migrations' ),
            ], 'php-quickbooks-migrations' );
        }
    }
}
