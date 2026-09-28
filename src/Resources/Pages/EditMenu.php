<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Notifications\Notification;
use Primix\Resources\Pages\EditRecord;
use Tagixo\PageBuilder\Services\MenuItemsTreePersister;
use Tagixo\Primix\Resources\MenuResource;

/**
 * The menu and its items. The items are rows of their own, so they are read and
 * written by the page builder's `MenuItemsTreePersister`, which owns their order
 * and their parents; the form only ever holds the tree.
 */
class EditMenu extends EditRecord
{
    protected static ?string $resource = MenuResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = MenuResource::itemsForForm(
            app(MenuItemsTreePersister::class)->toTree($this->record),
        );

        return $data;
    }

    public function save(): void
    {
        $this->validate(
            $this->getFormValidationRules('form'),
            $this->getFormValidationMessages('form'),
            $this->getFormValidationAttributes('form'),
        );

        $form = $this->getForm('form');

        // The repeater is not dehydrated: the tree is read from the form state.
        $items = is_array($this->data['items'] ?? null) ? $this->data['items'] : [];

        $data = $this->data;
        $form->dehydrateState($data);

        $this->record->update($data);

        app(MenuItemsTreePersister::class)->persist($this->record, MenuResource::itemsForPersister($items));

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
