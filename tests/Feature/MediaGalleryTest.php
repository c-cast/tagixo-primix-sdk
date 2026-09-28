<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use LiVue\Features\SupportAssets\AssetManager;
use LiVue\Features\SupportFileUploads\TemporaryUploadedFile;
use Orchestra\Testbench\Factories\UserFactory;
use Primix\Forms\Form;
use Primix\Tables\Table;
use Tagixo\Core\MediaGallery\Models\Media;
use Tagixo\Core\MediaGallery\Services\MediaService;
use Tagixo\Primix\Forms\Fields\MediaPickerField;
use Tagixo\Primix\Resources\MediaResource;
use Tagixo\Primix\Resources\Pages\UploadMedia;

/*
 * The media library of the core, in the panel: a section to browse and edit it,
 * and the picker of the editor available to any form.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(UserFactory::new()->create());
});

function uploadedImage(string $name = 'photo.jpg', ?string $folder = null): Media
{
    return app(MediaService::class)->upload(
        UploadedFile::fake()->image($name, 40, 30),
        folder: $folder,
    );
}

it('gives the panel a media section, and the routes of its screens', function () {
    expect(MediaResource::getModel())->toBe(Media::class)
        ->and(MediaResource::getSlug())->toBe('media')
        ->and(Route::has('primix.admin.media.index'))->toBeTrue()
        ->and(Route::has('primix.admin.media.create'))->toBeTrue()
        ->and(Route::has('primix.admin.media.edit'))->toBeTrue();
});

it('lists the files with what a librarian needs', function () {
    uploadedImage('hero.jpg');

    $columns = array_map(
        static fn ($column): string => $column->getName(),
        MediaResource::table(new Table)->getColumns(),
    );

    expect($columns)->toBe(['thumbnail_url', 'filename', 'type', 'formatted_size', 'folder', 'created_at']);

    $this->get(MediaResource::getUrl('index'))
        ->assertOk()
        ->assertSee('hero')
        ->assertSee('image');
});

it('shows the originals only, not the crops of a file', function () {
    $original = uploadedImage('hero.jpg');
    $crop = Media::create([
        'parent_id' => $original->getKey(),
        'filename' => 'hero-crop.jpg',
        'original_filename' => 'hero.jpg',
        'path' => 'media/hero-crop.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'extension' => 'jpg',
        'size' => 10,
    ]);

    $listed = MediaResource::getEloquentQuery()->pluck('id')->all();

    expect($listed)->toContain($original->getKey())
        ->and($listed)->not->toContain($crop->getKey());
});

it('edits only the metadata of a file, through the service', function () {
    $media = uploadedImage('hero.jpg');

    $inputs = array_map(
        static fn ($component): string => $component->getName(),
        MediaResource::form(new Form)->getComponents(),
    );

    expect($inputs)->toBe(['title', 'alt_text', 'description', 'folder']);

    app(MediaService::class)->updateMetadata($media, [
        'alt_text' => 'A hero',
        // The service keeps a folder a flat, path-safe label.
        'folder' => '../../etc',
    ]);

    expect($media->refresh()->alt_text)->toBe('A hero')
        ->and($media->folder)->not->toContain('/');
});

it('offers the picker of the editor to any form', function () {
    $field = MediaPickerField::make('cover')->images()->maxFiles(3)->multiple();

    expect($field->getView())->toBe('tagixo-primix::forms.fields.media-picker')
        ->and($field->isMultiple())->toBeTrue()
        ->and($field->getMaxFiles())->toBe(3)
        ->and($field->getAcceptedTypes())->toBe(['image/*'])
        ->and($field->toVueProps())->toHaveKeys(['multiple', 'maxFiles', 'acceptedTypes', 'statePath']);
});

it('renders that field through the core component, so the dialog is the same one', function () {
    $html = MediaPickerField::make('cover')->images()->toHtml();

    expect($html)->toContain('<tgx-media-picker-field')
        ->toContain('accepted-types')
        ->toContain('image/*');
});

it('opens the upload screen with the two things an upload needs', function () {
    $this->get(MediaResource::getUrl('create'))
        ->assertOk()
        ->assertSee('Folder')
        ->assertSee('Files');
});

it('hands a temporary upload to the service, whatever disk it sat on', function () {
    Storage::fake('local');
    Storage::disk('local')->put('livue-tmp/photo.jpg', UploadedFile::fake()->image('photo.jpg', 20, 10)->get());

    $temporary = new TemporaryUploadedFile(
        'livue-tmp/photo.jpg',
        'photo.jpg',
        'image/jpeg',
        Storage::disk('local')->size('livue-tmp/photo.jpg'),
        'local',
    );

    $media = app(MediaService::class)->upload(
        UploadMedia::asUploadedFile($temporary),
        folder: 'press',
    );

    expect($media->exists)->toBeTrue()
        ->and($media->original_filename)->toBe('photo.jpg')
        ->and($media->folder)->toBe('press')
        ->and($media->type)->toBe('image')
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue();
});

it('puts the picker script on the LiVue app of the panel', function () {
    $src = app(AssetManager::class)
        ->getScriptSrc('tagixo-media-picker', 'tagixo-primix');

    expect($src)->toContain('vendor/tagixo/core/media-picker.js')
        // No version query: a module entry with one forks the ES module graph.
        ->and($src)->not->toContain('?v=');
});
