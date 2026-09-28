<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Forms\Form;
use Primix\Forms\HasForms;
use Primix\Resources\Pages\Page;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\Primix\Resources\FormResource;
use Tagixo\Primix\Support\FormSchemaToPrimix;

/**
 * A form designed in the builder, shown as a real Primix form — native tabs and
 * wizard included. This is the previewer the form builder delegates to for an
 * `app`-target form (`FormBuilder::registerAppFormPreviewer`), instead of its own
 * HTML preview, because an app form is only itself inside the panel.
 */
class PreviewAppForm extends Page
{
    use HasForms;

    protected static ?string $resource = FormResource::class;

    public ?FormSchema $record = null;

    /** Form state path. */
    public array $data = [];

    /** Mapped Primix definitions, kept across LiVue requests. */
    public array $previewDefinitions = [];

    protected ?string $title = null;

    public function mount(int|string $record): void
    {
        $this->record = FormSchema::findOrFail($record);
        $this->title = trim((string) ($this->record->title ?? '')).' — '.__('Preview');

        $this->previewDefinitions = app(FormSchemaToPrimix::class)->fromForm($this->record);
    }

    /**
     * No submit action: the preview is for looking at the layout and trying the
     * behaviour, not for collecting anything.
     */
    public function form(Form $form): Form
    {
        return $form
            ->fromSchema($this->previewDefinitions)
            ->statePath('data');
    }

    protected function render(): string
    {
        return 'tagixo-primix::pages.preview-app-form';
    }
}
