<?php
namespace RonyLang\FilamentTinymceEditor\Console;

use Illuminate\Console\Command;

class InstallTinymceAssets extends Command
{
    protected $signature = 'tinymce:install-assets';
    protected $description = 'Download TinyMCE assets to public/vendor/filament-tinymce-editor';

    public function handle()
    {
        $version = config('filament-tinymce-editor.version.tiny', '8.0.2');
        $url = 'https://cdn.jsdelivr.net/npm/tinymce@' . $version . '/tinymce.min.js';

        $publicDir = public_path('vendor/filament-tinymce-editor');
        if (!is_dir($publicDir) && !mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
            $this->error('Unable to create directory: ' . $publicDir);
            return 1;
        }

        $dest = $publicDir . DIRECTORY_SEPARATOR . 'tinymce.min.js';

        $this->info('Downloading TinyMCE v' . $version . ' from ' . $url);

        // Try using file_get_contents first, fallback to curl
        $content = @file_get_contents($url);
        if ($content === false) {
            if (function_exists('curl_version')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $content = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);
                if ($content === false) {
                    $this->error('Failed to download TinyMCE: ' . $err);
                    return 1;
                }
            } else {
                $this->error('file_get_contents failed and cURL is not available.');
                return 1;
            }
        }

        if (file_put_contents($dest, $content) === false) {
            $this->error('Failed to write file to ' . $dest);
            return 1;
        }

        $this->info('TinyMCE downloaded to: ' . $dest);
        return 0;
    }
}
