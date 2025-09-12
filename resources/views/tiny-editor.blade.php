<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    class="relative z-0"
>
    <div
        x-data="{ state: $wire.entangle('{{ $getStatePath() }}'), initialized: false }"
        x-init="(() => {
            $nextTick(() => {
                tinymce.createEditor('tiny-editor-{{ $getId() }}', {
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
                        const win = tinymce.activeEditor.windowManager.openUrl({
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
                            state = editor.getContent()
                        })

                        editor.on('init', function(e) {
                            if (state != null) {
                                editor.setContent(state)
                            }
                        })

                        editor.on('OpenWindow', function(e) {

                            target = e.target.container.closest('.fi-modal')
                            if (target) target.setAttribute('x-trap.noscroll', 'false')

                            target = e.target.container.closest('.jetstream-modal')
                            if (target) {
                                targetDiv = target.children[1]
                                targetDiv.setAttribute('x-trap.inert.noscroll', 'false')
                            }
                        })

                        editor.on('CloseWindow', function(e) {
                            target = e.target.container.closest('.fi-modal')
                            if (target) target.setAttribute('x-trap.noscroll', 'isOpen')

                            target = e.target.container.closest('.jetstream-modal')
                            if (target) {
                                targetDiv = target.children[1]
                                targetDiv.setAttribute('x-trap.inert.noscroll', 'show')
                            }
                        })

                        function putCursorToEnd() {
                            editor.selection.select(editor.getBody(), true);
                            editor.selection.collapse(false);
                        }

                        $watch('state', function(newstate) {
                            // unfortunately livewire doesn't provide a way to 'unwatch' so this listener sticks
                            // around even after this component is torn down. Which means that we need to check
                            // that editor.container exists. If it doesn't exist we do nothing because that means
                            // the editor was removed from the DOM
                            if (editor.container && newstate !== editor.getContent()) {
                                editor.resetContent(newstate || '');
                                putCursorToEnd();
                            }
                        });
                    },


                }).render();
            });

            // We initialize here because if the component is first loaded from within a modal DOMContentLoaded
            // won't fire and if we want to register a Livewire.hook listener Livewire.hook isn't available from
            // inside the once body
            if (!window.tinyMceInitialized) {
                window.tinyMceInitialized = true;
                $nextTick(() => {
                    Livewire.hook('morph.removed', (el, component) => {
                        if (el.el.nodeName === 'INPUT' && el.el.getAttribute('x-ref') === 'tinymce') {
                            tinymce.get(el.el.id)?.remove();
                        }
                    });
                });
            }
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
