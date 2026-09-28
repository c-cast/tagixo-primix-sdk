<?php

namespace Tagixo\Primix\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Primix\Actions\Action;
use Primix\Tables\Table;

/**
 * Printable documents of the document builder. A document is meant to be printed,
 * so the listing offers the file itself: the download is the document builder's
 * own route, which prints the saved document with the engine the application
 * chose.
 */
class DocumentResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'documents';

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table->actions([
            static::downloadAction(),
            ...$table->getActions(),
        ]);
    }

    public static function downloadAction(): Action
    {
        return Action::make('download')
            ->label(__('Download'))
            ->icon('pi pi-download')
            ->url(
                static fn (Model $record): string => route('tagixo.documents.download', ['id' => $record->getKey()]),
                shouldOpenInNewTab: true,
            )
            ->visible(static fn (): bool => Route::has('tagixo.documents.download'));
    }
}
