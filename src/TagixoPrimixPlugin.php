<?php

namespace Tagixo\Primix;

use Primix\Contracts\Plugin;
use Primix\Panel;
use Tagixo\Core\BuilderTypeRegistry;
use Tagixo\Core\Tagixo;
use Tagixo\Primix\Capabilities\Capability;
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

    /**
     * Capabilities of the packages that are not record types. Each one decides
     * whether it applies; a panel drops one with `withoutCapability('form-builder')`.
     *
     * @var list<class-string<Capability>>
     */
    protected array $capabilities = [
        Capabilities\FormBuilderCapability::class,
    ];

    /**
     * @var list<string>
     */
    protected array $withoutCapabilities = [];

    protected bool $appForms = true;

    protected ?string $formTarget = null;

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

        foreach ($this->resolveCapabilities() as $capability) {
            $capability->apply($panel, $this);
        }
    }

    public function boot(Panel $panel): void {}

    /**
     * The capabilities this panel gets: declared, not excluded, and available —
     * which each one answers for itself, asking the container.
     *
     * @return list<Capability>
     */
    public function resolveCapabilities(): array
    {
        $configured = (array) config('tagixo-primix.capabilities', []);
        $classes = array_values(array_unique([...$this->capabilities, ...array_filter($configured, 'is_string')]));

        $resolved = [];

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var Capability $capability */
            $capability = app($class);

            if (in_array($capability->id(), $this->withoutCapabilities, true) || ! $capability->available()) {
                continue;
            }

            $resolved[] = $capability;
        }

        return $resolved;
    }

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

    /**
     * Leave a capability out, by id ('form-builder'), keeping the rest.
     */
    public function withoutCapability(string ...$ids): static
    {
        $this->withoutCapabilities = [...$this->withoutCapabilities, ...$ids];

        return $this;
    }

    /**
     * Add a capability of your own (or of another package).
     *
     * @param  class-string<Capability>  $capability
     */
    public function capability(string $capability): static
    {
        $this->capabilities[] = $capability;

        return $this;
    }

    /**
     * Whether the builder offers the `app` form target in this installation: the
     * interactive layouts (tabs, wizard, groups) a panel can render and a public
     * page cannot. On by default, since that is why this SDK exists.
     */
    public function withAppForms(bool $enabled = true): static
    {
        $this->appForms = $enabled;

        return $this;
    }

    public function appFormsEnabled(): bool
    {
        return $this->appForms;
    }

    /**
     * Lock every form of this installation to one target ('universal' or 'app'),
     * hiding the choice from the editor.
     */
    public function lockFormTarget(string $target): static
    {
        $this->formTarget = $target;

        return $this;
    }

    public function lockedFormTarget(): ?string
    {
        return $this->formTarget;
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
