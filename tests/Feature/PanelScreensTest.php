<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Factories\UserFactory;
use Tagixo\Primix\Resources\PageResource;

/*
 * The screens actually render: a listing with its columns and actions, and the
 * metadata form of a record.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(UserFactory::new()->create());
});

it('renders the listing of a type', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    $this->get(PageResource::getUrl('index'))
        ->assertOk()
        ->assertSee('About us')
        ->assertSee('Pages')
        // The status badge carries the label of the option the type declared.
        ->assertSee('Draft')
        // And a row leads to the builder of the core.
        ->assertSee('/tagixo/builder/embed', false)
        ->assertSee('type=pages', false);
});

it('renders the metadata form of a record', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    $this->get(PageResource::getUrl('edit', ['record' => $page->getKey()]))
        ->assertOk()
        ->assertSee('About us')
        // Editable fields of the type, the slug included (hidden only on create).
        ->assertSee('Slug')
        ->assertSee($page->slug);
});
