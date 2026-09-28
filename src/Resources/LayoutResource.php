<?php

namespace Tagixo\Primix\Resources;

use Illuminate\Database\Eloquent\Builder;
use Primix\Forms\Components\Fields\Repeater;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Fields\TextInput;
use Primix\Forms\Components\Fields\Toggle;
use Primix\Forms\Components\Utilities\Get;
use Primix\Forms\Form;
use Primix\Resources\Actions\CreateAction;
use Primix\Resources\Actions\DeleteAction;
use Primix\Resources\Actions\DeleteBulkAction;
use Primix\Resources\Actions\EditAction;
use Primix\Resources\Pages\CreateRecord;
use Primix\Resources\Pages\EditRecord;
use Primix\Resources\Pages\ListRecords;
use Primix\Resources\Resource;
use Primix\Tables\Columns\IconColumn;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Table;
use Tagixo\Core\Facades\Tagixo;
use Tagixo\PageBuilder\Models\Layout;
use Tagixo\PageBuilder\Models\Page;
use Tagixo\PageBuilder\Services\LayoutConditionService;

/**
 * Layouts of the page builder: the header and footer a page wears. They are not a
 * builder type, because their content is edited from a page — the section toggler
 * of the editor saves the header and the footer of whichever layout matched. What
 * is administered here is which pages a layout applies to.
 *
 * A layout matches by conditions, scored by `LayoutResolver`; the one marked
 * global is the fallback for everything that matches nothing.
 */
class LayoutResource extends Resource
{
    protected static ?string $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return config('tagixo-page-builder.models.layout', Layout::class);
    }

    public static function getSlug(): string
    {
        return 'layouts';
    }

    public static function getNavigationLabel(): string
    {
        return __('Layouts');
    }

    public static function getModelLabel(): string
    {
        return __('Layout');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Layouts');
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? config('tagixo-primix.icons.layouts', 'pi pi-table');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? config('tagixo-primix.navigation_group');
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->orderByDesc('is_global')->orderBy('name');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                IconColumn::make('is_global')->label(__('Global'))->boolean(),
                TextColumn::make('conditions')
                    ->label(__('Applies to'))
                    ->formatStateUsing(static fn (mixed $state): string => static::conditionsSummary($state)),
                TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            Toggle::make('is_global')
                ->label(__('Global'))
                ->helperText(__('The fallback for every page no other layout claims. Only one.')),
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

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'create' => CreateRecord::route('/create'),
            'edit' => EditRecord::route('/{record}/edit'),
        ];
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
