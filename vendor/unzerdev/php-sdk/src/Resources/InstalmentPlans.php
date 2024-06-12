<?php

namespace UnzerSDK\Resources;

use DateTime;
use UnzerSDK\Adapter\HttpAdapterInterface;
use stdClass;

/**
 * Resource used to fetch instalment plans for Installment Secured payment method specified as parent resource.
 * Please use Unzer methods to fetch the list of instalment plans
 * (e.g. Unzer::fetchInstallmentPlans(...)).
 *
 * @link  https://docs.unzer.com/
 *
 */
class InstalmentPlans extends AbstractUnzerResource
{
    /** @var float */
    private $amount;

    /** @var string */
    private $currency;

    /** @var float */
    private $effectiveInterest;

    /** var stdClass[] $plans */
    private $plans = [];

    /** @var string|null */
    private $orderDate;

    /**
     * InstalmentPlans constructor.
     *
     * @param float                $amount
     * @param string               $currency
     * @param float                $effectiveInterest
     * @param DateTime|string|null $orderDate
     */
    public function __construct(
        float $amount,
        string $currency,
        float $effectiveInterest,
        $orderDate = null
    ) {
        $this->amount            = $amount;
        $this->currency          = $currency;
        $this->effectiveInterest = $effectiveInterest;
        $this->setOrderDate($orderDate);
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return InstalmentPlans
     */
    public function setAmount(float $amount): InstalmentPlans
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return InstalmentPlans
     */
    public function setCurrency(string $currency): InstalmentPlans
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return float
     */
    public function getEffectiveInterest(): float
    {
        return $this->effectiveInterest;
    }

    /**
     * @param float $effectiveInterest
     *
     * @return InstalmentPlans
     */
    public function setEffectiveInterest(float $effectiveInterest): InstalmentPlans
    {
        $this->effectiveInterest = $effectiveInterest;
        return $this;
    }

    /**
     * @return stdClass[]
     */
    public function getPlans(): array
    {
        return $this->plans;
    }

    /**
     * @param stdClass[] $plans
     *
     * @return InstalmentPlans
     */
    protected function setPlans(array $plans): InstalmentPlans
    {
        $this->plans = $plans;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderDate(): ?string
    {
        return $this->orderDate;
    }

    /**
     * @param string|DateTime|null $orderDate
     *
     * @return InstalmentPlans
     */
    public function setOrderDate($orderDate): InstalmentPlans
    {
        $this->orderDate = $orderDate instanceof DateTime ? $orderDate->format('Y-m-d') : $orderDate;
        return $this;
    }

    /**
     * Returns the parameter array containing the values for the query string.
     *
     * @return array
     */
    protected function getQueryArray(): array
    {
        $parameters = [];
        $parameters['amount'] = $this->getAmount();
        $parameters['currency'] = $this->getCurrency();
        $parameters['effectiveInterest'] = $this->getEffectiveInterest();
        if ($this->getOrderDate() !== null) {
            $parameters['orderDate'] = $this->getOrderDate();
        }
        return $parameters;
    }

    /**
     * Returns the query string for this resource.
     *
     * @return string
     */
    protected function getQueryString(): string
    {
        return '?' . http_build_query($this->getQueryArray());
    }

    /**
     * {@inheritDoc}
     */
    public function getResourcePath(string $httpMethod = HttpAdapterInterface::REQUEST_GET): string
    {
        return 'plans' . $this->getQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function handleResponse(stdClass $response, string $method = HttpAdapterInterface::REQUEST_GET): void
    {
        parent::handleResponse($response, $method);

        if (isset($response->entity)) {
            $plans = [];
            foreach ($response->entity as $plan) {
                $instalment = new InstalmentPlan();
                $instalment->handleResponse($plan);
                $plans[] = $instalment;
            }
            $this->setPlans($plans);
        }
    }
}
