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
                \RonyLang\FilamentTinymceEditor\Console\InstallTinymceAssets::class,
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

        // 计算主脚本路径/URL，支持 cloud/cdn/local
        if ($provider === 'local') {
            // 统一使用文件系统绝对路径作为 Asset 的来源，避免 assets 命令 copy 失败
            $targetPath = public_path('vendor/tinymce');
            $mainFile = $targetPath . DIRECTORY_SEPARATOR . 'tinymce.min.js';

            if (!file_exists($mainFile)) {
                try {
                    $tgzUrl = 'https://registry.npmjs.org/tinymce/-/tinymce-' . $tinyVersion . '.tgz';
                    $escapedTgzUrl = escapeshellarg($tgzUrl);
                    $escapedTarget = escapeshellarg($targetPath);

                    @mkdir($targetPath, 0755, true);
                    $cmd = "curl -L {$escapedTgzUrl} | tar -xzf - -C {$escapedTarget} --strip-components=1 package";
                    @exec($cmd, $output, $status);

                    if (($status ?? 1) !== 0 || !file_exists($mainFile)) {
                        $singleUrl = 'https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.min.js';
                        $outFile = $mainFile;
                        @exec("curl -L " . escapeshellarg($singleUrl) . " -o " . escapeshellarg($outFile), $o2, $r2);
                    }
                } catch (\Throwable $e) {
                    // 保持静默，避免阻塞应用启动
                }
            }

            $mainJsSrc = $mainFile; // 关键：使用文件系统路径，非 /vendor/**
        } else {
            $mainJsSrc = 'https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.js';
            if ($tiny_licence_key != 'no-api-key') {
                $mainJsSrc = 'https://cdn.tiny.cloud/1/' . $tiny_licence_key . '/tinymce/' . $tinyVersion . '/tinymce.min.js';
            }
        }

        FilamentAsset::register([
            // 主脚本按需加载；local 用绝对文件路径，cdn 用远程 URL
            Js::make('tinymce', $mainJsSrc)->loadedOnRequest(),
            ...$languages,
        ], package: $this->getAssetPackageName());
    }

    protected function getAssetPackageName(): ?string
    {
        return 'ronylang/filament-tinymce-editor';
    }

}
