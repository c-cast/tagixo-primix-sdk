<?php

namespace Tagixo\Primix\Tables\Columns;

use Closure;
use Primix\Tables\Columns\Column;

/**
 * What {@see \Tagixo\Primix\Forms\Fields\MediaPickerField} picked, shown in a
 * listing: the thumbnail of an image, an icon for anything else, and nothing at
 * all — or the column's placeholder — when the record holds no media.
 *
 * It reads the state the picker writes (`{url, thumbnail_url, filename,
 * alt_text, type}`, or a list of those), so a column and a field can sit on the
 * same attribute.
 */
class MediaPickerColumn extends Column
{
    protected bool|Closure $isCircular = false;

    protected int|Closure $size = 48;

    public function circular(bool|Closure $condition = true): static
    {
        $this->isCircular = $condition;

        return $this;
    }

    /** The side of the thumbnail, in pixels. */
    public function size(int|Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function isCircular(): bool
    {
        return (bool) $this->evaluate($this->isCircular);
    }

    public function getSize(): int
    {
        return (int) $this->evaluate($this->size);
    }

    /**
     * The state as a list, whatever shape it arrived in: one item, several, or
     * nothing recognisable.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMediaItems(mixed $state): array
    {
        if (empty($state) || ! is_array($state)) {
            return [];
        }

        if (array_key_exists('url', $state)) {
            return [$state];
        }

        return isset($state[0]) && is_array($state[0]) ? array_values($state) : [];
    }

    public function getView(): string
    {
        return 'tagixo-primix::tables.columns.media-picker';
    }
}
