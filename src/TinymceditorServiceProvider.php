<?php

namespace Amirhellboy\FilamentTinymceEditor;

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
                        ->copyAndRegisterServiceProviderInApp();
                    //->askToStarRepoOnGitHub($this->getAssetPackageName());
                }
            );
    }

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        $tinyVersion = config('filament-tinymce-editor.version.tiny', '8.0.2');
        $tiny_licence_key = config('filament-tinymce-editor.version.licence_key', 'no-api-key');
        $tiny_languages = Tiny::getLanguages();

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

        $provider = config('filament-tinymce-editor.provider', 'cloud');

        $mainJs = 'https://cdn.jsdelivr.net/npm/tinymce@' . $tinyVersion . '/tinymce.js';

        if ($tiny_licence_key != 'no-api-key') {
            $mainJs = 'https://cdn.tiny.cloud/1/' . $tiny_licence_key . '/tinymce/' . $tinyVersion . '/tinymce.min.js';
        }

        FilamentAsset::register([
            Js::make('tinymce', $mainJs),
            //Js::make('tinymce-editor', __DIR__ . '/../resources/js/tinymce-editor.js'),
            ...$languages,
        ], package: $this->getAssetPackageName());
    }

    protected function getAssetPackageName(): ?string
    {
        return 'amirhellboy/filament-tinymce-editor';
    }
}
