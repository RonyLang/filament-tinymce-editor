<?php

return [
    // 资源提供方式: cloud(官方 tiny.cloud), cdn(jsDelivr), local(本地静态文件)
    'provider' => env('TINYMCE_PROVIDER', 'cloud'),

    'version' => [
        'tiny' => '8.0.2',
        'language' => [
            'version' => '25.8.4',
            'package' => 'langs8',
        ],
        'licence_key' => env('TINY_LICENSE_KEY', 'no-api-key'),
    ],
    // 'direction' => 'rtl',

    /**
     * change darkMode: 'auto'|'force'|'class'|'media'|false|'custom'
     */
    'darkMode' => 'auto',

    'profiles' => [
        'default' => [
            'plugins' => 'accordion autoresize codesample directionality advlist link image lists preview pagebreak searchreplace wordcount code fullscreen insertdatetime media table emoticons',
            'toolbar' => 'undo redo removeformat | fontfamily fontsize fontsizeinput font_size_formats styles | bold italic underline | rtl ltr | alignjustify alignleft aligncenter alignright | numlist bullist outdent indent | forecolor backcolor | blockquote table toc hr | image link media codesample emoticons | wordcount fullscreen',
        ],

        'simple' => [
            'plugins' => 'autoresize directionality emoticons link wordcount',
            'toolbar' => 'removeformat | bold italic | rtl ltr | numlist bullist | link emoticons',
        ],

        'minimal' => [
            'plugins' => 'link wordcount',
            'toolbar' => 'bold italic link numlist bullist',
        ],

        'full' => [
            'plugins' => 'accordion autoresize codesample directionality advlist autolink link image lists charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media table emoticons help',
            'toolbar' => 'undo redo removeformat | fontfamily fontsize fontsizeinput font_size_formats styles | bold italic underline | rtl ltr | alignjustify alignright aligncenter alignleft | numlist bullist outdent indent accordion | forecolor backcolor | blockquote table toc hr | image link anchor media codesample emoticons | visualblocks print preview wordcount fullscreen help',
        ],
    ],

    /**
     * this option will load optional language file based on you app locale
     * example:
     * languages => [
     *      'fa' => 'https://cdn.jsdelivr.net/npm/tinymce-i18n@25.8.4/langs7/fa.min.js',
     *      'es' => 'https://cdn.jsdelivr.net/npm/tinymce-i18n@25.8.4/langs7/es.min.js',
     *      'ja' => asset('assets/ja.min.js')
     * ]
     */
    'languages' => [],

    /**
     * 当 provider=local 时，配置本地 TinyMCE 主文件路径。
     * 例: 'local' => ['main_js' => asset('vendor/tinymce/tinymce.min.js')]
     * 也可使用相对/绝对 URL，如 '/vendor/tinymce/tinymce.min.js'
     */
    'local' => [
        'main_js' => '/vendor/tinymce/tinymce.min.js',
    ],

    'extra' => [
        'toolbar' => [
            // 'fontsize' => '10px 12px 13px 14px 16px 18px 20px',
            // 'fontfamily' => 'Tahoma=tahoma,arial,helvetica,sans-serif;',
        ]
    ],

    /**
     * Optional external file manager integration for TinyMCE's file picker.
     *
     * If enabled, the editor will open the configured URL inside TinyMCE's
     * window manager and expect a postMessage containing an object with a
     * `url` property to be sent back from the file manager window.
     */
    'file_manager' => [
        'url' => '/file-manager00',
        'title' => 'File Manager',
        'width' => 1000,
        'height' => 400,
    ],

    // Disk used for file attachments and file manager root
    'fileAttachmentsDisk' => env('TINYMCE_FILE_ATTACHMENTS_DISK', 'public'),

    // Allowed upload types and max size (in kilobytes)
    'fileAttachmentsTypes' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'media' => ['mp4', 'mp3', 'webm', 'ogg'],
        'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
    ],
    'fileAttachmentsMaxSize' => [
        // type => size in KB
        'image' => 10048, // 2 MB
        'media' => 10240, // 10 MB
        'file' => 5120, // 5 MB
    ],
];
