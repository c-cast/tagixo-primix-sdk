<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Actions\Action;
use Primix\Notifications\Notification;
use Primix\Resources\Pages\EditRecord;
use Tagixo\Primix\Resources\TagixoRecordResource;

/**
 * Metadata of a builder record. The structure is edited in the builder, which
 * this page links to; saving goes through the type, whose rules carry what only
 * it knows.
 */
class EditTagixoRecord extends EditRecord
{
    protected function getHeaderActions(): array
    {
        /** @var class-string<TagixoRecordResource> $resource */
        $resource = $this->resolveResource();

        return [
            Action::make('build')
                ->label(__('Build'))
                ->icon('pi pi-pencil')
                ->url($resource::builderUrl($this->record)),
            ...parent::getHeaderActions(),
        ];
    }

    public function save(): void
    {
        /** @var class-string<TagixoRecordResource> $resource */
        $resource = $this->resolveResource();

        $this->validate(
            [...$this->getFormValidationRules('form'), ...$resource::metadataRules($this->record)],
            $this->getFormValidationMessages('form'),
            $this->getFormValidationAttributes('form'),
        );

        $form = $this->getForm('form');

        $data = $this->data;
        $form->dehydrateState($data);

        $resource::updateRecord($this->record, $data);

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
