<?php declare(strict_types=1);

namespace HeidelPayment\Resources\app\storefront\src\snippets\en_GB;
use Shopware\Core\Framework\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'heidelpay.en-GB';
    }

    public function getPath(): string
    {
        return __DIR__ . '/heidelpay.en_GB.json';
    }

    public function getIso(): string
    {
        return 'en-GB';
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
