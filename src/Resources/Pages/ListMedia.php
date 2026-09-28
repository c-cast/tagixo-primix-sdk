<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Actions\Action;
use Primix\Resources\Pages\ListRecords;
use Tagixo\Primix\Resources\MediaResource;

/**
 * The library. "New" here means uploading, which is what the header action says.
 */
class ListMedia extends ListRecords
{
    protected static ?string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('Upload'))
                ->icon('pi pi-upload')
                ->url(MediaResource::getUrl('create')),
        ];
    }
}
