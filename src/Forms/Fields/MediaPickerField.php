<?php

namespace Tagixo\Primix\Forms\Fields;

use Closure;
use Primix\Forms\Components\Fields\Field;

/**
 * Pick from the Tagixo media library in any Primix form. The dialog itself is the
 * core's (`media-picker.js`, which registers `TgxMediaPickerField` on the LiVue
 * app), so browsing, uploading and external URLs behave exactly as in the editor.
 *
 * The state is what the picker returns: `{id, url, thumbnail_url, filename,
 * alt_text, width, height}`, or a list of those when multiple.
 */
class MediaPickerField extends Field
{
    protected bool|Closure $isMultiple = false;

    protected int|Closure|null $maxFiles = null;

    protected array|Closure $acceptedTypes = [];

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function maxFiles(int|Closure|null $count): static
    {
        $this->maxFiles = $count;

        return $this;
    }

    /**
     * MIME types the picker offers, e.g. `['image/*']` or `['video/*']` — the
     * latter also turns the external URL tab into a video one.
     *
     * @param  array<int, string>|Closure  $types
     */
    public function acceptedTypes(array|Closure $types): static
    {
        $this->acceptedTypes = $types;

        return $this;
    }

    public function images(): static
    {
        return $this->acceptedTypes(['image/*']);
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getMaxFiles(): ?int
    {
        return $this->evaluate($this->maxFiles);
    }

    /**
     * @return array<int, string>
     */
    public function getAcceptedTypes(): array
    {
        return (array) $this->evaluate($this->acceptedTypes);
    }

    public function getView(): string
    {
        return 'tagixo-primix::forms.fields.media-picker';
    }

    public function toVueProps(): array
    {
        return array_merge(parent::toVueProps(), [
            'multiple' => $this->isMultiple(),
            'maxFiles' => $this->getMaxFiles(),
            'acceptedTypes' => $this->getAcceptedTypes(),
        ]);
    }
}
