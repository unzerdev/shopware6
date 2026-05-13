<?php declare(strict_types=1);

namespace UnzerPayment6\Components\UnzerUtil;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class UnzerApiUtil
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getCachedKeypairConfig(string $publicKey, int $cacheTtl = 7200): ?array
    {
        $cacheKey = 'UnzerKeypairConfig_' . $publicKey;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($publicKey, $cacheTtl): ?array {
            $item->expiresAfter($cacheTtl);

            return $this->getKeypairConfig($publicKey);
        });
    }

    private function getKeypairConfig(string $publicKey): ?array
    {
        try {
            $host = str_starts_with($publicKey, 'p-pub-') ? 'api.unzer.com' : 'sbx-api.unzer.com';

            $ch = curl_init('https://' . $host . '/v1/keypair/types');
            curl_setopt_array($ch, [
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_USERPWD => $publicKey . ':',
                \CURLOPT_HTTPHEADER => ['Accept: application/json'],
                \CURLOPT_TIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!\is_string($response) || $status !== 200) {
                return null;
            }

            $decoded = json_decode($response, true);

            return \is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
