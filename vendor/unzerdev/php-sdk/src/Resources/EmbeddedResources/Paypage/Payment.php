<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use stdClass;
use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\EmbeddedResources\Message;

class Payment extends AbstractUnzerResource
{
    protected ?string $paymentId = null;
    protected ?string $transactionStatus = null;
    protected ?string $creationDate = null;

    /** @var Message[]|null */
    protected ?array $messages = null;

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): Payment
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->transactionStatus;
    }

    public function setTransactionStatus(?string $transactionStatus): Payment
    {
        $this->transactionStatus = $transactionStatus;
        return $this;
    }

    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    public function setCreationDate(?string $creationDate): Payment
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getMessages(): ?array
    {
        return $this->messages;
    }

    public function setMessages(?array $messages): Payment
    {
        $this->messages = $messages;
        return $this;
    }

    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        if (isset($response->messages) && !empty($response->messages)) {
            $messages = [];
            foreach ($response->messages as $payment) {
                $newPayment = (new Message());
                $newPayment->handleResponse($payment);

                $messages[] = $newPayment;
            }
            $this->messages = $messages;
        }

        parent::handleResponse($response, $method);
    }


}