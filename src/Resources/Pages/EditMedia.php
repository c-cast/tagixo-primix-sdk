<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Notifications\Notification;
use Primix\Resources\Pages\EditRecord;
use Tagixo\Core\MediaGallery\Services\MediaService;
use Tagixo\Primix\Resources\MediaResource;

/**
 * What an editor may change about a file. It goes through `MediaService`, which
 * keeps a folder a flat, path-safe label.
 */
class EditMedia extends EditRecord
{
    protected static ?string $resource = MediaResource::class;

    public function save(): void
    {
        $this->validate(
            $this->getFormValidationRules('form'),
            $this->getFormValidationMessages('form'),
            $this->getFormValidationAttributes('form'),
        );

        $form = $this->getForm('form');

        $data = $this->data;
        $form->dehydrateState($data);

        app(MediaService::class)->updateMetadata($this->record, $data);

        $this->record->refresh();
        $this->data = $form->fillWithRelationships(
            $this->mutateFormDataBeforeFill($this->record->toArray()),
            $this->record,
        );

        Notification::make()
            ->title(__('primix::panel.notifications.saved'))
            ->success()
            ->send();
    }
}
