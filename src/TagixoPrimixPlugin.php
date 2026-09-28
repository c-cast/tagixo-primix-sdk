<?php

namespace Tagixo\Primix;

use Primix\Contracts\Plugin;
use Primix\Panel;
use Tagixo\Core\BuilderTypeRegistry;
use Tagixo\Core\Tagixo;
use Tagixo\Primix\Resources\DocumentResource;
use Tagixo\Primix\Resources\FormResource;
use Tagixo\Primix\Resources\GlobalBlockResource;
use Tagixo\Primix\Resources\MailResource;
use Tagixo\Primix\Resources\PageResource;
use Tagixo\Primix\Resources\PopupResource;
use Tagixo\Primix\Resources\SliderResource;
use Tagixo\Primix\Resources\TagixoRecordResource;

/**
 * Brings Tagixo into a Primix panel: one admin resource per record type the
 * installed builders register, and nothing for the ones they don't.
 *
 * Which packages are installed is asked of the BuilderTypeRegistry, not of the
 * autoloader: an application can drop a type from `tagixo.builder_types` or
 * replace its handler, and the panel has to follow that, not the file system.
 */
class TagixoPrimixPlugin implements Plugin
{
    /**
     * Resource class per builder type key. An application adds its own type, or
     * replaces one of these, with `->resource('pages', MyPageResource::class)`.
     *
     * @var array<string, class-string<TagixoRecordResource>>
     */
    protected array $resources = [
        'pages' => PageResource::class,
        'popups' => PopupResource::class,
        'global-blocks' => GlobalBlockResource::class,
        'forms' => FormResource::class,
        'mails' => MailResource::class,
        'documents' => DocumentResource::class,
        'sliders' => SliderResource::class,
    ];

    /**
     * @var list<string>|null
     */
    protected ?array $only = null;

    /**
     * @var list<string>
     */
    protected array $except = [];

    protected ?string $navigationGroup = null;

    /**
     * @var array<string, string>
     */
    protected array $icons = [];

    public function getId(): string
    {
        return 'tagixo';
    }

    /**
     * Resolved from the container so the panel, the resources and the
     * application all see the same configured plugin.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Called while the panel provider boots — before the core loads its routes
     * in an `app()->booted()` callback, which is the last moment the management
     * API can still be switched off.
     */
    public function register(Panel $panel): void
    {
        Tagixo::disableManagementApi();

        $panel->resources(array_values($this->resolveResources()));
    }

    public function boot(Panel $panel): void {}

    /**
     * The resources this panel gets: a registered type with a resource class,
     * minus what the panel excluded.
     *
     * @return array<string, class-string<TagixoRecordResource>>
     */
    public function resolveResources(): array
    {
        $registry = app(BuilderTypeRegistry::class);
        $configured = (array) config('tagixo-primix.resources', []);
        $resources = [...$this->resources, ...array_filter($configured, 'is_string')];

        $resolved = [];

        foreach ($resources as $type => $resource) {
            if (! $registry->has($type) || ! $this->wanted($type) || ! class_exists($resource)) {
                continue;
            }

            $resolved[$type] = $resource;
        }

        return $resolved;
    }

    /**
     * Administer only these types.
     *
     * @param  list<string>  $types
     */
    public function only(array $types): static
    {
        $this->only = $types;

        return $this;
    }

    /**
     * Administer every type but these: the records stay, their admin section goes.
     *
     * @param  list<string>  $types
     */
    public function except(array $types): static
    {
        $this->except = $types;

        return $this;
    }

    /**
     * Use this resource class for that type, in place of (or in addition to) the
     * ones the SDK ships.
     *
     * @param  class-string<TagixoRecordResource>  $resource
     */
    public function resource(string $type, string $resource): static
    {
        $this->resources[$type] = $resource;

        return $this;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * @param  array<string, string>  $icons  Type key => icon class.
     */
    public function icons(array $icons): static
    {
        $this->icons = [...$this->icons, ...$icons];

        return $this;
    }

    public function iconFor(string $type): ?string
    {
        return $this->icons[$type]
            ?? config("tagixo-primix.icons.{$type}")
            ?? config('tagixo-primix.icons.default');
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('tagixo-primix.navigation_group');
    }

    protected function wanted(string $type): bool
    {
        if (in_array($type, $this->except, true)) {
            return false;
        }

        return $this->only === null || in_array($type, $this->only, true);
    }
}
