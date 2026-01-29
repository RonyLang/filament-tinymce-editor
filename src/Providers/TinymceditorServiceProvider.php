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

            // 如果本地资源不存在，尝试在引导阶段自动下载并解压到 public/vendor/tinymce
            $targetPath = public_path('vendor/tinymce');
            $mainFile = $targetPath . DIRECTORY_SEPARATOR . 'tinymce.min.js';

            if (!file_exists($mainFile)) {
                try {
                    // 优先从 npm registry 下载 tgz 并解压（需要系统有 curl 和 tar）
                    $tgzUrl = 'https://registry.npmjs.org/tinymce/-/tinymce-' . $tinyVersion . '.tgz';
                    $escapedTgzUrl = escapeshellarg($tgzUrl);
                    $escapedTarget = escapeshellarg($targetPath);

                    // 创建目标目录并从标准输入解压（--strip-components=1 去掉 package/ 前缀）
                    @mkdir($targetPath, 0755, true);
                    $cmd = "curl -L {$escapedTgzUrl} | tar -xzf - -C {$escapedTarget} --strip-components=1 package";
                    @exec($cmd, $output, $status);

                    if (($status ?? 1) !== 0 || !file_exists($mainFile)) {
                        // 回退：只下载单个 minified 文件（保证最基础功能）
                        $singleUrl = 'https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.min.js';
                        $escapedSingle = escapeshellarg($singleUrl);
                        $outFile = $targetPath . DIRECTORY_SEPARATOR . 'tinymce.min.js';
                        @exec("curl -L {$escapedSingle} -o " . escapeshellarg($outFile), $o2, $r2);
                    }
                } catch (\Throwable $e) {
                    // 失败则静默，不阻塞应用启动；前端仍会报 404，提示用户手动安装
                }
            }
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
