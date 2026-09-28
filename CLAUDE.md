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
3. **Records are written through the type** (`createRecord`, `updateRecord`,
   `metadataRules` on the resource), because `create()` decides slugs, initial
   structures and defaults that a raw `Model::create()` would leave empty.
4. No backward compatibility with `ccast/tagixo-primix`.
5. Reports in Italian; commit at the end of a milestone, after confirmation.

## Shape

| Piece | What it does |
| --- | --- |
| `TagixoPrimixPlugin` | Registers one resource per registered type; `only()`, `except()`, `resource()`, `navigationGroup()`, `icons()`. Calls `Tagixo::disableManagementApi()` in `register(Panel)` — the panel provider boots before the core's `booted()` callback loads the routes, which is the last moment the switch can be thrown. |
| `Resources\TagixoRecordResource` | The generic resource: model, slug, labels, icon, listing query, table, form, pages, builder and preview actions, plus record writing through the type. |
| `Resources\{Page,Popup,GlobalBlock,Form,Mail,Document,Slider}Resource` | Six lines each: Primix keys resources by class, so every type needs one. They name their type and nothing else. |
| `Resources\Pages\{CreateTagixoRecord,EditTagixoRecord}` | Shared by every resource (`Primix\Resources\Pages\Page::getResource()` falls back to the route's `_resource`). Creation and saving are handed to the type; the listing uses Primix's own `ListRecords`. |
| `Support\RecordFieldSchema` | `RecordField[]` → Primix columns and inputs. Status becomes a badge with the option's colour; a field the type marks `formOnly` never becomes a column; what `storeRules()` does not accept is hidden on the create screen. |

The builder itself is the core's page: the Build action links to
`/tagixo/builder/embed?type&id&back`, which survives the management switch.
Clicking a row in a listing opens it too.

## Tests

`composer test` (Pest + Testbench): a panel with the core, the page builder and
the mail builder — two builders on purpose, so the tests show the panel offering
what is installed and nothing more.
