<?php

namespace Tagixo\Primix\Resources;

/**
 * Templates of the mail builder, named after their `name` column.
 */
class MailResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'mails';
}
