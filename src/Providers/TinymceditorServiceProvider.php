<?php

namespace RonyLang\FilamentTinymceEditor\Providers;

use RonyLang\FilamentTinymceEditor\Http\Middleware\EnsureTinymcePermission;
use RonyLang\FilamentTinymceEditor\Tiny;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TinymceditorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-tinymce-editor')
            ->hasConfigFile()
            ->hasViews()
            ->hasInstallCommand(
                function (InstallCommand $command) {
                    $command->publishConfigFile()
                        ->copyAndRegisterServiceProviderInApp()
                        ->askToStarRepoOnGitHub($this->getAssetPackageName());
                }
            );
    }

    public function packageRegistered(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \RonyLang\FilamentTinymceEditor\Console\GrantTinymceEditorPermission::class,
            ]);
        }
    }

    public function packageBooted(): void
    {
        $tinyVersion = config('filament-tinymce-editor.version.tiny', '8.0.2');
        $tiny_licence_key = config('filament-tinymce-editor.version.licence_key', 'no-api-key');
        $tiny_languages = Tiny::getLanguages();

        // Register package routes automatically
        \RonyLang\FilamentTinymceEditor\Controllers\FileManagerController::routes();

        // Register middleware alias for easier use in routes
        app('router')->aliasMiddleware('tinymce.permission', EnsureTinymcePermission::class);
        // Publish migration
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_tinymce_permissions_table.php.stub' => database_path('migrations/2025_09_12_140932_create_tinymce_permissions_table.php'),
        ], 'tinymce-migrations');

        $languages = [];
        $optional_languages = config('filament-tinymce-editor.languages', []);
        if (!is_array($optional_languages)) {
            $optional_languages = [];
        }

        foreach ($tiny_languages as $locale => $language) {
            $locale = str_replace('tinymce-lang-', '', $locale);
            $languages[] = Js::make(
                'tinymce-lang-' . $locale,
                array_key_exists($locale, $optional_languages) ? $optional_languages[$locale] : $language
            )->loadedOnRequest();
        }

        $provider = config('filament-tinymce-editor.provider', 'local');

        // 计算主脚本 URL，支持 cloud/cdn/local
        if ($provider === 'local') {
            $mainJs = config('filament-tinymce-editor.local.main_js', '/vendor/tinymce/tinymce.min.js');
        } else {
            $mainJs = 'https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.js';
            if ($tiny_licence_key != 'no-api-key') {
                $mainJs = 'https://cdn.tiny.cloud/1/' . $tiny_licence_key . '/tinymce/' . $tinyVersion . '/tinymce.min.js';
            }
        }

        FilamentAsset::register([
            // 主脚本按需加载，避免未使用页面也加载
            Js::make('tinymce', $mainJs)->loadedOnRequest(),
            ...$languages,
        ], package: $this->getAssetPackageName());
    }

    protected function getAssetPackageName(): ?string
    {
        return 'ronylang/filament-tinymce-editor';
    }

}
