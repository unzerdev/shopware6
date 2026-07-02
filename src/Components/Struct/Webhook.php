<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct;

class Webhook
{
    private string $event;

    private string $publicKey;

    private string $retrieveUrl;

    private string $paymentId;

    public function __construct(string $jsonData)
    {
        $this->fromJson($jsonData);
    }

    public function fromJson(string $jsonData): void
    {
        $webhookData = json_decode($jsonData, true);

        $this->event = $webhookData['event'] ?? '';
        $this->publicKey = $webhookData['publicKey'] ?? '';
        $this->retrieveUrl = $webhookData['retrieveUrl'] ?? '';
        $this->paymentId = $webhookData['paymentId'] ?? '';
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getRetrieveUrl(): string
    {
        return $this->retrieveUrl;
    }

    public function setRetrieveUrl(string $retrieveUrl): self
    {
        $this->retrieveUrl = $retrieveUrl;

        return $this;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }
}
