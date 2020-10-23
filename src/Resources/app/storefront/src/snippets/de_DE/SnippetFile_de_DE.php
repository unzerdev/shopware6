<?php

declare(strict_types=1);

namespace UnzerPayment6\Resources\app\storefront\src\snippets\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'unzer-payment.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/unzer-payment.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
    }

    public function getAuthor(): string
    {
        return 'Unzer Payment GmbH';
    }

    public function isBase(): bool
    {
        return false;
    }
}
