<?php

namespace Tagixo\Primix\Resources;

use Tagixo\Primix\Resources\Pages\PreviewAppForm;

/**
 * Forms of the form builder. On top of the common screens it carries the preview
 * of an `app`-target form, rendered as a real Primix form — which is what the
 * builder's own Preview opens once the form capability is on.
 */
class FormResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'forms';

    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'preview-app' => PreviewAppForm::route('/{record}/preview'),
        ];
    }
}
