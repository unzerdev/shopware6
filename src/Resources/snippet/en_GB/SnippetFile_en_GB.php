<?php

declare(strict_types=1);

namespace UnzerPayment6\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'unzer-payment.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__ . '/unzer-payment.en-GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
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
