<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Factories\UserFactory;
use Primix\Forms\Form;
use Primix\Tables\Table;
use Tagixo\PageBuilder\Enums\MenuItemTargetType;
use Tagixo\PageBuilder\Models\Menu;
use Tagixo\PageBuilder\Services\MenuItemsTreePersister;
use Tagixo\Primix\Resources\MenuResource;
use Tagixo\Primix\Resources\PageResource;

/*
 * Menus: a list of links, not a document. The items are rows of their own, so the
 * page builder's persister reads and writes them and the form only holds the tree.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(UserFactory::new()->create());
});

function menuWithItems(): Menu
{
    $menu = Menu::create(['name' => 'Main', 'slug' => 'main']);

    app(MenuItemsTreePersister::class)->persist($menu, [
        [
            'label' => 'Home',
            'target_type' => 'page',
            'target_value' => 'home',
            'dropdown_type' => 'mega',
            'children' => [
                ['label' => 'About', 'target_type' => 'page', 'target_value' => 'about-us', 'children' => []],
            ],
        ],
        ['label' => 'Blog', 'target_type' => 'url', 'target_value' => 'https://example.test/blog', 'new_tab' => true, 'children' => []],
    ]);

    return $menu->refresh();
}

it('administers the menus, and counts what is in them', function () {
    $menu = menuWithItems();

    expect(Route::has('primix.admin.menus.index'))->toBeTrue()
        ->and(Route::has('primix.admin.menus.edit'))->toBeTrue()
        ->and(array_map(
            static fn ($column): string => $column->getName(),
            MenuResource::table(new Table)->getColumns(),
        ))->toBe(['name', 'slug', 'all_items_count', 'updated_at'])
        // Three items in total: the persister counts the sub-item too.
        ->and(MenuResource::getEloquentQuery()->find($menu->getKey())->all_items_count)->toBe(3);
});

it('reads the tree as the form wants it, page items pointing at their page', function () {
    $menu = menuWithItems();

    $items = MenuResource::itemsForForm(app(MenuItemsTreePersister::class)->toTree($menu));

    expect($items)->toHaveCount(2)
        ->and($items[0]['label'])->toBe('Home')
        ->and($items[0]['target_page'])->toBe('home')
        // A page item types nothing in the address field.
        ->and($items[0]['target_value'])->toBeNull()
        ->and($items[0]['dropdown_type'])->toBe('mega')
        ->and($items[0]['children'][0]['target_page'])->toBe('about-us')
        ->and($items[1]['target_value'])->toBe('https://example.test/blog')
        ->and($items[1]['target_page'])->toBeNull();
});

it('writes it back as one destination, and nothing the persister did not ask for', function () {
    $prepared = MenuResource::itemsForPersister([
        [
            'label' => 'Home',
            'target_type' => 'page',
            'target_page' => 'home',
            'target_value' => 'left over from another type',
            'dropdown_type' => '',
            'visible' => true,
            'children' => [
                ['label' => 'Contact', 'target_type' => 'anchor', 'target_value' => '#contact', 'children' => []],
            ],
        ],
    ]);

    expect($prepared[0]['target_value'])->toBe('home')
        ->and($prepared[0])->not->toHaveKey('target_page')
        // An empty dropdown is no dropdown.
        ->and($prepared[0]['dropdown_type'])->toBeNull()
        ->and($prepared[0]['children'][0]['target_value'])->toBe('#contact');
});

it('survives the round trip through the persister', function () {
    $menu = menuWithItems();
    $persister = app(MenuItemsTreePersister::class);

    $tree = $persister->toTree($menu);
    $persister->persist($menu, MenuResource::itemsForPersister(MenuResource::itemsForForm($tree)));

    expect($persister->toTree($menu->refresh()))->toBe($tree);
});

it('offers the destinations the page builder knows, pages by slug', function () {
    PageResource::createRecord(['title' => 'About us']);

    $fields = collect(MenuResource::form(new Form)->getComponents())->keyBy->getName();

    expect($fields->keys()->all())->toBe(['name', 'slug', 'description', 'css_class', 'items'])
        // The items never become a column of the menu.
        ->and($fields['items']->isDehydrated())->toBeFalse()
        ->and(MenuItemTargetType::options())->toHaveKeys(['url', 'page', 'route', 'anchor'])
        ->and(array_keys(MenuResource::pageOptions()))->toContain('about-us-1');
});

it('renders the menus screen and the editor of one', function () {
    $menu = menuWithItems();

    $this->get(MenuResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Main')
        ->assertSee('main');

    $this->get(MenuResource::getUrl('edit', ['record' => $menu->getKey()]))
        ->assertOk()
        ->assertSee('Home')
        ->assertSee('Blog');
});
