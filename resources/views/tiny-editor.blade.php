@php
    $tinyVersion = config('filament-tinymce-editor.version.tiny', '8.0.2');
    $tinyLicence = config('filament-tinymce-editor.version.licence_key', 'no-api-key');
    $provider = config('filament-tinymce-editor.provider', 'cloud');
    if ($provider === 'local') {
        $__tinyMainSrc = config('filament-tinymce-editor.local.main_js', '/vendor/tinymce/tinymce.min.js');
    } else {
        $__tinyMainSrc = $tinyLicence !== 'no-api-key'
            ? ('https://cdn.tiny.cloud/1/' . $tinyLicence . '/tinymce/' . $tinyVersion . '/tinymce.min.js')
            : ('https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.js');
    }
@endphp

@once
    <style>
        /* 修复在 Repeater 内 toolbar 粘性定位导致的抖动/错乱 */
        .fi-fo-repeater-item .tox .tox-editor-header {
            position: unset !important;
            top: auto !important;
        }
    </style>
@endonce

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    class="relative z-0"
>
    <div
        x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            initialized: false,
            initTiny() {
                if (this.initialized) return;
                const self = this;
                const ensureTinyLoaded = () => {
                    if (window.tinymce) return Promise.resolve(window.tinymce);
                    if (window.__loadingTinyMCE) return window.__loadingTinyMCE;
                    window.__loadingTinyMCE = new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = @js($__tinyMainSrc);
                        s.async = true;
                        s.referrerPolicy = 'origin';
                        s.onload = () => resolve(window.tinymce);
                        s.onerror = () => reject(new Error('Failed to load TinyMCE'));
                        document.head.appendChild(s);
                    });
                    return window.__loadingTinyMCE;
                };

                ensureTinyLoaded().then((t) => {
                    self.initialized = true;
                    $nextTick(() => {
                        t.createEditor('tiny-editor-{{ $getId() }}', {
                            target: $refs.tinymce,
                            toolbar_sticky: {{ $getToolbarSticky() ? 'true' : 'false' }},
                            toolbar_sticky_offset: {{ $getToolbarStickyOffset() }},
                            toolbar_mode: '{{ $getToolbarMode() }}',
                            toolbar_location: '{{ $getToolbarLocation() }}',
                            plugins: '{{ $getPlugins() }}',
                            external_plugins: {{ $getExternalPlugins() }},
                            toolbar: '{{ $getToolbar() }}',
                            language: '{{ $getInterfaceLanguage() }}',
                            language_url: '{{ $getLanguageURL($getInterfaceLanguage()) }}',
                            directionality: '{{ $getDirection() }}',
                            branding: false,
                            @if ($getHeight()) height: @js($getHeight()), @endif
                            @if ($getMaxHeight()) max_height: @js($getMaxHeight()), @endif
                            @if ($getMinHeight()) min_height: @js($getMinHeight()), @endif
                            @if ($getWidth()) width: @js($getWidth()), @endif
                            @if ($getTinyMaxWidth()) max_width: @js($getTinyMaxWidth()), @endif
                            @if ($getMinWidth()) min_width: @js($getMinWidth()), @endif
                            resize: @js($getResize()),

                            @if (!filament()->hasDarkModeForced() && $darkMode() == 'media') skin: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide'),
                            content_css: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default'),
                            @elseif(!filament()->hasDarkModeForced() && $darkMode() == 'class')
                            skin: (document.querySelector('html').getAttribute('class').includes('dark') ? 'oxide-dark' : 'oxide'),
                            content_css: (document.querySelector('html').getAttribute('class').includes('dark') ? 'dark' : 'default'),
                            @elseif(filament()->hasDarkModeForced() || $darkMode() == 'force')
                            skin: 'oxide-dark',
                            content_css: 'dark',
                            @elseif(!filament()->hasDarkModeForced() && $darkMode() == false)
                            skin: 'oxide',
                            content_css: 'default',
                            @else
                            skin: ((localStorage.getItem('theme') ?? 'system') == 'dark' || (localStorage.getItem('theme') === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) ? 'oxide-dark' : 'oxide',
                            content_css: ((localStorage.getItem('theme') ?? 'system') == 'dark' || (localStorage.getItem('theme') === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) ? 'dark' : 'default',
                            @endif
                            menubar: {{ $getShowMenuBar() ? 'true' : 'false' }},
                            relative_urls: {{ $getRelativeUrls() ? 'true' : 'false' }},
                            remove_script_host: {{ $getRemoveScriptHost() ? 'true' : 'false' }},
                            convert_urls: {{ $getConvertUrls() ? 'true' : 'false' }},
                            font_size_formats: '{{ $getFontSizes() }}',
                            fontfamily: '{{ $getFontFamilies() }}',
                            locale: '{{ app()->getLocale() }}',
                            disabled: {{ $isDisabled() ? "true" : "false"}},
                            placeholder: @js($getPlaceholder()),
                            custom_configs: {{ $getCustomConfigs() }},
                            promotion: false,
                            license_key: '{{ $getLicenseKey() }}',
                            image_advtab: @js($getimageAdvtab()),
                            file_picker_callback: (cb, value, meta) => {
                                let fmUrl = '{{ route('tinymc-editor.file-manager') }}';
                                const width = {{ $getFileManagerWidth() }};
                                const height = {{ $getFileManagerHeight() }};
                                const title = 'File Manager';
                                const win = t.activeEditor.windowManager.openUrl({
                                    title,
                                    url: fmUrl,
                                    width,
                                    height,
                                    onMessage: (api, data) => {
                                        if (data?.url) {
                                            cb(data.url);
                                            api.close();
                                        }
                                    },
                                });
                            },

                            setup: function(editor) {
                                if(!window.tinySettingsCopy) {
                                    window.tinySettingsCopy = [];
                                }

                                if (
                                    editor &&
                                    editor.settings &&
                                    typeof editor.settings.id !== 'undefined'
                                ) {
                                    if (!window.tinySettingsCopy.some(obj => obj.id === editor.settings.id)) {
                                        window.tinySettingsCopy.push(editor.settings);
                                    }
                                }

                                editor.on('blur', function(e) {
                                    self.state = editor.getContent();
                                });

                                editor.on('init', function(e) {
                                    if (self.state != null) {
                                        editor.setContent(self.state);
                                    }
                                });

                                editor.on('OpenWindow', function(e) {
                                    var target = e.target.container.closest('.fi-modal');
                                    if (target) target.setAttribute('x-trap.noscroll', 'false');

                                    target = e.target.container.closest('.jetstream-modal');
                                    if (target) {
                                        target.children[1].setAttribute('x-trap.inert.noscroll', 'false');
                                    }
                                });

                                editor.on('CloseWindow', function(e) {
                                    var target = e.target.container.closest('.fi-modal');
                                    if (target) target.setAttribute('x-trap.noscroll', 'isOpen');

                                    target = e.target.container.closest('.jetstream-modal');
                                    if (target) {
                                        target.children[1].setAttribute('x-trap.inert.noscroll', 'show');
                                    }
                                });

                                function putCursorToEnd() {
                                    editor.selection.select(editor.getBody(), true);
                                    editor.selection.collapse(false);
                                }

                                self.$watch('state', function(newstate) {
                                    if (editor.container && newstate !== editor.getContent()) {
                                        editor.resetContent(newstate || '');
                                        putCursorToEnd();
                                    }
                                });
                            },


                        }).render();
                    });
                }).catch(() => {/* noop */});

                if (!window.tinyMceInitialized) {
                    window.tinyMceInitialized = true;
                    $nextTick(() => {
                        Livewire.hook('morph.removed', (el, component) => {
                            if (el.el.nodeName === 'INPUT' && el.el.getAttribute('x-ref') === 'tinymce') {
                                if (window.tinymce) {
                                    tinymce.get(el.el.id)?.remove();
                                }
                            }
                        });
                    });
                }
            }
        }"
        x-init="(() => {
            // 可见时立即初始化；否则用 IntersectionObserver 按需懒加载
            const isVisible = () => !!(
                $el && $el.offsetParent !== null &&
                $el.offsetWidth > 0 && $el.offsetHeight > 0
            );

            const tryInit = () => {
                if (!initialized) {
                    (window.requestIdleCallback || ((cb) => setTimeout(cb, 0)))(() => initTiny());
                }
            };

            if (isVisible()) {
                tryInit();
            } else if ('IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries, obs) => {
                    entries.forEach((e) => {
                        if (e.isIntersecting) {
                            obs.unobserve(e.target);
                            tryInit();
                        }
                    });
                }, { root: null, rootMargin: '200px', threshold: 0 });
                io.observe($el);
            }

            // 用户交互兜底
            $el.addEventListener('pointerdown', () => { tryInit(); }, { once: true, passive: true });
        })()"
        x-cloak
        wire:ignore
    >
        <input
            id="tiny-editor-{{ $getId() }}"
            type="hidden"
            x-ref="tinymce"
            placeholder="{{ $getPlaceholder() }}"
        >
    </div>
</x-dynamic-component>
