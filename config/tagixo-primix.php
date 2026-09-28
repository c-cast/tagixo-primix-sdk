<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Admin resource class per Tagixo record type. The SDK already maps the types
    | of its builders; add a line here (or call
    | `TagixoPrimixPlugin::make()->resource('recipes', RecipeResource::class)`)
    | for a type of your own, or to replace one of ours with a subclass.
    |
    | A type whose package is not installed is skipped: the panel follows the
    | BuilderTypeRegistry, so it shows exactly the builders you bought.
    |
    */

    'resources' => [
        // 'recipes' => App\Primix\Resources\RecipeResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    |
    | What the panel gains from a package beyond its record types: the form
    | builder brings the `app` form target, the table tab of each field and the
    | preview of an app form. Each capability checks whether its package is
    | installed, so listing one costs nothing when it is not.
    |
    | Add your own here, or with `TagixoPrimixPlugin::make()->capability(...)`;
    | drop one with `->withoutCapability('form-builder')`.
    |
    */

    'capabilities' => [
        // App\Primix\Capabilities\MyCapability::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Group every Tagixo resource under one navigation group (null keeps them at
    | the top level), and the icon of each one. `default` covers a type without
    | its own icon, your own types included.
    |
    */

    'navigation_group' => null,

    'icons' => [
        'default' => 'pi pi-th-large',
        'pages' => 'pi pi-file',
        'popups' => 'pi pi-window-maximize',
        'global-blocks' => 'pi pi-clone',
        'forms' => 'pi pi-list-check',
        'mails' => 'pi pi-envelope',
        'documents' => 'pi pi-file-pdf',
        'sliders' => 'pi pi-images',
        'media' => 'pi pi-images',
        'layouts' => 'pi pi-table',
        'menus' => 'pi pi-bars',
        'site-settings' => 'pi pi-cog',
    ],

];
