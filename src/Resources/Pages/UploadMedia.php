<?php

namespace Tagixo\Primix\Resources\Pages;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LiVue\Features\SupportFileUploads\TemporaryUploadedFile;
use Primix\Forms\Components\Fields\FileUpload;
use Primix\Forms\Components\Fields\TextInput;
use Primix\Forms\Form;
use Primix\Notifications\Notification;
use Primix\Resources\Pages\CreateRecord;
use Tagixo\Core\MediaGallery\Services\MediaService;
use Tagixo\Primix\Resources\MediaResource;

/**
 * Uploading is not creating a row: `MediaService` stores the file, reads its size
 * and dimensions, makes the thumbnail and writes the record. This page therefore
 * hands each file over as it arrives and never creates anything itself.
 */
class UploadMedia extends CreateRecord
{
    protected static ?string $resource = MediaResource::class;

    /** Ids of what was stored, so the page can say how many. */
    protected array $uploaded = [];

    protected function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('folder')
                    ->label(__('Folder'))
                    ->maxLength(255)
                    ->helperText(__('Optional. A flat label, not a path.')),
                FileUpload::make('files')
                    ->label(__('Files'))
                    ->multiple()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): ?string {
                        $media = app(MediaService::class)->upload(
                            static::asUploadedFile($file),
                            folder: is_string($this->data['folder'] ?? null) ? $this->data['folder'] : null,
                        );

                        $this->uploaded[] = $media->getKey();
                        $file->delete();

                        return (string) $media->getKey();
                    }),
            ])
            ->operation('create')
            ->statePath('data')
            ->submitAction('create')
            ->footerActions(fn () => $this->getVisibleFooterActions());
    }

    public function create(): void
    {
        $this->validate(
            $this->getFormValidationRules('form'),
            $this->getFormValidationMessages('form'),
            $this->getFormValidationAttributes('form'),
        );

        $this->uploaded = [];

        // Dehydrating the state is what hands the files to the service.
        $data = $this->data;
        $this->getForm()->dehydrateState($data);

        Notification::make()
            ->title(trans_choice(':count file uploaded|:count files uploaded', count($this->uploaded), ['count' => count($this->uploaded)]))
            ->success()
            ->send();

        $this->redirect(MediaResource::getUrl('index'), navigate: true);
    }

    /**
     * The temporary file of LiVue lives on a disk, which may not be local, so it
     * is read through the filesystem and handed over as a real upload — which is
     * what `MediaService` validates and stores.
     */
    public static function asUploadedFile(TemporaryUploadedFile $file): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tgx-upload-');
        file_put_contents($path, Storage::disk($file->getDisk())->get($file->getPath()));

        return new UploadedFile($path, $file->getOriginalName(), $file->getMimeType(), null, true);
    }
}
