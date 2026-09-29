<?php

namespace Tagixo\Primix\Resources;

use Illuminate\Database\Eloquent\Builder;
use Primix\Forms\Components\Fields\Field;
use Primix\Forms\Components\Fields\Repeater;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Fields\Textarea;
use Primix\Forms\Components\Fields\TextInput;
use Primix\Forms\Components\Fields\Toggle;
use Primix\Forms\Components\Utilities\Get;
use Primix\Forms\Form;
use Primix\Resources\Actions\CreateAction;
use Primix\Resources\Actions\DeleteAction;
use Primix\Resources\Actions\DeleteBulkAction;
use Primix\Resources\Actions\EditAction;
use Primix\Resources\Resource;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Table;
use Tagixo\PageBuilder\Enums\MenuItemTargetType;
use Tagixo\PageBuilder\Models\Menu;
use Tagixo\PageBuilder\Models\Page;

/**
 * Menus of the page builder, and the items they are made of. A menu is not a
 * builder type — it is a list of links, not a document — so it gets its own
 * screens; the items are written by the page builder's own
 * `MenuItemsTreePersister`, which owns the order and the parents.
 *
 * The form offers two levels, which is what a menu with dropdowns needs; the
 * persister itself is not limited to two.
 */
class MenuResource extends Resource
{
    protected static ?string $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return config('tagixo-page-builder.models.menu', Menu::class);
    }

    public static function getSlug(): string
    {
        return 'menus';
    }

    public static function getNavigationLabel(): string
    {
        return __('Menus');
    }

    public static function getModelLabel(): string
    {
        return __('Menu');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Menus');
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? config('tagixo-primix.icons.menus', 'pi pi-bars');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? config('tagixo-primix.navigation_group');
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->withCount('allItems')->orderBy('name');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('Slug'))->searchable()->sortable(),
                TextColumn::make('all_items_count')->label(__('Items')),
                TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            TextInput::make('slug')->label(__('Slug'))->required()->maxLength(255)
                ->helperText(__('How a page asks for this menu.')),
            Textarea::make('description')->label(__('Description')),
            TextInput::make('css_class')->label(__('CSS class'))->maxLength(255),
            static::itemsField(),
        ]);
    }

    /**
     * The items, as the persister reads and writes them. Never dehydrated: they
     * are rows of their own, not a column of the menu.
     */
    public static function itemsField(): Repeater
    {
        return Repeater::make('items')
            ->label(__('Items'))
            ->dehydrated(false)
            ->hiddenOn('create')
            ->addActionLabel(__('Add an item'))
            ->itemLabel(__('Item'))
            ->collapsible()
            ->schema([
                ...static::itemFields(),
                Select::make('dropdown_type')
                    ->label(__('Dropdown'))
                    ->options([
                        '' => __('Normal'),
                        'mega' => __('Mega menu'),
                    ]),
                Repeater::make('children')
                    ->label(__('Sub-items'))
                    ->addActionLabel(__('Add a sub-item'))
                    ->itemLabel(__('Sub-item'))
                    ->collapsible()
                    ->schema(static::itemFields()),
            ]);
    }

    /**
     * What one item is: a label, where it points, and how it looks.
     *
     * @return list<Field>
     */
    protected static function itemFields(): array
    {
        return [
            TextInput::make('label')->label(__('Label'))->required()->maxLength(255),
            Select::make('target_type')
                ->label(__('Points to'))
                ->options(MenuItemTargetType::options())
                ->default(MenuItemTargetType::Page->value)
                ->required(),
            // Two fields, one destination: a page is picked from a list, anything
            // else is typed. They are merged back into `target_value` on save,
            // because two inputs cannot share one state path.
            Select::make('target_page')
                ->label(__('Page'))
                ->options(static fn (): array => static::pageOptions())
                ->visible(static fn (Get $get): bool => $get('target_type') === MenuItemTargetType::Page->value),
            TextInput::make('target_value')
                ->label(__('Address'))
                ->maxLength(2048)
                ->helperText(__('A URL, a route name or an anchor, depending on what it points to.'))
                ->visible(static fn (Get $get): bool => $get('target_type') !== MenuItemTargetType::Page->value),
            Toggle::make('new_tab')->label(__('Open in a new tab')),
            Toggle::make('visible')->label(__('Visible'))->default(true),
            TextInput::make('icon')->label(__('Icon'))->maxLength(255),
            TextInput::make('css_class')->label(__('CSS class'))->maxLength(255),
        ];
    }

    /**
     * The tree as the form wants it: a page item shows its page in a list of its
     * own, so the destination is split in two.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, array<string, mixed>>
     */
    public static function itemsForForm(array $tree): array
    {
        return array_map(static function (array $item): array {
            $isPage = ($item['target_type'] ?? null) === MenuItemTargetType::Page->value;

            return [
                ...$item,
                'dropdown_type' => $item['dropdown_type'] ?? '',
                'target_page' => $isPage ? ($item['target_value'] ?? null) : null,
                'target_value' => $isPage ? null : ($item['target_value'] ?? null),
                'children' => static::itemsForForm(is_array($item['children'] ?? null) ? $item['children'] : []),
            ];
        }, $tree);
    }

    /**
     * And back: one destination again, and nothing the persister did not ask for.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function itemsForPersister(array $items): array
    {
        $prepared = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['target_type'] ?? MenuItemTargetType::Url->value);
            $target = $type === MenuItemTargetType::Page->value
                ? ($item['target_page'] ?? null)
                : ($item['target_value'] ?? null);

            $prepared[] = [
                'label' => $item['label'] ?? null,
                'target_type' => $type,
                'target_value' => $target === '' ? null : $target,
                'new_tab' => (bool) ($item['new_tab'] ?? false),
                'visible' => (bool) ($item['visible'] ?? true),
                'icon' => $item['icon'] ?? null,
                'css_class' => $item['css_class'] ?? null,
                'dropdown_type' => ($item['dropdown_type'] ?? '') === '' ? null : $item['dropdown_type'],
                'children' => static::itemsForPersister(is_array($item['children'] ?? null) ? $item['children'] : []),
            ];
        }

        return $prepared;
    }

    /**
     * @return array<int|string, string>
     */
    public static function pageOptions(): array
    {
        return config('tagixo-page-builder.models.page', Page::class)::query()
            ->orderBy('title')
            ->pluck('title', 'slug')
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
