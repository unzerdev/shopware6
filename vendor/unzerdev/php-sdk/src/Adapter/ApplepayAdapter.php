<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace UnzerSDK\Adapter;

use UnzerSDK\Constants\ApplepayValidationDomains;
use UnzerSDK\Exceptions\ApplepayMerchantValidationException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use UnzerSDK\Services\EnvironmentService;

/**
 * This is a wrapper for the applepay http adapter (CURL).
 *
 * @link https://dev.unzer.com/
 *
 */
class ApplepayAdapter
{
    private $request;

    /**
     * @param string          $merchantValidationURL URL for merchant validation request
     * @param ApplepaySession $applePaySession       Containing applepay session data.
     *
     * @return string|null
     *
     * @throws ApplepayMerchantValidationException
     */
    public function validateApplePayMerchant(
        string $merchantValidationURL,
        ApplepaySession $applePaySession
    ): ?string {
        if (!$this->validMerchantValidationDomain($merchantValidationURL)) {
            throw new ApplepayMerchantValidationException("Invalid URL used for merchantValidation request.");
        }
        if ($this->request === null) {
            throw new ApplepayMerchantValidationException('No curl adapter initiated yet. Make sure to cal init() function before.');
        }
        $payload = $applePaySession->jsonSerialize();
        $this->setOption(CURLOPT_URL, $merchantValidationURL);
        $this->setOption(CURLOPT_POSTFIELDS, $payload);

        $sessionResponse = $this->execute();
        $this->close();
        return $sessionResponse;
    }

    /**
     * Check whether domain of merchantValidationURL is allowed for validation request.
     *
     * @param string $merchantValidationURL URL used for merchant validation request.
     *
     * @return bool
     */
    public function validMerchantValidationDomain(string $merchantValidationURL): bool
    {
        $domain = explode('/', $merchantValidationURL)[2] ?? '';

        $UrlList = ApplepayValidationDomains::ALLOWED_VALIDATION_URLS;
        return in_array($domain, $UrlList);
    }

    /**
     * @param string      $sslCert Path to merchant identification certificate.
     * @param string|null $sslKey  Path to merchant identification key file.
     *                             This is necessary if the ssl cert file doesn't contain key already.
     * @param string|null $caCert  Path to CA certificate.
     */
    public function init(string $sslCert, ?string $sslKey = null, ?string $caCert = null): void
    {
        $timeout = EnvironmentService::getTimeout();
        $curlVerbose = EnvironmentService::isCurlVerbose();

        $this->request = curl_init();
        $this->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $this->setOption(CURLOPT_POST, 1);
        $this->setOption(CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        $this->setOption(CURLOPT_FAILONERROR, false);
        $this->setOption(CURLOPT_TIMEOUT, $timeout);
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
        $this->setOption(CURLOPT_HTTP200ALIASES, (array)400);
        $this->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->setOption(CURLOPT_SSL_VERIFYPEER, 1);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $this->setOption(CURLOPT_VERBOSE, $curlVerbose);
        $this->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

        $this->setOption(CURLOPT_SSLCERT, $sslCert);
        if (isset($sslKey) && !empty($sslKey)) {
            $this->setOption(CURLOPT_SSLKEY, $sslKey);
        }
        if (isset($caCert) && !empty($caCert)) {
            $this->setOption(CURLOPT_CAINFO, $caCert);
        }
    }

    /**
     * Sets curl option.
     *
     * @param $name
     * @param $value
     */
    private function setOption($name, $value): void
    {
        curl_setopt($this->request, $name, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ApplepayMerchantValidationException
     */
    public function execute(): ?string
    {
        $response = curl_exec($this->request);
        $error = curl_error($this->request);
        $errorNo = curl_errno($this->request);

        switch ($errorNo) {
            case 0:
                return $response;
                break;
            case CURLE_OPERATION_TIMEDOUT:
                $errorMessage = 'Timeout: The Applepay API seems to be not available at the moment!';
                break;
            default:
                $errorMessage = $error . ' (curl_errno: ' . $errorNo . ').';
                break;
        }
        throw new ApplepayMerchantValidationException($errorMessage);
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        curl_close($this->request);
    }

    /**
     * @inheritDoc
     */
    public function getResponseCode(): string
    {
        return curl_getinfo($this->request, CURLINFO_HTTP_CODE);
    }
}
