# Filament TineMce Editor

A [TineMce](https://www.tiny.cloud/) integration for [Filament](https://filamentphp.com/) Admin/Forms.


## Installation

1-Install the package via composer

```bash
composer require rony-lang/filament-tinymce-editor
```

2-Publish assets

```bash
php artisan vendor:publish --provider="RonyLang\FilamentTinymceEditor\Providers\TinymceditorServiceProvider"
```

```bash
php artisan tinymce:install-assets
```

3-Migrate Database

```bash
php artisan migrate
```

4-For Add User Access To FileManager

Example UserId = 1

```bash
php artisan tinymce:editor --user=1
```

## Usage

The editor extends the default Field class so most other methods available on that class can be used when adding it to a form.

```php
use RonyLang\FilamentTinymceEditor\TinyEditor;

TinyEditor::make('content')
	->fileAttachmentsDisk('public')
	->fileAttachmentsVisibility('public')
	->fileAttachmentsDirectory('uploads')
    ->profile('default|simple|full|minimal|none|custom')
	->rtl()
	->resize('both')
    ->required();
```

## Config

The plugin will work without publishing the config, but should you need to change any of the default settings you can publish the config file with the following Artisan command:

```bash
php artisan vendor:publish --tag="filament-tinymce-editor-config"
```

### Profiles / Tools

The package comes with 4 profiles (or toolbars) out of the box. You can also use a pipe `|` to separate tools into groups. The default profile is the full set of tools.

```php
'profiles' => [
    'default' => [
		'plugins' => 'accordion autoresize codesample directionality advlist link image lists preview pagebreak searchreplace wordcount code fullscreen insertdatetime media table emoticons',
		// Installing external plugins
		'external_plugins' => [
		'paste_from_word' => 'https://unpkg.com/@pangaeatech/tinymce-paste-from-word-plugin@latest/index.js'
    ],
		'toolbar' => 'undo redo removeformat | styles | bold italic | rtl ltr | alignjustify alignright aligncenter alignleft | numlist bullist outdent indent | forecolor backcolor | blockquote table toc hr | image link media codesample emoticons | wordcount fullscreen',
		'upload_directory' => null,
		// Custom Configs
		'custom_configs' => [
		'paste_webkit_styles' => 'color font-size',
    ]
	],

	'simple' => [
		'plugins' => 'autoresize directionality emoticons link wordcount',
		'toolbar' => 'removeformat | bold italic | rtl ltr | numlist bullist | link emoticons',
		'upload_directory' => null,
	],

	'minimal' => [
		'plugins' => 'link wordcount',
		'toolbar' => 'bold italic link numlist bullist',
		'upload_directory' => null,
	],

	'full' => [
		'plugins' => 'accordion autoresize codesample directionality advlist autolink link image lists charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons template help',
		'toolbar' => 'undo redo removeformat | styles | bold italic | rtl ltr | alignjustify alignright aligncenter alignleft | numlist bullist outdent indent accordion | forecolor backcolor | blockquote table toc hr | image link anchor media codesample emoticons | visualblocks print preview wordcount fullscreen help',
		'upload_directory' => null,
	],
],
```

### RTL Support

In order for things like text align to work properly with RTL languages you
can switch the `direction` key in the config to 'rtl'.

```php
// config/filament-tinyeditor.php
'direction' => 'rtl'
```

## Upgrading TinyMCE (local provider)

With `provider = local`, TinyMCE is served from `public/vendor/tinymce` (not tracked by git, not managed by composer).

1. Set the new version in `config/filament-tinymce-editor.php` (`version.tiny`).
2. Run the installer **as the web server user** on every server:

```bash
php artisan tinymce:install-assets            # installs version.tiny if it differs from the installed one
php artisan tinymce:install-assets --force    # reinstall the same version
php artisan tinymce:install-assets --tinymce-version=8.10.2
```

The installer downloads the npm tarball, verifies it against the registry's sha512 `integrity`, extracts it next to the live directory and swaps it in with two renames, so the live copy is never half-written. On any failure nothing is changed. The previous version is kept in `storage/app/tinymce-backups/<version>-<timestamp>` (rollback: move it back to `public/vendor/tinymce`).

Do **not** delete `public/vendor/tinymce` on a live server: when `tinymce.min.js` is missing the package downloads TinyMCE during a web request, and concurrent requests can race.

### Cache busting

Every local TinyMCE URL (core, theme, model, icons, plugins, skins) carries `?v=<installed version>` via TinyMCE's `cache_suffix`. The version is read from the installed `public/vendor/tinymce/package.json`, not from the config, so a URL always matches the files actually served — deploying the config before or after the files never caches old files under a new version. npm ships TinyMCE files with a 1985 `Last-Modified`, which browsers otherwise cache heuristically for years and then mix with a newer core.

Scripts that load TinyMCE themselves can reuse the same values from `window.filamentData.filamentTinymceEditor` (`src`, `cacheSuffix`).

## Versioning

This project follow the [Semantic Versioning](https://semver.org/) guidelines.

## License

Licensed under the MIT license, see [LICENSE.md](LICENSE.md) for details.
