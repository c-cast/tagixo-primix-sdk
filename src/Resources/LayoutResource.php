<?php

namespace Tagixo\Primix\Resources;

use Illuminate\Database\Eloquent\Model;
use Primix\Actions\Action;
use Primix\Forms\Components\Fields\Repeater;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Utilities\Get;
use Primix\Forms\Form;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Table;
use Tagixo\Core\Facades\Tagixo;
use Tagixo\PageBuilder\Models\Page;
use Tagixo\PageBuilder\Services\LayoutConditionService;
use Tagixo\PageBuilder\Services\LayoutSections;

/**
 * Layouts of the page builder — the header and footer pages wear — which the
 * `layouts` builder type makes a record like any other. What this resource adds
 * is the thing only a layout has: the conditions that decide which pages wear it,
 * and one Build action per section, because a layout holds two documents.
 *
 * A layout matches by conditions, scored by `LayoutResolver`; the one marked
 * global is the fallback for everything that matches nothing. Its body is not
 * here: pages own their body, and a template scoped to a model stands for that
 * model's archive or single page (the Theme Builder shows all three).
 */
class LayoutResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'layouts';

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table
            ->columns([
                ...$table->getColumns(),
                TextColumn::make('conditions')
                    ->label(__('Applies to'))
                    ->formatStateUsing(static fn (mixed $state): string => static::conditionsSummary($state)),
            ])
            ->actions([
                ...static::sectionActions(),
                ...array_values(array_filter(
                    $table->getActions(),
                    // The generic Build opens one scope: a layout has two.
                    static fn ($action): bool => $action->getName() !== 'build',
                )),
            ]);
    }

    /**
     * One action per document a layout holds.
     *
     * @return list<Action>
     */
    public static function sectionActions(): array
    {
        return array_map(
            static fn (string $section): Action => Action::make($section)
                ->label($section === 'header' ? __('Header') : __('Footer'))
                ->icon($section === 'header' ? 'pi pi-arrow-up' : 'pi pi-arrow-down')
                ->url(static fn (Model $record): string => static::builderUrl($record, $section)),
            LayoutSections::SECTIONS,
        );
    }

    /**
     * The editor of one section. Without a section it is the header, which is
     * what `LayoutType` opens by default.
     */
    public static function builderUrl(Model $record, ?string $section = null): string
    {
        return route('tagixo.builder.embed', [
            'type' => static::tagixoType(),
            'id' => $record->getKey(),
            'back' => static::getUrl('index'),
            ...($section !== null ? ['scope' => $section] : []),
        ]);
    }

    public static function form(Form $form): Form
    {
        $form = parent::form($form);

        return $form->schema([
            ...$form->getComponents(),
            Repeater::make('conditions')
                ->label(__('Applies to'))
                ->helperText(__('A page wears the layout whose condition fits it best.'))
                ->addActionLabel(__('Add a condition'))
                ->schema([
                    Select::make('type')
                        ->label(__('Condition'))
                        ->options(static::conditionTypes())
                        ->required(),
                    Select::make('value')
                        ->label(__('Page'))
                        ->options(static fn (): array => static::pageOptions())
                        ->visible(static fn (Get $get): bool => $get('type') === 'page_id'),
                    Select::make('model')
                        ->label(__('Content type'))
                        ->options(static fn (): array => static::modelOptions())
                        ->visible(static fn (Get $get): bool => in_array($get('type'), ['model_all', 'model_archive'], true)),
                ]),
        ]);
    }

    /**
     * The conditions an editor can pick here. `LayoutResolver` scores more of
     * them (a record, a taxonomy term), which a page assigns by itself.
     *
     * @return array<string, string>
     */
    public static function conditionTypes(): array
    {
        return [
            'all_pages' => __('All pages'),
            'homepage' => __('Homepage'),
            'page_id' => __('One page'),
            'model_all' => __('Everything of a content type'),
            'model_archive' => __('The archive of a content type'),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    public static function pageOptions(): array
    {
        return config('tagixo-page-builder.models.page', Page::class)::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function modelOptions(): array
    {
        $options = [];

        foreach (Tagixo::getRegisteredModels() as $key => $model) {
            $options[$key] = (string) ($model['label'] ?? $key);
        }

        return $options;
    }

    /**
     * What a listing shows instead of raw conditions: the labels the page builder
     * itself writes for them.
     */
    public static function conditionsSummary(mixed $conditions): string
    {
        if (! is_array($conditions) || $conditions === []) {
            return __('Nothing yet');
        }

        $service = app(LayoutConditionService::class);

        return implode(', ', array_map(
            static fn (mixed $condition): string => is_array($condition) ? $service->getConditionLabel($condition) : '',
            $conditions,
        ));
    }
}
