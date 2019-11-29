<?php

declare(strict_types=1);

namespace HeidelPayment\Resources\app\storefront\src\snippets\de_DE;

use Shopware\Core\Framework\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'heidelpay.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/heidelpay.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
    }

    public function getAuthor(): string
    {
        return 'heidelpay GmbH';
    }

    public function isBase(): bool
    {
        return false;
    }
}
