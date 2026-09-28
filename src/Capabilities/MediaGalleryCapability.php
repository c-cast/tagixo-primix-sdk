<?php

namespace Tagixo\Primix\Capabilities;

use LiVue\Features\SupportAssets\AssetManager;
use LiVue\Features\SupportAssets\Js;
use Primix\Panel;
use Tagixo\Core\Support\TagixoAsset;
use Tagixo\Primix\Resources\MediaResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/**
 * The media library of the core, in the panel: a section to browse and edit it,
 * and the picker of the editor available to any Primix form
 * (`Forms\Fields\MediaPickerField`).
 *
 * The picker's own script is the core's, published to public/vendor/tagixo/core:
 * it registers the Vue component on the LiVue app of the panel, so no builder
 * bundle is needed for a form to pick an image.
 */
class MediaGalleryCapability implements Capability
{
    public function id(): string
    {
        return 'media-gallery';
    }

    public function available(): bool
    {
        return class_exists(AssetManager::class);
    }

    public function apply(Panel $panel, TagixoPrimixPlugin $plugin): void
    {
        if ($plugin->mediaLibraryEnabled()) {
            $panel->resources([...$panel->getResources(), MediaResource::class]);
        }

        app()->booted(static function (): void {
            app(AssetManager::class)->register([
                Js::make('tagixo-media-picker', TagixoAsset::url('core/media-picker.js', version: false))->module(),
            ], 'tagixo-primix');
        });
    }
}
