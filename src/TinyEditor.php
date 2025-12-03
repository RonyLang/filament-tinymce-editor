<?php

namespace RonyLang\FilamentTinymceEditor;

use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Contracts;
use Filament\Forms\Components\Field;

class TinyEditor extends Field implements Contracts\CanBeLengthConstrained
{
    use Concerns\CanBeLengthConstrained;
    use Concerns\HasFileAttachments;
    use Concerns\HasPlaceholder;

    protected string $view = 'filament-tinymce-editor::tiny-editor';

    protected string $profile = 'default';
    protected bool $toolbarSticky = true;
    protected int $toolbarStickyOffset = 64;
    protected string $toolbar;
    protected string|\Closure $language;
    protected bool $isSimple = false;
    protected string $direction;
    protected int $width = 0;
    protected int $height = 0;
    protected int $minWidth = 500;
    protected int $minHeight = 500;
    protected int $maxHeight = 0;
    protected int $tinyMaxWidth = 0;
    protected bool|string $resize = false;
    protected string|bool $darkMode;
    protected string $toolbarMode = 'floating';
    protected string $toolbarLocation = 'auto';
    protected bool $showMenuBar = true;
    protected bool $relativeUrls = false;
    protected bool $removeScriptHost = true;
    protected bool $convertUrls = true;
    protected array|\Closure $customConfigs = [];
    protected bool $imageAdvtab = false;
    protected string $fileManagerTitle;
    protected int $fileManagerWidth;
    protected int $fileManagerHeight;
    ////////

    protected function setUp(): void
    {
        parent::setUp();

        $this->language = app()->getLocale();
        $this->direction = config('filament-tinymce-editor.direction', 'ltr');
        $this->darkMode = config('filament-tinymce-editor.darkMode', 'auto');
        $this->fileManagerWidth = config('filament-tinymce-editor.file_manager.width', '1200');
        $this->fileManagerHeight = config('filament-tinymce-editor.file_manager.height', '400');
        $this->fileManagerTitle = config('filament-tinymce-editor.file_manager.title', 'File Manager');
    }

    public function getLicenseKey(): string
    {
        return config('filament-tinymce-editor.license_key', 'gpl');
    }

    public function getToolbarSticky(): bool
    {
        return $this->toolbarSticky;
    }

    public function toolbarSticky(bool $toolbarSticky): static
    {
        $this->toolbarSticky = $toolbarSticky;

        return $this;
    }

    public function getToolbarStickyOffset(): int
    {
        return $this->toolbarStickyOffset;
    }

    public function toolbarStickyOffset(int $toolbarStickyOffset): static
    {
        $this->toolbarStickyOffset = $toolbarStickyOffset;

        return $this;
    }

    public function isSimple(): bool
    {
        return (bool)$this->evaluate($this->isSimple);
    }

    public function getPlugins(): string
    {
        $plugins = 'accordion autoresize codesample directionality advlist autolink link image lists charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons help';

        if ($this->isSimple()) {
            $plugins = 'autoresize directionality emoticons link wordcount';
        }

        if (config('filament-tinymce-editor.profiles.' . $this->profile . '.plugins')) {
            $plugins = config('filament-tinymce-editor.profiles.' . $this->profile . '.plugins');
        }

        return $plugins;
    }


    public function getExternalPlugins(): string
    {
        if (config('filament-tinymce-editor.profiles.' . $this->profile . '.external_plugins')) {
            return str_replace('"', "'", json_encode(config('filament-tinymce-editor.profiles.' . $this->profile . '.external_plugins')));
        }

        return '{}';
    }

    public function getToolbar(): string
    {
        $toolbar = 'undo redo removeformat | styles | bold italic | rtl ltr | alignjustify alignright aligncenter alignleft | numlist bullist outdent indent accordion | forecolor backcolor | blockquote table toc hr | image link anchor media codesample emoticons | visualblocks print preview wordcount fullscreen help';
        if ($this->isSimple()) {
            $toolbar = 'removeformat | bold italic | rtl ltr | link emoticons';
        }

        if (config('filament-tinymce-editor.profiles.' . $this->profile . '.toolbar')) {
            $toolbar = config('filament-tinymce-editor.profiles.' . $this->profile . '.toolbar');
        }

        return $toolbar;
    }

    public function language(string|\Closure $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getInterfaceLanguage(): string
    {
        return match ($this->evaluate($this->language)) {
            'ar' => 'ar',
            'az' => 'az',
            'bg' => 'bg-BG',
            'bn' => 'bn-BD',
            'ca' => 'ca',
            'cs' => 'cs',
            'cy' => 'cy',
            'da' => 'da',
            'de' => 'de',
            'dv' => 'dv',
            'el' => 'el',
            'eo' => 'eo',
            'es' => 'es',
            'et' => 'et',
            'eu' => 'eu',
            'fa' => 'fa',
            'fi' => 'fi',
            'fr' => 'fr-FR',
            'ga' => 'ga',
            'gl' => 'gl',
            'he' => 'he-IL',
            'hr' => 'hr',
            'hu' => 'hu-HU',
            'hy' => 'hy',
            'id' => 'id',
            'is' => 'is-IS',
            'it' => 'it',
            'ja' => 'ja',
            'kab' => 'kab',
            'kk' => 'kk',
            'ko' => 'ko-KR',
            'ku' => 'ku',
            'lt' => 'lt',
            'lv' => 'lv',
            'nb' => 'nb-NO',
            'nl' => 'nl',
            'oc' => 'oc',
            'pl' => 'pl',
            'pt_PT' => 'pt-PT',
            'pt_BR' => 'pt-BR',
            'pt-PT' => 'pt-PT',
            'pt-BR' => 'pt-BR',
            'ro' => 'ro',
            'ru' => 'ru',
            'sk' => 'sk',
            'sl' => 'sl',
            'sq' => 'sq',
            'sr' => 'sr',
            'sv' => 'sv-SE',
            'ta' => 'ta',
            'tg' => 'tg',
            'th' => 'th-TH',
            'tr' => 'tr',
            'ug' => 'ug',
            'uk' => 'uk',
            'vi' => 'vi',
            'zh' => 'zh-Hans',
            'zh-CN' => 'zh-Hans',
            'zh-TW' => 'zh-Hant',
            'zh-HK' => 'zh-HK',
            'zh-MO' => 'zh-MO',
            'zh-SG' => 'zh-SG',
            default => 'en',
        };
    }

    public function getLanguageURL(string $lang): string
    {
        return Tiny::getLanguageURL($lang);
    }

    public function getDirection(): string
    {
        if (!$this->direction || $this->direction == 'auto') {
            return match ($this->getInterfaceLanguage()) {
                'ar' => 'rtl',
                'fa' => 'rtl',
                default => 'ltr',
            };
        }

        return $this->direction;
    }

    public function rtl(): static
    {
        $this->direction = 'rtl';

        return $this;
    }

    public function ltr(): static
    {
        $this->direction = 'ltr';

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    public function maxHeight(int $maxHeight): static
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    public function getMinHeight(): int
    {
        return $this->minHeight;
    }

    public function minHeight(int $minHeight): static
    {
        $this->minHeight = $minHeight;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getTinyMaxWidth(): int
    {
        return $this->tinyMaxWidth;
    }

    public function maxTinyWidth(int $maxWidth): static
    {
        $this->tinyMaxWidth = $maxWidth;

        return $this;
    }

    public function getMinWidth(): int
    {
        return $this->minWidth;
    }

    public function minWidth(int $minWidth): static
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    public function getResize(): bool|string
    {
        return is_bool($this->resize) ? $this->resize : "'$this->resize'";
    }

    public function resize(bool|string $resize): static
    {
        $this->resize = $resize;

        return $this;
    }

    public function darkMode(): string|bool
    {
        return $this->darkMode;
    }

    public function getToolbarMode(): string
    {
        return $this->toolbarMode;
    }

    public function toolbarMode(string $toolbarMode): static
    {
        $this->toolbarMode = $toolbarMode;

        return $this;
    }

    public function getToolbarLocation(): string
    {
        return $this->toolbarLocation;
    }

    public function toolbarLocation(string $toolbarLocation): static
    {
        $this->toolbarLocation = $toolbarLocation;

        return $this;
    }

    public function getShowMenuBar(): bool
    {
        return $this->showMenuBar;
    }

    public function showMenuBar(): static
    {
        $this->showMenuBar = true;

        return $this;
    }

    public function getRelativeUrls(): bool
    {
        return $this->relativeUrls;
    }

    public function RelativeUrls(bool $relativeUrls): static
    {
        $this->relativeUrls = $relativeUrls;

        return $this;
    }

    public function getRemoveScriptHost(): bool
    {
        return $this->removeScriptHost;
    }

    public function RemoveScriptHost(bool $removeScriptHost): static
    {
        $this->removeScriptHost = $removeScriptHost;

        return $this;
    }

    public function getConvertUrls(): bool
    {
        return $this->convertUrls;
    }

    public function ConvertUrls(bool $convertUrls): static
    {
        $this->convertUrls = $convertUrls;

        return $this;
    }

    public function getFontSizes(): string
    {
        return config('filament-tinymce-editor.extra.toolbar.fontsize', '10px 12px 13px 14px 16px 18px 20px');
    }

    public function getFontFamilies(): string
    {
        return config('filament-tinymce-editor.extra.toolbar.fontfamily', '');
    }

    public function getCustomConfigs(): string
    {
        $defaultConfigs = config("filament-tinymce-editor.profiles.{$this->profile}.custom_configs", []);
        $customConfigs = $this->evaluate($this->customConfigs) ?? [];

        $mergedConfigs = array_replace_recursive($customConfigs, $defaultConfigs);

        if (empty($mergedConfigs)) {
            return '{}';
        }

        return str_replace('"', "'", json_encode($mergedConfigs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function getimageAdvtab(): bool
    {
        return $this->imageAdvtab ?? false;
    }

    public function imageAdvtab(bool $imageAdvtab): static
    {
        $this->imageAdvtab = $imageAdvtab;

        return $this;
    }

    public function profile(string $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * File manager integration getters.
     */

    public function getFileManagerUrl(): string
    {
        // Always use named route to respect prefix configuration & middleware
        return route('tinymc-editor.file-manager');
    }

    public function getFileManagerTitle(): string
    {
        return $this->fileManagerTitle;
    }

    public function getFileManagerWidth(): int
    {
        return $this->fileManagerWidth;
    }

    public function FileManagerWidth(int $width): static
    {
        $this->fileManagerWidth = $width;

        return $this;
    }

    public function getFileManagerHeight(): int
    {
        return $this->fileManagerHeight;
    }

    public function FileManagerHeight(int $height): static
    {
        $this->fileManagerHeight = $height;

        return $this;
    }
}
