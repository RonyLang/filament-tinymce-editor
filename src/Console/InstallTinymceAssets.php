<?php

namespace RonyLang\FilamentTinymceEditor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RonyLang\FilamentTinymceEditor\Tiny;
use RuntimeException;
use Throwable;

class InstallTinymceAssets extends Command
{
    // 使用非保留选项名，避免与全局 --version 冲突
    protected $signature = 'tinymce:install-assets
        {--tinymce-version= : Version to install (default: config filament-tinymce-editor.version.tiny)}
        {--force : Reinstall even if this version is already installed}';

    protected $description = 'Install or upgrade TinyMCE in public/vendor/tinymce (verified npm download + atomic swap)';

    private const REGISTRY = 'https://registry.npmjs.org/tinymce/';

    public function handle(): int
    {
        $version = (string) ($this->option('tinymce-version') ?: Tiny::version());
        if (!Tiny::isValidVersion($version)) {
            $this->error("Invalid TinyMCE version: {$version}");

            return self::FAILURE;
        }

        $target = Tiny::localPath();
        $installed = Tiny::installedVersion();
        if ($installed === $version && !$this->option('force')) {
            $this->info("TinyMCE {$version} is already installed at {$target}.");

            return self::SUCCESS;
        }

        $this->info("Installing TinyMCE {$version}" . ($installed ? " (replacing {$installed})" : '') . '...');
        $staging = dirname($target) . DIRECTORY_SEPARATOR . '.tinymce-' . $version . '-staging';
        $tarball = storage_path('app/tinymce-' . $version . '-' . uniqid() . '.tgz');

        try {
            $this->download($version, $tarball);
            $this->extract($tarball, $staging, $version);
            $backup = $this->swap($staging, $target, $installed);
        } catch (Throwable $e) {
            File::deleteDirectory($staging);
            $this->error('TinyMCE install failed, the installed version was not changed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            File::delete($tarball);
        }

        $this->info("TinyMCE {$version} installed at {$target}.");
        if ($backup !== null) {
            $this->line("Previous version kept at {$backup}");
        }

        return self::SUCCESS;
    }

    /** Download the npm tarball and verify it against the registry's sha512 integrity. */
    private function download(string $version, string $tarball): void
    {
        $dist = Http::timeout(30)->retry(2, 500)->get(self::REGISTRY . $version)->throw()->json('dist');
        $integrity = (string) ($dist['integrity'] ?? '');
        if (!str_starts_with($integrity, 'sha512-') || empty($dist['tarball'])) {
            throw new RuntimeException('npm registry metadata has no sha512 integrity or tarball URL');
        }

        File::ensureDirectoryExists(dirname($tarball));
        Http::timeout(300)->retry(2, 1000)->sink($tarball)->get($dist['tarball'])->throw();

        $actual = 'sha512-' . base64_encode((string) hash_file('sha512', $tarball, true));
        if (!hash_equals($integrity, $actual)) {
            throw new RuntimeException('integrity mismatch for the downloaded tarball');
        }
        $this->line('Download verified against npm integrity.');
    }

    /** Extract into a staging directory next to the target and sanity-check it. */
    private function extract(string $tarball, string $staging, string $version): void
    {
        File::deleteDirectory($staging);
        File::ensureDirectoryExists($staging);
        // No member filter argument: works with both GNU tar and BusyBox tar.
        Process::timeout(120)->run(['tar', '-xzf', $tarball, '-C', $staging, '--strip-components=1'])->throw();

        $extracted = json_decode((string) @file_get_contents($staging . '/package.json'), true)['version'] ?? null;
        if ($extracted !== $version || !is_file($staging . '/tinymce.min.js')) {
            throw new RuntimeException("extracted package is incomplete or not version {$version}");
        }
    }

    /**
     * Two same-filesystem renames (target -> sibling backup, staging -> target), so the
     * live directory is never half-written. The backup then leaves public/ for
     * storage/app/tinymce-backups (kept as a hidden sibling if that move fails).
     */
    private function swap(string $staging, string $target, ?string $installed): ?string
    {
        if (!is_dir($target)) {
            if (!@rename($staging, $target)) {
                throw new RuntimeException("cannot move {$staging} to {$target}");
            }

            return null;
        }

        $stamp = ($installed ?? 'unknown') . '-' . date('YmdHis');
        $sibling = dirname($target) . DIRECTORY_SEPARATOR . '.tinymce-backup-' . $stamp;
        if (!@rename($target, $sibling)) {
            throw new RuntimeException("cannot move {$target} aside");
        }
        if (!@rename($staging, $target)) {
            @rename($sibling, $target);
            throw new RuntimeException("cannot move {$staging} into place (previous version restored)");
        }

        $backup = storage_path('app/tinymce-backups/' . $stamp);
        File::ensureDirectoryExists(dirname($backup));

        return File::moveDirectory($sibling, $backup) ? $backup : $sibling;
    }
}
