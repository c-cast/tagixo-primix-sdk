<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Factories\UserFactory;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\Primix\Resources\FormResource;

/*
 * The preview of an app form is a real Primix form: what the builder's own
 * Preview opens once the panel is there.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(UserFactory::new()->create());
});

it('renders the form the editor drew, as a Primix form', function () {
    $components = [
        ['id' => 't', 'type' => 'form-tabs', 'parent_id' => null, 'order' => 0, 'props' => ['content' => ['label' => 'Sections']]],
        ['id' => 't1', 'type' => 'form-tab', 'parent_id' => 't', 'order' => 0, 'props' => ['content' => ['label' => 'Contact']]],
        ['id' => 'a', 'type' => 'text-input', 'parent_id' => 't1', 'order' => 0, 'props' => ['content' => ['name' => 'email', 'label' => 'Your e-mail']]],
    ];

    $form = FormSchema::create([
        'title' => 'Contact us',
        'slug' => 'contact-us',
        'status' => 'published',
        'content' => ['body' => [], 'components' => $components],
        'fields' => $components,
    ]);

    $this->get(FormResource::getUrl('preview-app', ['record' => $form->getKey()]))
        ->assertOk()
        ->assertSee('Contact us — Preview')
        // The native tab, and the field inside it.
        ->assertSee('Contact')
        ->assertSee('Your e-mail')
        ->assertSee('data-tgx-field="email"', false);
});

it('answers 404 for a form that does not exist', function () {
    $this->get(FormResource::getUrl('preview-app', ['record' => 9999]))->assertNotFound();
});
