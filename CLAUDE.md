# tagixo/primix — Primix SDK for Tagixo

One SDK for every builder. Rewritten on the Tagixo monorepo (`tagixo/core` +
builder packages); the legacy SDK for the single `ccast/tagixo` plugin is the
`main` branch of this repository, kept only as reference.

## Rules

1. **Nothing here knows what a page or a mail is.** Everything comes from
   `Tagixo\Core\Contracts\BuilderTypeContract`: model, listing query, fields,
   rules, editor mount, preview. A builder released tomorrow is administered
   without touching this package.
2. **Which builders are installed is asked of the `BuilderTypeRegistry`**, never
   of the autoloader. An application can drop a type from `tagixo.builder_types`
   or replace its handler, and the panel follows that. `class_exists()` is for
   capabilities that are not record types (media gallery, form target, …).
3. **`resources()` on a Panel replaces the list**, so the plugin merges the
   panel's own resources (listed or discovered) with its own.
4. **Records are written through the type** (`createRecord`, `updateRecord`,
   `metadataRules` on the resource), because `create()` decides slugs, initial
   structures and defaults that a raw `Model::create()` would leave empty.
5. No backward compatibility with `ccast/tagixo-primix`.
6. Reports in Italian; commit at the end of a milestone, after confirmation.

## Shape

| Piece | What it does |
| --- | --- |
| `TagixoPrimixPlugin` | Registers one resource per registered type; `only()`, `except()`, `resource()`, `navigationGroup()`, `icons()`. Calls `Tagixo::disableManagementApi()` in `register(Panel)` — the panel provider boots before the core's `booted()` callback loads the routes, which is the last moment the switch can be thrown. |
| `Resources\TagixoRecordResource` | The generic resource: model, slug, labels, icon, listing query, table, form, pages, builder and preview actions, plus record writing through the type. |
| `Resources\{Page,Popup,GlobalBlock,Form,Mail,Document,Slider}Resource` | Six lines each: Primix keys resources by class, so every type needs one. They name their type, and add only what is theirs — the document's download, the form's app preview. |
| `Resources\Pages\{CreateTagixoRecord,EditTagixoRecord}` | Shared by every resource (`Primix\Resources\Pages\Page::getResource()` falls back to the route's `_resource`). Creation and saving are handed to the type; the listing uses Primix's own `ListRecords`. |
| `Capabilities\Capability` + `TagixoPrimixPlugin::resolveCapabilities()` | What a package adds beyond its record types. `available()` asks the container (`app()->bound(...)`), never the autoloader, so a package that is present but not booted turns nothing on. A panel drops one with `withoutCapability('form-builder')` and adds its own with `capability(...)`. |
| `Capabilities\FormBuilderCapability` | Enables the `app` form target (or locks it), gives every form module the `table` tab (`PrimixTablePropType`, plus the boolean, date and file variants), hides `sizing`, and points the builder's app-form Preview at `FormResource`'s own page. |
| `Forms\PrimixForm{Fields,Columns,Filters,Styles}` + `Support\FormSchemaToPrimix` | A form drawn in the builder, used in the panel: fields as Primix components, columns and filters from each field's Table tab, and the element styles as CSS scoped to `data-tgx-field`. The converter maps the builder's own ids — the layout modules carry a `form-` prefix (`form-grid`, `form-tabs`, `form-tab`, …) — and drops the submit button, which a panel form provides itself. |
| `Capabilities\MediaGalleryCapability` | The media library of the core in the panel (`MediaResource` + its list, upload and metadata pages, off with `withMediaGallery(false)`) and the editor's picker for any form (`Forms\Fields\MediaPickerField`, rendering the core's `TgxMediaPickerField` from `media-picker.js`, registered on the LiVue app without a version query — a query on a module entry forks the ES module graph). Uploads go through `MediaService`; the `TemporaryUploadedFile` of LiVue is read off its disk and handed over as a real upload. |
| `Capabilities\PageBuilderCapability` | `Resources\LayoutResource` (what a layout applies to: `LayoutConditionService::getConditionLabel()` writes the summary, and the condition repeater reads `$get('type')` to show the target a condition needs) and `Pages\SiteSettings` (the five keys `SiteSettings::KEYS` declares; `store()` holds the writing so it can be tested without mounting the page). A layout's content is not edited here — the section toggler of the editor saves it from whichever page matched. |
| `Resources\Pages\PreviewAppForm` | That form as a real Primix form, tabs and wizard native. |
| `Support\RecordFieldSchema` | `RecordField[]` → Primix columns and inputs. Status becomes a badge with the option's colour; a field the type marks `formOnly` never becomes a column; what `storeRules()` does not accept is hidden on the create screen. |

The builder itself is the core's page: the Build action links to
`/tagixo/builder/embed?type&id&back`, which survives the management switch.
Clicking a row in a listing opens it too.

## Tests

`composer test` (Pest + Testbench). Two installations, because that is the whole
point of the SDK:

- `tests/Feature`, `tests/Unit` — core + page builder + mail builder. Two
  builders on purpose: the panel must offer what is installed and nothing more,
  and a capability of an absent package must leave no trace.
- `tests/WithFormBuilder`, `tests/WithDocumentBuilder` — the same plus that
  builder, booted with it from the start so its migrations run (one TestCase each,
  wired in `tests/Pest.php`). Adding a provider through `TestCase::$extraProviders`
  + `refreshApplication()` works for anything that does not touch the database —
  the in-memory sqlite does not survive a refresh, so a test that reconfigures the
  plugin and needs rows cannot have both.
