<?php

use Illuminate\Database\Eloquent\Model;
use Primix\Forms\Components\Fields\Field;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Fields\TextInput;
use Primix\Forms\Form;
use Primix\Tables\Columns\Column;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Table;
use Tagixo\Core\Enums\PublishStatus;
use Tagixo\MailBuilder\Models\MailTemplate;
use Tagixo\PageBuilder\Models\Page;
use Tagixo\Primix\Resources\MailResource;
use Tagixo\Primix\Resources\PageResource;
use Tagixo\Primix\Resources\TagixoRecordResource;

/*
 * The resource is generic: model, label, columns, inputs and actions are what
 * the builder type declares. Two types with different shapes prove it — a page
 * is titled `title` and has a public preview, a mail is titled `name` and adds
 * a subject.
 */

/**
 * @param  class-string<TagixoRecordResource>  $resource
 * @return array<string, Column>
 */
function columnsOf(string $resource): array
{
    $columns = [];

    foreach ($resource::table(new Table)->getColumns() as $column) {
        $columns[$column->getName()] = $column;
    }

    return $columns;
}

/**
 * @param  class-string<TagixoRecordResource>  $resource
 * @return array<string, Field>
 */
function inputsOf(string $resource): array
{
    $inputs = [];

    foreach ($resource::form(new Form)->getComponents() as $component) {
        $inputs[$component->getName()] = $component;
    }

    return $inputs;
}

it('takes the model, the labels and the title attribute from the type', function () {
    expect(PageResource::getModel())->toBe(Page::class)
        ->and(PageResource::getSlug())->toBe('pages')
        ->and(PageResource::getNavigationLabel())->toBe('Pages')
        ->and(PageResource::getPluralModelLabel())->toBe('Pages')
        ->and(PageResource::getModelLabel())->toBe('Page')
        ->and(PageResource::getRecordTitleAttribute())->toBe('title')
        ->and(MailResource::getModel())->toBe(MailTemplate::class)
        ->and(MailResource::getNavigationLabel())->toBe('Mails')
        // A mail is named by its own column, as MailType declares.
        ->and(MailResource::getRecordTitleAttribute())->toBe('name');
});

it('lists the fields the type lists, with the kind each one asked for', function () {
    $columns = columnsOf(PageResource::class);

    expect(array_keys($columns))->toBe(['title', 'slug', 'status', 'updated_at'])
        ->and($columns['title'])->toBeInstanceOf(TextColumn::class)
        ->and($columns['title']->getLabel())->toBe('Title')
        ->and($columns['title']->isSortable())->toBeTrue()
        ->and($columns['title']->isSearchable())->toBeTrue()
        ->and($columns['status']->isBadge())->toBeTrue();
});

it('adds what a mail declares beyond the common fields', function () {
    $columns = columnsOf(MailResource::class);

    expect(array_keys($columns))->toBe(['name', 'slug', 'status', 'updated_at', 'subject'])
        // Declared form-only by MailType: an input, never a column.
        ->and($columns)->not->toHaveKey('preheader')
        ->and(inputsOf(MailResource::class))->toHaveKey('preheader');
});

it('shows a status as a badge with the colour and the label of its option', function () {
    $status = columnsOf(PageResource::class)['status'];
    $published = Page::make(['status' => PublishStatus::Published]);
    $draft = Page::make(['status' => PublishStatus::Draft]);

    // getState() resolves the state the colour closure then reads.
    expect($status->getState($published))->toBe('Published')
        ->and($status->getColor())->toBe('success')
        ->and($status->getState($draft))->toBe('Draft')
        ->and($status->getColor())->toBe('gray');
});

it('turns the editable fields into inputs of the right kind', function () {
    $inputs = inputsOf(MailResource::class);

    expect(array_keys($inputs))->toBe(['name', 'slug', 'status', 'subject', 'preheader'])
        ->and($inputs['name'])->toBeInstanceOf(TextInput::class)
        ->and($inputs['name']->isRequired())->toBeTrue()
        ->and($inputs['status'])->toBeInstanceOf(Select::class)
        ->and($inputs['status']->getOptions())->toBe([
            'draft' => 'Draft',
            'published' => 'Published',
            'scheduled' => 'Scheduled',
            'archived' => 'Archived',
        ])
        ->and($inputs['preheader']->getHelperText())->not->toBeNull();
});

it('asks on create only for what the type accepts there', function () {
    $inputs = inputsOf(PageResource::class);

    $onCreate = static fn (string $name): bool => $inputs[$name]->operation('create')->isHidden();
    $onEdit = static fn (string $name): bool => $inputs[$name]->operation('edit')->isHidden();

    // PageType::create() reads the title and decides slug and status itself.
    expect($onCreate('title'))->toBeFalse()
        ->and($onCreate('slug'))->toBeTrue()
        ->and($onCreate('status'))->toBeTrue()
        ->and($onEdit('slug'))->toBeFalse()
        ->and($onEdit('status'))->toBeFalse();
});

it('opens the editor of the core, and comes back to the listing', function () {
    $page = Page::make();
    $page->id = 12;

    $url = PageResource::builderUrl($page);

    expect($url)->toContain('/tagixo/builder/embed')
        ->toContain('type=pages')
        ->toContain('id=12')
        ->toContain(urlencode(PageResource::getUrl('index')));
});

it('offers a preview only where the type has one', function () {
    $actions = static fn (string $resource): array => array_map(
        static fn ($action): string => $action->getName(),
        $resource::table(new Table)->getActions(),
    );

    expect($actions(PageResource::class))->toBe(['build', 'preview', 'edit', 'delete'])
        ->and(PageResource::builderType()->previewable())->toBeTrue();
});

it('queries what the type queries', function () {
    Page::query()->getConnection()->statement('select 1');

    expect(PageResource::getEloquentQuery()->getModel())->toBeInstanceOf(Page::class)
        ->and(PageResource::getEloquentQuery()->toSql())
        ->toBe(PageResource::builderType()->indexQuery(request())->toSql());
});
