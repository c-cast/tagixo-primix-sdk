<?php

namespace Tagixo\Primix\Forms\PropTypes;

class FileTablePropType extends PrimixTablePropType
{
    protected function allowedColumnTypes(): array
    {
        return [
            ['value' => 'image', 'label' => __('Image')],
            ['value' => 'text',  'label' => __('Text')],
        ];
    }

    protected function defaultColumnType(): string
    {
        return 'image';
    }
}
