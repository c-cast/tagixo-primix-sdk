<?php

namespace Tagixo\Primix\Forms\PropTypes;

class BooleanTablePropType extends PrimixTablePropType
{
    protected function allowedColumnTypes(): array
    {
        return [
            ['value' => 'boolean', 'label' => __('Boolean')],
        ];
    }

    protected function defaultColumnType(): string
    {
        return 'boolean';
    }
}
