<?php

namespace Luttje\UserCustomId;

use Luttje\UserCustomId\Facades\UserCustomId;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserCustomIdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-user-custom-id')
            ->hasMigration('create_user_custom_ids_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('luttje/laravel-user-custom-id');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(UserCustomIdManager::class, function ($app) {
            return new UserCustomIdManager();
        });
    }
}
