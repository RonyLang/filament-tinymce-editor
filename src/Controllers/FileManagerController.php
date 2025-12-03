<?php

namespace RonyLang\FilamentTinymceEditor\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManagerController
{
    /**
     * Registers the routes for the file manager.
     *
     * @return void
     */

    public static function routes()
    {
        Route::middleware(['web', 'auth', \RonyLang\FilamentTinymceEditor\Http\Middleware\EnsureTinymcePermission::class])
            ->prefix("tinymc-editor" . config('filament-tinymce-editor.file_manager.url', 'file-manager'))
            ->name('tinymc-editor.')
            ->group(function () {
                Route::get('/', [self::class, 'index'])->name('file-manager');
                Route::post('/upload', [self::class, 'upload'])->name("upload");
                Route::post('/rename', [self::class, 'rename'])->name("rename");
                Route::post('/folder', [self::class, 'folder'])->name("folder");
                Route::delete('/file', [self::class, 'delete'])->name("delete");
                Route::get('/serve/{path}', [self::class, 'serveFile'])->where('path', '.*')->name('file-manager.serve');
            });
    }

    public function index(Request $request)
    {
        $type = $request->query('type');
        $path = trim(str_replace(['..', '\\'], ['.', '/'], (string)$request->query('path', '')), '/');
        // Get disk from config
        $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
        $root = Storage::disk($disk)->path('');
        if (!is_dir($root) && !mkdir($root, 0755, true)) {
            return response()->json(['ok' => false, 'error' => 'Failed to create root directory'], 500);
        }
        $current = $path ? $root . DIRECTORY_SEPARATOR . $path : $root;
        $realCurrent = realpath($current) ?: $current;
        $realRoot = realpath($root) ?: $root;
        if (!str_starts_with($realCurrent, $realRoot)) {
            abort(403, 'Access denied');
        }
        if (!is_dir($current)) {
            $current = $root;
            $path = '';
        }
        $dirs = collect(glob($current . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR))
            ->map(function ($dir) use ($root) {
                $name = basename($dir);
                $rel = trim(str_replace($root, '', $dir), '\\/');
                return [
                    'name' => $name,
                    'path' => $rel,
                    'size' => 0,
                    'mtime' => filemtime($dir) ?: null,
                ];
            })->values();
        $allFiles = glob($current . DIRECTORY_SEPARATOR . '*');
        $files = collect($allFiles)
            ->filter(fn($p) => is_file($p))
            ->map(function ($p) use ($root) {
                $filename = basename($p);
                $rel = trim(str_replace($root, '', $p), '\\/');
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                return [
                    'name' => $filename,
                    'url' => route('tinymc-editor.file-manager.serve', ['path' => ltrim(str_replace('\\', '/', $rel), '/')]),
                    'ext' => $ext,
                    'path' => $rel,
                    'size' => filesize($p) ?: 0,
                    'mtime' => filemtime($p) ?: null,
                ];
            })->values();
        if ($type === 'image') {
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $files = $files->filter(fn($f) => in_array($f['ext'], $imageExts));
        }
        $breadcrumbs = [];
        $acc = '';
        if ($path !== '') {
            foreach (explode('/', $path) as $seg) {
                $acc = ltrim($acc . '/' . $seg, '/');
                $breadcrumbs[] = ['name' => $seg, 'path' => $acc];
            }
        }
        return view('filament-tinymce-editor::file-manager', [
            'type' => $type,
            'path' => $path,
            'breadcrumbs' => $breadcrumbs,
            'dirs' => $dirs,
            'files' => $files,
        ]);
    }

    /**
     * Handle file upload.
     *
     * Returns a JSON structure compatible with the file-manager view, including
     * name, url, ext, path, size, and mtime for immediate UI update.
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => ['required', 'file'],
                'path' => ['nullable', 'string'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        }
        $file = $request->file('file');
        $path = trim(str_replace(['..', '\\'], ['.', '/'], (string)$request->string('path')), '/');
        // Get disk and upload options from config
        $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
        $allowedTypes = config('filament-tinymce-editor.fileAttachmentsTypes', []);
        $maxSizes = config('filament-tinymce-editor.fileAttachmentsMaxSize', []);
        $root = Storage::disk($disk)->path('');
        $directory = $path ? $root . DIRECTORY_SEPARATOR . $path : $root;
        $realDirectory = realpath($directory) ?: $directory;
        $realRoot = realpath($root) ?: $root;
        if (!str_starts_with($realDirectory, $realRoot)) {
            return response()->json(['ok' => false, 'error' => 'Access denied'], 403);
        }
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return response()->json(['ok' => false, 'error' => 'Failed to create directory'], 500);
        }
        if (!is_writable($directory)) {
            return response()->json(['ok' => false, 'error' => 'Directory is not writable'], 403);
        }
        // Detect file type by extension if not provided
        $ext = strtolower($file->getClientOriginalExtension());
        $type = $request->input('type');
        if (!$type) {
            if (in_array($ext, $allowedTypes['image'] ?? [])) {
                $type = 'image';
            } elseif (in_array($ext, $allowedTypes['media'] ?? [])) {
                $type = 'media';
            } else {
                $type = 'file';
            }
        }
        $allowedExts = $allowedTypes[$type] ?? [];
        $maxSize = $maxSizes[$type] ?? null;
        if (empty($allowedExts)) {
            return response()->json(['ok' => false, 'error' => 'File type not allowed for this type of upload', 'type' => $type], 422);
        }
        if (!in_array($ext, $allowedExts)) {
            return response()->json(['ok' => false, 'error' => 'File type not allowed', 'ext' => $ext, 'allowed' => $allowedExts, 'type' => $type], 422);
        }
        if ($maxSize && $file->getSize() > $maxSize * 1024) {
            return response()->json(['ok' => false, 'error' => 'File size exceeds the allowed limit', 'max' => $maxSize, 'size' => $file->getSize()], 422);
        }
        $safeName = time() . '_' . Str::random(8) . ($ext ? '.' . $ext : '');
        try {
            $file->move($directory, $safeName);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => 'Failed to upload file: ' . $e->getMessage()], 500);
        }
        $storedPath = ltrim(($path ? trim($path, '/') . '/' : '') . $safeName, '/');
        $fullPath = $directory . DIRECTORY_SEPARATOR . $safeName;
        return response()->json([
            'ok' => true,
            'name' => $safeName,
            'url' => route('tinymc-editor.file-manager.serve', ['path' => $storedPath]),
            'ext' => $ext,
            'path' => $storedPath,
            'size' => is_file($fullPath) ? (filesize($fullPath) ?: 0) : 0,
            'mtime' => is_file($fullPath) ? (filemtime($fullPath) ?: null) : null,
        ]);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'regex:/^[A-Za-z0-9_\-\.]+$/'],
        ]);
        // Get disk from config
        $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
        $root = Storage::disk($disk)->path('');
        $rel = trim(str_replace(['..', '\\'], ['.', '/'], (string)$request->path), '/');
        $relDs = str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $src = $root . DIRECTORY_SEPARATOR . $relDs;
        $realSrc = realpath($src) ?: $src;
        $realRoot = realpath($root) ?: $root;
        if (!str_starts_with($realSrc, $realRoot)) {
            return response()->json(['ok' => false, 'error' => 'Access denied'], 403);
        }
        $parent = dirname($src);
        $dst = $parent . DIRECTORY_SEPARATOR . $request->name;
        if (!file_exists($src)) {
            return response()->json(['ok' => false, 'error' => 'File/folder not found'], 404);
        }
        if (file_exists($dst)) {
            return response()->json(['ok' => false, 'error' => 'Target already exists'], 409);
        }
        if (!is_writable($parent)) {
            return response()->json(['ok' => false, 'error' => 'Directory is not writable'], 403);
        }
        try {
            if (!rename($src, $dst)) {
                throw new \Exception('Rename failed');
            }
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Rename failed: ' . $e->getMessage(),
                'src' => $src,
                'dst' => $dst,
            ], 422);
        }
        $newName = basename($dst);
        return response()->json(['ok' => true, 'name' => $newName]);
    }

    public function folder(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'regex:/^[A-Za-z0-9_\-\.]+$/'],
            'path' => ['nullable', 'string'],
        ]);
        // Get disk from config
        $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
        $root = Storage::disk($disk)->path('');
        $path = trim(str_replace(['..', '\\'], ['.', '/'], (string)$request->string('path')), '/');
        $dir = $root . DIRECTORY_SEPARATOR . ($path ? $path . DIRECTORY_SEPARATOR : '') . $request->name;
        $realDir = realpath($dir) ?: $dir;
        $realRoot = realpath($root) ?: $root;
        if (str_starts_with($realDir, $realRoot) && is_dir($dir)) {
            return response()->json(['ok' => false, 'error' => 'Folder already exists'], 409);
        }
        try {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception('Failed to create folder');
            }
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => 'Failed to create folder: ' . $e->getMessage()], 500);
        }
        return response()->json(['ok' => true]);
    }

    public function delete(Request $request)
    {
        \Log::info('Delete request received', ['request' => $request->all()]);
        try {
            $request->validate([
                'path' => ['required', 'string'],
            ]);
            // Get disk from config
            $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
            $root = Storage::disk($disk)->path('');
            $rel = trim(str_replace(['..', '\\'], ['.', '/'], (string)$request->path), '/');
            $relDs = str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $target = $root . DIRECTORY_SEPARATOR . $relDs;
            $realTarget = realpath($target) ?: $target;
            $realRoot = realpath($root) ?: $root;
            if (!str_starts_with($realTarget, $realRoot)) {
                \Log::error('Security violation', [
                    'real_target' => $realTarget,
                    'real_root' => $realRoot
                ]);
                return response()->json(['ok' => false, 'error' => 'Access denied'], 403);
            }
            if (!file_exists($target)) {
                \Log::error('Target not found', ['target' => $target]);
                return response()->json(['ok' => false, 'error' => 'File or directory not found'], 404);
            }
            if (!is_writable($target)) {
                \Log::error('Target not writable', ['target' => $target]);
                return response()->json(['ok' => false, 'error' => 'File or directory not writable'], 403);
            }
            if (is_dir($target)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                $success = true;
                foreach ($files as $file) {
                    $filePath = $file->getRealPath();
                    if ($file->isDir()) {
                        if (!rmdir($filePath)) {
                            $success = false;
                            break;
                        }
                    } else {
                        if (!unlink($filePath)) {
                            $success = false;
                            break;
                        }
                    }
                }
                if ($success && !rmdir($target)) {
                    $success = false;
                }
                if (!$success) {
                    throw new \Exception('Failed to delete directory');
                }
            } else {
                if (!unlink($target)) {
                    throw new \Exception('Failed to delete file');
                }
            }
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            \Log::error('File deletion failed: ' . $e->getMessage());
            return response()->json([
                'ok' => false,
                'error' => 'Failed to delete file or directory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve a file from the configured disk root.
     */
    public function serveFile(Request $request, $path)
    {
        // Get disk from config
        $disk = config('filament-tinymce-editor.fileAttachmentsDisk', 'public');
        $root = Storage::disk($disk)->path('');
        $filePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $realFilePath = realpath($filePath) ?: $filePath;
        $realRoot = realpath($root) ?: $root;
        if (!str_starts_with($realFilePath, $realRoot) || !is_file($filePath)) {
            abort(404, 'File not found');
        }
        return response()->file($filePath);
    }
}
