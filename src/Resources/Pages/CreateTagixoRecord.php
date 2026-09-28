<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Notifications\Notification;
use Primix\Resources\Pages\CreateRecord;
use Tagixo\Primix\Resources\TagixoRecordResource;

/**
 * Creation of a builder record: the resource hands it to the type, and the new
 * record opens in the editor, which is where an empty draft is worth anything.
 */
class CreateTagixoRecord extends CreateRecord
{
    public function create(): void
    {
        $this->validate(
            $this->getFormValidationRules('form'),
            $this->getFormValidationMessages('form'),
            $this->getFormValidationAttributes('form'),
        );

        /** @var class-string<TagixoRecordResource> $resource */
        $resource = $this->resolveResource();

        $data = $this->data;
        $this->getForm()->dehydrateState($data);

        $record = $resource::createRecord($data);

        $this->afterCreate($record);

        Notification::make()
            ->title(__('primix::panel.notifications.created'))
            ->success()
            ->send();

        $this->redirect($resource::builderUrl($record), navigate: true);
    }
}
