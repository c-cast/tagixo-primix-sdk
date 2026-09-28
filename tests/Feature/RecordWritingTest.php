<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tagixo\Core\Enums\PublishStatus;
use Tagixo\MailBuilder\Models\MailTemplate;
use Tagixo\PageBuilder\Models\Page;
use Tagixo\Primix\Resources\MailResource;
use Tagixo\Primix\Resources\PageResource;

/*
 * Records are written through their type, never straight into the model: that is
 * what makes turning the core's own CRUD off safe.
 */

uses(RefreshDatabase::class);

it('creates a record the way its type does', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->title)->toBe('About us')
        // PageType decides these: a raw Page::create() would leave them empty.
        ->and($page->slug)->toBe('about-us-'.$page->getKey())
        ->and($page->status)->toBe(PublishStatus::Draft)
        ->and($page->content)->toBe(['components' => [], 'body' => []]);
});

it('ignores on creation what the type does not accept there', function () {
    $page = PageResource::createRecord(['title' => 'Contact', 'slug' => 'chosen-by-hand', 'status' => 'published']);

    expect($page->slug)->toBe('contact-'.$page->getKey())
        ->and($page->status)->toBe(PublishStatus::Draft);
});

it('creates each type through its own handler', function () {
    $mail = MailResource::createRecord(['name' => 'Welcome']);

    expect($mail)->toBeInstanceOf(MailTemplate::class)
        ->and($mail->name)->toBe('Welcome')
        // MailType names the slug after the record, not after the subject line:
        // each type creates its records its own way, and the panel asks nothing.
        ->and($mail->slug)->toBe('mail-'.$mail->getKey());
});

it('applies the metadata through the type', function () {
    $page = PageResource::createRecord(['title' => 'Draft']);

    PageResource::updateRecord($page, ['title' => 'Home', 'slug' => 'home', 'status' => 'published']);

    expect($page->refresh()->title)->toBe('Home')
        ->and($page->slug)->toBe('home')
        ->and($page->status)->toBe(PublishStatus::Published);
});

it('writes nothing the type did not ask about', function () {
    $page = PageResource::createRecord(['title' => 'Home']);

    PageResource::updateRecord($page, ['title' => 'Home', 'content' => ['components' => [['id' => 'x']]]]);

    // The structure belongs to the builder, not to a metadata form.
    expect($page->refresh()->content)->toBe(['components' => [], 'body' => []]);
});

it('validates the metadata with the rules of the type', function () {
    $taken = PageResource::createRecord(['title' => 'Taken']);
    $page = PageResource::createRecord(['title' => 'Mine']);

    $rules = PageResource::metadataRules($page);

    expect(array_keys($rules))->toBe(['data.title', 'data.slug', 'data.status'])
        ->and(Validator::make(['data' => ['slug' => $taken->slug]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['data' => ['slug' => 'free-slug']], $rules)->fails())->toBeFalse()
        ->and(Validator::make(['data' => ['status' => 'nonsense']], $rules)->fails())->toBeTrue();
});
