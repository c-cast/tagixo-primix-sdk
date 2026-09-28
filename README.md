# tagixo/primix

Primix SDK for [Tagixo](https://github.com/c-cast). One package for every
builder: each record type the installed builders register becomes an admin
resource, and the builders you did not buy leave no trace in the panel.

## Installation

```bash
composer require tagixo/primix
```

Enable the plugin in your panel provider:

```php
use Tagixo\Primix\TagixoPrimixPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->path('admin')
        ->plugin(TagixoPrimixPlugin::make());
}
```

That is the whole setup. Registering the plugin also turns the core's own record
CRUD off (`Tagixo::disableManagementApi()`): the panel manages records now. What
the editor needs — `/tagixo/builder/*`, including the mount page, the record
payload and the save — stays on, and the resources link to it.

## What you get

One resource per registered record type: pages, popups, global blocks, forms,
mails, documents, sliders — whichever of them are installed. Each one lists,
creates, edits the metadata of and deletes its records, opens the builder, and
offers a preview when the type issues preview URLs.

Nothing in here describes a page or a mail: the resource asks the type
(`Tagixo\Core\Contracts\BuilderTypeContract`) for its model, its listing query,
its fields and its rules. A builder released tomorrow is administered the day it
is installed.

## What each builder adds

Beyond its records, a builder can bring the panel features of its own. They are
capabilities: each one checks whether its package is there, so nothing has to be
configured and nothing breaks when a builder is not installed.

**Form builder.** The panel is where the interactive layouts of a form — tabs,
wizard, groups — are native, so the `app` form target is enabled: the editor
offers them, and its Preview of an app form opens the panel's own page, which
renders it as a real Primix form. Every field also gains a **Table** tab, where
it says how its answers look in a listing.

Use a form the editor drew anywhere in the panel:

```php
use Tagixo\Primix\Forms\PrimixFormColumns;
use Tagixo\Primix\Forms\PrimixFormFields;
use Tagixo\Primix\Forms\PrimixFormFilters;
use Tagixo\Primix\Forms\PrimixFormStyles;

$form->schema(PrimixFormFields::from('contact-us'));          // its fields, as Primix ones

$table->columns(PrimixFormColumns::from('contact-us'))        // the columns the fields asked for
      ->filters(PrimixFormFilters::from('contact-us'));       // and the filters they allow

PrimixFormStyles::scriptFrom('contact-us');                    // the look the editor gave them
```

**Media library.** The panel gets a section to browse the library, edit what a
file says about itself (title, alt text, description, folder) and delete it;
uploading goes through the core's `MediaService`, so thumbnails, variants and the
folder rules stay its business. Any Primix form can also use the picker of the
editor — the same dialog, browsing, upload and external URL included:

```php
use Tagixo\Primix\Forms\Fields\MediaPickerField;

MediaPickerField::make('cover')->images();
MediaPickerField::make('gallery')->images()->multiple()->maxFiles(6);
```

**Document builder.** The listing of the documents offers the printed file, from
the builder's own download route.

**Page builder.** Two screens that are not record types: the **layouts** a page
wears — administered by what they apply to, since their content is edited from a
page's header and footer — and the **site settings** the public site reads (name,
default title and description, favicon, custom CSS). Turn either off with
`withLayouts(false)` / `withSiteSettings(false)`.

## Tuning it

```php
TagixoPrimixPlugin::make()
    ->navigationGroup('Content')          // one group for every Tagixo resource
    ->except(['global-blocks'])            // manage those only from inside the builder
    ->only(['pages', 'forms'])             // or the other way round
    ->icons(['pages' => 'pi pi-file'])
    ->resource('pages', MyPageResource::class)    // your own screens for a type
    ->lockFormTarget('app')                       // every form is a panel form
    ->withAppForms(false)                         // or: leave the builder to the universal palette
    ->withMediaGallery(false)                     // no library section (the picker stays)
    ->withLayouts(false)                          // no layouts section
    ->withSiteSettings(false)                     // no site settings screen
    ->withoutCapability('form-builder')           // nothing from that package at all
    ->capability(MyCapability::class);
```

`config/tagixo-primix.php` (publish it with
`php artisan vendor:publish --tag=tagixo-primix-config`) holds the same
defaults, plus the resource class of a record type of your own:

```php
'resources' => [
    'recipes' => App\Primix\Resources\RecipeResource::class,
],
```

A resource of your own extends `Tagixo\Primix\Resources\TagixoRecordResource`
and names its type; override `table()`, `form()` or the labels to go further.

```php
class RecipeResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'recipes';
}
```

## Requirements

- PHP 8.2+
- `primix/primix` ^0.7.17
- `tagixo/core` ^0.1, plus at least one builder

The editor routes of the core use `config('tagixo.route_middleware')`
(`['web', 'auth']` by default). A panel behind another guard should set that
config accordingly, so the same people who reach the panel reach the builder.

## Development

The package is developed against the Tagixo monorepo next to it: the `path`
repository in `composer.json` points at `../../Projects/tagixo/packages/*`.
Tests boot a Primix panel with the core, the page builder and the mail builder:

```bash
composer test
```

## License

MIT
