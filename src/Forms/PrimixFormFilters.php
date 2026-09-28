<?php

namespace Tagixo\Primix\Forms;

use Primix\Tables\Filters\DateFilter;
use Primix\Tables\Filters\SelectFilter;
use Primix\Tables\Filters\TernaryFilter;
use Tagixo\FormBuilder\FormModule;
use Tagixo\FormBuilder\Models\FormSchema;

class PrimixFormFilters
{
    public static function from(string $formSlug): array
    {
        $form = FormSchema::where('slug', $formSlug)->first();

        return $form ? self::resolveFilters($form) : [];
    }

    public static function forForm(int|string $formId): array
    {
        $form = FormSchema::find($formId);

        return $form ? self::resolveFilters($form) : [];
    }

    public static function fromFields(array $fields): array
    {
        return self::resolveFields($fields);
    }

    private static function resolveFilters(FormSchema $form): array
    {
        return self::resolveFields($form->fields ?? []);
    }

    private static function resolveFields(array $fields): array
    {
        $filters = [];

        foreach ($fields as $field) {
            $typeId = (string) ($field['type'] ?? '');
            $tableProps = $field['props']['table'] ?? [];
            $content = FormModule::fillContentDefaults($typeId, $field['props']['content'] ?? []);

            if (! (bool) self::prop($tableProps, 'show_in_table')) {
                continue;
            }

            if (! (bool) self::prop($tableProps, 'filterable')) {
                continue;
            }

            $fieldKey = $content['name'] ?? $field['key'] ?? $field['id'] ?? null;

            if ($fieldKey === null) {
                continue;
            }

            $rawLabel = (string) (self::prop($tableProps, 'column_label') ?? '');
            $fallbackLabel = strip_tags((string) ($field['props']['content']['label'] ?? $field['label'] ?? $fieldKey));
            $columnLabel = $rawLabel !== '' ? strip_tags($rawLabel) : $fallbackLabel;
            $columnType = (string) (self::prop($tableProps, 'column_type') ?? 'text');

            $options = self::fieldOptions($content);

            $filter = match (true) {
                $columnType === 'boolean', $typeId === 'checkbox' => TernaryFilter::make($fieldKey)
                    ->label($columnLabel)
                    ->trueLabel(__('Yes'))
                    ->falseLabel(__('No'))
                    ->allLabel(__('All')),

                $columnType === 'date', $typeId === 'date-picker' => DateFilter::make($fieldKey)
                    ->label($columnLabel),

                // A field that offers choices filters by them.
                $options !== [] => SelectFilter::make($fieldKey)
                    ->label($columnLabel)
                    ->multiple()
                    ->options($options),

                $columnType === 'badge' => self::buildBadgeFilter($fieldKey, $columnLabel, $tableProps),

                // Free text is searched through the column (`searchable`), not
                // filtered: Primix has no filter for it, and a list of every answer
                // given would be no help.
                default => null,
            };

            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    private static function buildBadgeFilter(string $fieldKey, string $columnLabel, array $p): SelectFilter
    {
        $filter = SelectFilter::make($fieldKey)->label($columnLabel)->multiple();

        $badgeColors = self::prop($p, 'badge_colors');
        if (is_array($badgeColors) && $badgeColors !== []) {
            $options = [];
            foreach ($badgeColors as $item) {
                if (isset($item['value'])) {
                    $options[$item['value']] = $item['value'];
                }
            }
            if ($options !== []) {
                $filter->options($options);
            }
        }

        return $filter;
    }

    /**
     * Choices the field itself declares (a select, a radio), as Primix options.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    private static function fieldOptions(array $content): array
    {
        $options = [];

        foreach (is_array($content['options'] ?? null) ? $content['options'] : [] as $option) {
            if (! is_array($option)) {
                continue;
            }

            $value = (string) ($option['value'] ?? '');

            if ($value === '') {
                continue;
            }

            $options[$value] = (string) ($option['label'] ?? $value);
        }

        return $options;
    }

    private static function prop(array $tableProps, string $key): mixed
    {
        if (! array_key_exists($key, $tableProps)) {
            return null;
        }

        $v = $tableProps[$key];

        return is_array($v) && array_key_exists('value', $v) ? $v['value'] : $v;
    }
}
