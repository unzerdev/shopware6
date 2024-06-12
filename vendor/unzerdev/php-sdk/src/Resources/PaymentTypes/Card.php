<?php

namespace UnzerSDK\Resources\PaymentTypes;

use UnzerSDK\Adapter\HttpAdapterInterface;
use UnzerSDK\Resources\EmbeddedResources\CardDetails;
use UnzerSDK\Traits\CanAuthorize;
use UnzerSDK\Traits\CanDirectCharge;
use UnzerSDK\Traits\CanPayout;
use UnzerSDK\Traits\CanRecur;
use UnzerSDK\Validators\ExpiryDateValidator;
use RuntimeException;
use stdClass;

/**
 * This represents the card payment type which supports credit card as well as debit card payments.
 *
 * @link  https://docs.unzer.com/
 *
 */
class Card extends BasePaymentType
{
    use CanDirectCharge;
    use CanAuthorize;
    use CanPayout;
    use CanRecur;

    /** @var string $number */
    protected $number;

    /** @var string $expiryDate */
    protected $expiryDate;

    /** @var string $cvc */
    protected $cvc;

    /** @var string $cardHolder */
    protected $cardHolder = '';

    /** @var bool $card3ds */
    protected $card3ds;

    /** @var string */
    protected $email;

    /** @var string $brand */
    private $brand = '';

    /** @var CardDetails $cardDetails */
    private $cardDetails;

    /**
     * Card constructor.
     *
     * @param string|null $number
     * @param string|null $expiryDate
     * @param string|null $email
     */
    public function __construct(?string $number, ?string $expiryDate, string $email = null)
    {
        $this->setNumber($number);
        $this->setExpiryDate($expiryDate);
        $this->setEmail($email);
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string|null $pan
     *
     * @return Card
     */
    public function setNumber(?string $pan): Card
    {
        $this->number = $pan;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExpiryDate(): ?string
    {
        return $this->expiryDate;
    }

    /**
     * @param string|null $expiryDate
     *
     * @return Card
     *
     */
    public function setExpiryDate(?string $expiryDate): Card
    {
        // Null value is allowed to be able to fetch a card object with nothing but the id set.
        if ($expiryDate === null) {
            return $this;
        }

        if (!ExpiryDateValidator::validate($expiryDate)) {
            throw new RuntimeException("Invalid expiry date format: \"{$expiryDate}\". Allowed formats are 'm/Y' and 'm/y'.");
        }
        $expiryDateParts = explode('/', $expiryDate);
        $this->expiryDate = date('m/Y', mktime(0, 0, 0, $expiryDateParts[0], 1, $expiryDateParts[1]));

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCvc(): ?string
    {
        return $this->cvc;
    }

    /**
     * @param string|null $cvc
     *
     * @return Card
     */
    public function setCvc(?string $cvc): Card
    {
        $this->cvc = $cvc;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardHolder(): ?string
    {
        return $this->cardHolder;
    }

    /**
     * @param string $cardHolder
     *
     * @return Card
     */
    public function setCardHolder(string $cardHolder): Card
    {
        $this->cardHolder = $cardHolder;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function get3ds(): ?bool
    {
        return $this->card3ds;
    }

    /**
     * @param bool|null $card3ds
     *
     * @return Card
     */
    public function set3ds(?bool $card3ds): Card
    {
        $this->card3ds = $card3ds;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * Setter for brand property.
     * Will be set internally on create or fetch card.
     *
     * @param string $brand
     *
     * @return Card
     */
    protected function setBrand(string $brand): Card
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return CardDetails|null
     */
    public function getCardDetails(): ?CardDetails
    {
        return $this->cardDetails;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return Card
     */
    public function setEmail(?string $email): Card
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Rename internal property names to external property names.
     *
     * {@inheritDoc}
     */
    public function expose()
    {
        $exposeArray = parent::expose();
        if (isset($exposeArray['card3ds'])) {
            $exposeArray['3ds'] = $exposeArray['card3ds'];
            unset($exposeArray['card3ds']);
        }
        return $exposeArray;
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->cardDetails)) {
            $this->cardDetails = new CardDetails();
            $this->cardDetails->handleResponse($response->cardDetails);
        }
    }
}
