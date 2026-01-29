<?php

namespace RonyLang\FilamentTinymceEditor\Console;

use Illuminate\Console\Command;

class InstallTinymceAssets extends Command
{
    protected $signature = 'tinymce:install-assets {--version=}';

    protected $description = 'Download and install TinyMCE front-end assets to public/vendor/tinymce';

    public function handle(): int
    {
        $version = $this->option('version') ?: config('filament-tinymce-editor.version.tiny', '8.0.2');
        $this->info("Installing TinyMCE assets (version: {$version})...");

        $targetPath = public_path('vendor/tinymce');
        $mainFile = $targetPath . DIRECTORY_SEPARATOR . 'tinymce.min.js';

        if (file_exists($mainFile)) {
            $this->info('TinyMCE appears to be already installed at: ' . $mainFile);
            return 0;
        }

        if (!@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
            $this->error('Failed to create target directory: ' . $targetPath);
            return 1;
        }

        // Try npm tarball first
        $tgzUrl = 'https://registry.npmjs.org/tinymce/-/tinymce-' . $version . '.tgz';
        $this->line("Attempting to download npm package: {$tgzUrl}");

        $escapedTgzUrl = escapeshellarg($tgzUrl);
        $escapedTarget = escapeshellarg($targetPath);
        $cmd = "curl -L {$escapedTgzUrl} | tar -xzf - -C {$escapedTarget} --strip-components=1 package";

        @exec($cmd, $output, $status);

        if (($status ?? 1) === 0 && file_exists($mainFile)) {
            $this->info('TinyMCE npm package downloaded and extracted successfully.');
            return 0;
        }

        $this->line('Npm tarball extraction failed or tinymce.min.js not found, falling back to CDN single-file download.');

        $singleUrl = 'https://cdn.jsdelivr.net/npm/tinymce@' . $version . '/tinymce.min.js';
        $outFile = $targetPath . DIRECTORY_SEPARATOR . 'tinymce.min.js';
        $this->line("Downloading single file: {$singleUrl}");

        $escapedSingle = escapeshellarg($singleUrl);
        $escapedOut = escapeshellarg($outFile);
        @exec("curl -L {$escapedSingle} -o {$escapedOut}", $o2, $r2);

        if (($r2 ?? 1) === 0 && file_exists($outFile)) {
            $this->info('Downloaded tinymce.min.js to: ' . $outFile);
            return 0;
        }

        $this->error('Failed to download TinyMCE assets. Please ensure curl/tar are available and writable permissions to public directory.');
        return 1;
    }
}
