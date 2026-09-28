<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Primix\Tables\Table;
use Tagixo\Primix\Resources\DocumentResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/*
 * Documents: the generic resource plus the one thing that is theirs — the printed
 * file. What a type adds for itself lives in its own six-line class.
 */

uses(RefreshDatabase::class);

it('administers documents once that builder is installed', function () {
    expect(app(TagixoPrimixPlugin::class)->resolveResources())->toHaveKey('documents')
        ->and(DocumentResource::getRecordTitleAttribute())->toBe('name');
});

it('offers the printed file, next to the common actions', function () {
    $actions = array_map(
        static fn ($action): string => $action->getName(),
        DocumentResource::table(new Table)->getActions(),
    );

    expect($actions)->toBe(['download', 'build', 'preview', 'edit', 'delete']);
});

it('points the download at the route of the document builder', function () {
    $document = DocumentResource::createRecord(['name' => 'Invoice']);
    $download = collect(DocumentResource::table(new Table)->getActions())
        ->firstWhere(fn ($action) => $action->getName() === 'download');

    expect($download->record($document)->getUrl())
        ->toBe(route('tagixo.documents.download', ['id' => $document->getKey()]))
        ->and($download->shouldOpenUrlInNewTab())->toBeTrue();
});

it('lists the paper and the orientation the type declares as columns', function () {
    $columns = array_map(
        static fn ($column): string => $column->getName(),
        DocumentResource::table(new Table)->getColumns(),
    );

    expect($columns)->toBe(['name', 'slug', 'status', 'updated_at', 'paper_size', 'orientation']);
});
