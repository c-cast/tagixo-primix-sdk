<?php

namespace Tagixo\Primix\Forms\PropTypes;

class DateTablePropType extends PrimixTablePropType
{
    protected function allowedColumnTypes(): array
    {
        return [
            ['value' => 'date', 'label' => __('Date')],
            ['value' => 'text', 'label' => __('Text')],
        ];
    }

    protected function defaultColumnType(): string
    {
        return 'date';
    }
}
