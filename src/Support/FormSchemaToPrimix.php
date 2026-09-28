<?php

namespace Tagixo\Primix\Support;

use Illuminate\Support\Str;
use Tagixo\FormBuilder\Models\FormSchema;

/**
 * Converts a saved Tagixo form schema (flat list of nodes with parent_id) into a
 * Primix Forms "fromSchema" definitions array (nested), so an app-target form can
 * be rendered as a real Primix form — with native interactive Tabs/Wizard.
 *
 * Tagixo type  → Primix type
 *   text-input → text-input,  text-area → textarea,  select → select
 *   checkbox → checkbox,  radio → radio,  date-picker → date-picker
 *   file-upload → file-upload
 *   form-grid/form-group → grid,  form-section → section,  form-fieldset → fieldset
 *   form-tabs → tabs (children → 'tabs'),  form-wizard → wizard (children → 'steps')
 *   submit-button → omitted (a form rendered by the panel has its own submit)
 */
class FormSchemaToPrimix
{
    /**
     * The layout modules of the form builder carry a `form-` prefix of their own
     * (`form-grid`, `form-tabs`, …); the field ones are named after the field.
     */
    private const TYPE_MAP = [
        'text-input' => 'text-input',
        'text-area' => 'textarea',
        'select' => 'select',
        'checkbox' => 'checkbox',
        'radio' => 'radio',
        'date-picker' => 'date-picker',
        'file-upload' => 'file-upload',
        'form-grid' => 'grid',
        'form-group' => 'grid',
        'form-section' => 'section',
        'form-fieldset' => 'fieldset',
        'form-tabs' => 'tabs',
        'form-wizard' => 'wizard',
    ];

    private const LAYOUT_TYPES = ['form-grid', 'form-group', 'form-section', 'form-fieldset'];

    private const TAB_TYPE = 'form-tab';

    private const STEP_TYPE = 'form-wizard-step';

    /** @var array<string, array<int, array<string,mixed>>> */
    private array $childrenByParent = [];

    /**
     * Definitions of a saved form: its components, wrapped in the grid its body
     * declares, so the column spans of the builder still mean something.
     *
     * @return array<int, array<string,mixed>>
     */
    public function fromForm(FormSchema $form): array
    {
        $content = is_array($form->content ?? null) ? $form->content : [];
        $components = is_array($content['components'] ?? null)
            ? $content['components']
            : (is_array($form->fields ?? null) ? $form->fields : []);

        $body = is_array($content['body'] ?? null) ? $content['body'] : [];
        $rootColumns = (int) ($body['grid']['columns']['value'] ?? $body['grid']['columns'] ?? 12);

        return $this->toDefinitions($components, $rootColumns);
    }

    /**
     * @param  array<int, array<string,mixed>>  $components  flat Tagixo components
     * @param  int|null  $rootColumns  columns of the root grid
     *                                 (from body.grid.columns); when provided and > 0,
     *                                 root-level fields are wrapped in a grid so column
     *                                 spans work correctly in merge mode.
     * @return array<int, array<string,mixed>> Primix fromSchema definitions
     */
    public function toDefinitions(array $components, ?int $rootColumns = null): array
    {
        $this->childrenByParent = [];
        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }
            $parent = $component['parent_id'] ?? null;
            $key = ($parent === null || $parent === '') ? '__root__' : (string) $parent;
            $this->childrenByParent[$key][] = $component;
        }
        foreach ($this->childrenByParent as &$siblings) {
            usort($siblings, static fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        }
        unset($siblings);

        $defs = $this->buildLevel('__root__');

        if ($rootColumns !== null && $rootColumns > 0 && $defs !== []) {
            $defs = [['type' => 'grid', 'columns' => $rootColumns, 'schema' => $defs]];
        }

        return $defs;
    }

    /** @return array<int, array<string,mixed>> */
    private function buildLevel(string $parentKey): array
    {
        $defs = [];
        // Orphan tab/wizard-step nodes (no tabs-layout/wizard parent — possible in
        // older malformed schemas) are grouped into a synthetic container so they
        // still render as real Tabs/Wizard.
        $pendingTabs = [];
        $pendingSteps = [];
        $flush = function () use (&$defs, &$pendingTabs, &$pendingSteps): void {
            if ($pendingTabs !== []) {
                $defs[] = ['type' => 'tabs', 'tabs' => $pendingTabs];
                $pendingTabs = [];
            }
            if ($pendingSteps !== []) {
                $defs[] = ['type' => 'wizard', 'steps' => $pendingSteps];
                $pendingSteps = [];
            }
        };

        foreach ($this->childrenByParent[$parentKey] ?? [] as $node) {
            $type = (string) ($node['type'] ?? '');

            if ($type === self::TAB_TYPE) {
                $pendingTabs[] = $this->branch($node, 'tab');

                continue;
            }
            if ($type === self::STEP_TYPE) {
                $pendingSteps[] = $this->branch($node, 'step');

                continue;
            }

            $flush();
            $def = $this->buildNode($node);
            if ($def !== null) {
                $defs[] = $def;
            }
        }

        $flush();

        return $defs;
    }

    /** @return array<string,mixed>|null */
    private function buildNode(array $node): ?array
    {
        $type = (string) ($node['type'] ?? '');
        $id = (string) ($node['id'] ?? '');
        $props = $this->props($node);

        // Tab / wizard-step are emitted as entries of their parent's tabs/steps,
        // not as standalone components.
        if ($type === 'submit-button' || $type === self::TAB_TYPE || $type === self::STEP_TYPE) {
            return null;
        }

        $primixType = self::TYPE_MAP[$type] ?? null;
        if ($primixType === null) {
            return null;
        }

        $def = ['type' => $primixType];
        $label = trim(strip_tags((string) ($props['label'] ?? '')));

        // Tabs: children are tab nodes → { label, name, schema }.
        if ($type === 'form-tabs') {
            if ($label !== '') {
                $def['label'] = $label;
            }
            $def['tabs'] = $this->buildBranches($id, 'tab');

            return $def;
        }

        // Wizard: children are wizard-step nodes → { label, name, schema }.
        if ($type === 'form-wizard') {
            if ($label !== '') {
                $def['label'] = $label;
            }
            $def['steps'] = $this->buildBranches($id, 'step');

            return $def;
        }

        // Layout containers (grid/group/section/fieldset): nested schema.
        if (in_array($type, self::LAYOUT_TYPES, true)) {
            if ($label !== '') {
                $def['label'] = $label;
            }
            $columns = (int) ($props['columns'] ?? 0);
            if ($columns > 0) {
                $def['columns'] = $columns;
            }
            $this->applySpan($def, $props);
            $def['schema'] = $this->buildLevel($id);

            return $def;
        }

        // Leaf field.
        $fieldName = $this->fieldName($props, $id);
        $def['name'] = $fieldName;
        if ($label !== '') {
            $def['label'] = $label;
        }
        $def['extraWrapperAttributes'] = ['data-tgx-field' => $fieldName];
        $placeholder = trim((string) ($props['placeholder'] ?? ''));
        if ($placeholder !== '') {
            $def['placeholder'] = $placeholder;
        }
        $helper = trim((string) ($props['helper_text'] ?? ''));
        if ($helper !== '') {
            $def['helperText'] = $helper;
        }
        $default = $props['default_value'] ?? null;
        if ($default !== null && $default !== '') {
            $def['default'] = $default;
        }
        if ((bool) data_get($props, 'validation.required', false)) {
            $def['required'] = true;
        }
        if (in_array($type, ['select', 'radio'], true)) {
            $options = $this->options($props['options'] ?? null);
            if ($options !== []) {
                $def['options'] = $options;
            }
        }
        $this->applySpan($def, $props);

        return $def;
    }

    /**
     * Build the { label, name, schema } branch list for tabs/wizard children.
     *
     * @param  string  $namePrefix  'tab' | 'step'
     * @return array<int, array<string,mixed>>
     */
    private function buildBranches(string $parentId, string $namePrefix): array
    {
        $branches = [];
        foreach ($this->childrenByParent[$parentId] ?? [] as $child) {
            $branches[] = $this->branch($child, $namePrefix);
        }

        return $branches;
    }

    /**
     * A single tab/step branch. A UNIQUE name is mandatory: Primix keys tabs/steps
     * by name (derived from the label slug by default), so unlabelled tabs would
     * all collapse onto the same name and behave like one tab. We force a unique
     * name from the node id.
     *
     * @return array<string,mixed>
     */
    private function branch(array $node, string $namePrefix): array
    {
        $props = $this->props($node);
        $label = trim(strip_tags((string) ($props['label'] ?? '')));
        $id = (string) ($node['id'] ?? '');

        return [
            'label' => $label !== '' ? $label : ($namePrefix === 'tab' ? 'Tab' : 'Step'),
            'name' => $namePrefix.'_'.substr(md5($id !== '' ? $id : uniqid($namePrefix, true)), 0, 10),
            'schema' => $this->buildLevel($id),
        ];
    }

    private function applySpan(array &$def, array $props): void
    {
        $span = (int) ($props['column_span'] ?? 0);
        if ($span > 0) {
            $def['columnSpan'] = $span;
        }
    }

    /** Merge a node's root + content props (content wins). */
    private function props(array $node): array
    {
        $raw = is_array($node['props'] ?? null) ? $node['props'] : [];
        $content = is_array($raw['content'] ?? null) ? $raw['content'] : [];
        $merged = array_merge($raw, $content);
        // Keep validation reachable via data_get($props, 'validation.required').
        if (! isset($merged['validation']) && isset($raw['validation'])) {
            $merged['validation'] = $raw['validation'];
        }

        return $merged;
    }

    private function fieldName(array $props, string $id): string
    {
        $name = trim((string) ($props['name'] ?? ''));
        if ($name !== '') {
            return Str::slug($name, '_');
        }

        return 'field_'.substr(md5($id !== '' ? $id : uniqid('f', true)), 0, 8);
    }

    /**
     * Tagixo options ([{label,value}]) → Primix options (['value' => 'label']).
     *
     * @return array<string,string>
     */
    private function options(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }
        $out = [];
        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $value = (string) ($opt['value'] ?? '');
            if ($value === '') {
                continue;
            }
            $out[$value] = (string) ($opt['label'] ?? $value);
        }

        return $out;
    }
}
