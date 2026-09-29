<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Factories\UserFactory;
use Primix\Forms\Form;
use Primix\Tables\Table;
use Tagixo\Core\Facades\Tagixo;
use Tagixo\PageBuilder\Facades\PageBuilder;
use Tagixo\PageBuilder\Models\Layout;
use Tagixo\Primix\Pages\ThemeBuilder;
use Tagixo\Primix\Resources\LayoutResource;

/*
 * The Theme Builder: every template with its three zones. A header and a footer
 * belong to the template and open it; a body belongs to a page, and for a
 * template scoped to a model that page is created on the way to the editor.
 */

uses(RefreshDatabase::class);

class ThemeArticle extends Model
{
    protected $table = 'tgx_test_theme_articles';

    protected $fillable = ['title'];
}

beforeEach(function () {
    Schema::create('tgx_test_theme_articles', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });

    Tagixo::registerModels(['articles' => ['class' => ThemeArticle::class, 'label' => 'Articles']]);
    PageBuilder::setRouteFor(ThemeArticle::class, 'articles');

    $this->actingAs(UserFactory::new()->create());
});

it('carries the Theme Builder while the page builder is installed', function () {
    expect(Route::has('primix.admin.theme-builder'))->toBeTrue()
        ->and(Route::has('tagixo-primix.templates.body'))->toBeTrue();
});

it('shows the three zones of a template, and where each one is edited', function () {
    $layout = Layout::create(['name' => 'Shop', 'conditions' => [['type' => 'all_pages']]]);

    $zones = collect(app(ThemeBuilder::class)->zones($layout))->keyBy('scope');

    expect($zones->keys()->all())->toBe(['header', 'body', 'footer'])
        // A header is the template's own document.
        ->and($zones['header']['url'])->toContain('type=layouts')
        ->and($zones['header']['url'])->toContain('scope=header')
        ->and($zones['header']['configured'])->toBeFalse()
        ->and($zones['footer']['url'])->toContain('scope=footer')
        // The body of an ordinary template belongs to each page.
        ->and($zones['body']['editable'])->toBeFalse()
        ->and($zones['body']['url'])->toBeNull()
        ->and($zones['body']['description'])->toContain('own body');
});

it('says a zone is built once it holds something', function () {
    $layout = Layout::create([
        'name' => 'Shop',
        'header_content' => ['components' => [['id' => 'h']], 'body' => []],
        'header_rendered_html' => '<header>Shop</header>',
    ]);

    $zones = collect(app(ThemeBuilder::class)->zones($layout))->keyBy('scope');

    expect($zones['header']['configured'])->toBeTrue()
        ->and($zones['footer']['configured'])->toBeFalse();
});

it('sends the body of a model template through the route that creates its page', function () {
    $layout = Layout::create([
        'name' => 'Articles',
        'conditions' => [['type' => 'model_archive', 'model' => 'articles']],
    ]);

    $body = collect(app(ThemeBuilder::class)->zones($layout))->keyBy('scope')['body'];

    expect($body['editable'])->toBeTrue()
        ->and($body['url'])->toBe(route('tagixo-primix.templates.body', ['layout' => $layout->getKey()]))
        ->and($body['configured'])->toBeFalse();

    // Following it creates the archive page of that model and opens its builder.
    $page = PageBuilder::findRoutePagesForModel('articles')['archive'];
    expect($page)->toBeNull();

    $this->get($body['url'])->assertRedirect();

    $page = PageBuilder::findRoutePagesForModel('articles')['archive'];

    expect($page)->not->toBeNull()
        ->and($this->get($body['url'])->headers->get('location'))
        ->toContain('type=pages')
        ->toContain('id='.$page->getKey());
});

it('sends an ordinary template back to the Theme Builder instead of inventing a page', function () {
    $layout = Layout::create(['name' => 'Everything', 'conditions' => [['type' => 'all_pages']]]);

    $this->get(route('tagixo-primix.templates.body', ['layout' => $layout->getKey()]))
        ->assertRedirect(ThemeBuilder::getUrl());
});

it('reads the templates global first, and says what each claims', function () {
    Layout::create(['name' => 'Zebra', 'conditions' => [['type' => 'homepage']]]);
    Layout::create(['name' => 'Global', 'is_global' => true]);

    $page = app(ThemeBuilder::class);
    $templates = $page->templates();

    expect($templates->pluck('name')->all())->toBe(['Global', 'Zebra'])
        ->and($page->conditionsSummary($templates[0]))->toBe('Everything else')
        ->and($page->conditionsSummary($templates[1]))->toBe('Homepage');
});

it('renders the screen with the templates and their zones', function () {
    Layout::create(['name' => 'Shop', 'conditions' => [['type' => 'homepage']]]);

    $this->get(ThemeBuilder::getUrl())
        ->assertOk()
        ->assertSee('Theme Builder')
        ->assertSee('Shop')
        ->assertSee('Homepage')
        ->assertSee('Header')
        ->assertSee('Body')
        ->assertSee('Footer');
});

it('opens one editor per document of a layout, and keeps the rest of the actions', function () {
    $actions = array_map(
        static fn ($action): string => $action->getName(),
        LayoutResource::table(new Table)->getActions(),
    );

    expect($actions)->toBe(['header', 'footer', 'edit', 'delete'])
        ->and(LayoutResource::builderUrl(Layout::create(['name' => 'Shop']), 'footer'))
        ->toContain('scope=footer');
});

it('administers a layout with its conditions on top of the declared fields', function () {
    $fields = array_map(
        static fn ($component): string => $component->getName(),
        LayoutResource::form(new Form)->getComponents(),
    );

    // name and is_global come from LayoutType::fields(); the conditions are the
    // resource's own, because only a layout has them.
    expect($fields)->toBe(['name', 'is_global', 'conditions']);
});
