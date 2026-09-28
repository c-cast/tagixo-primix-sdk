<?php

namespace Tagixo\Primix\Resources;

use Illuminate\Database\Eloquent\Builder;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Fields\Textarea;
use Primix\Forms\Components\Fields\TextInput;
use Primix\Forms\Form;
use Primix\Resources\Actions\DeleteAction;
use Primix\Resources\Actions\DeleteBulkAction;
use Primix\Resources\Actions\EditAction;
use Primix\Resources\Resource;
use Primix\Tables\Columns\ImageColumn;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Filters\SelectFilter;
use Primix\Tables\Table;
use Tagixo\Core\MediaGallery\Models\Media;
use Tagixo\Core\MediaGallery\Services\MediaService;

/**
 * The media library of the core, which every builder picks from. Uploads go
 * through `MediaService`, so thumbnails, variants and the folder rules stay its
 * business; deleting a record deletes its files by itself (the model does it).
 */
class MediaResource extends Resource
{
    protected static ?string $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'filename';

    public static function getModel(): string
    {
        return config('tagixo.media_gallery.model', Media::class);
    }

    public static function getSlug(): string
    {
        return 'media';
    }

    public static function getNavigationLabel(): string
    {
        return __('Media');
    }

    public static function getModelLabel(): string
    {
        return __('File');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Media');
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? config('tagixo-primix.icons.media', 'pi pi-images');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? config('tagixo-primix.navigation_group');
    }

    /**
     * Originals only: a crop or a resized variant belongs to its own file, not to
     * the library.
     */
    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->originals()->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_url')->label(__('Preview')),
                TextColumn::make('filename')->label(__('Filename'))->searchable()->sortable(),
                TextColumn::make('type')->label(__('Kind'))->badge(),
                TextColumn::make('formatted_size')->label(__('Size')),
                TextColumn::make('folder')->label(__('Folder'))->sortable(),
                TextColumn::make('created_at')->label(__('Uploaded'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Kind'))
                    ->options([
                        'image' => __('Images'),
                        'video' => __('Videos'),
                        'document' => __('Documents'),
                    ])
                    ->query(static fn (Builder $query, mixed $value): Builder => match ($value) {
                        'image' => $query->images(),
                        'video' => $query->videos(),
                        'document' => $query->documents(),
                        default => $query,
                    }),
                SelectFilter::make('folder')
                    ->label(__('Folder'))
                    ->options(static fn (): array => array_combine(static::folders(), static::folders())),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * What an editor can change about a file: the rest is what the upload found.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->label(__('Title'))->maxLength(255),
            TextInput::make('alt_text')->label(__('Alt text'))->maxLength(255)
                ->helperText(__('Read out instead of the image, and used by search engines.')),
            Textarea::make('description')->label(__('Description')),
            Select::make('folder')
                ->label(__('Folder'))
                ->options(static fn (): array => array_combine(static::folders(), static::folders()))
                ->nullable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\UploadMedia::route('/upload'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }

    /**
     * Folders as the service reports them, so a name the panel offers is a name it
     * accepts.
     *
     * @return list<string>
     */
    public static function folders(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $folder): string => is_array($folder) ? (string) ($folder['name'] ?? '') : (string) $folder,
                app(MediaService::class)->getFolders(),
            ),
            static fn (string $folder): bool => $folder !== '',
        ));
    }
}
