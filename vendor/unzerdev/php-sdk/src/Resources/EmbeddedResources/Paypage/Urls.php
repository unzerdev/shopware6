<?php

namespace UnzerSDK\Resources\EmbeddedResources\Paypage;

use UnzerSDK\Resources\AbstractUnzerResource;

class Urls extends AbstractUnzerResource
{
    protected ?string $termsAndCondition = null;
    protected ?string $privacyPolicy = null;
    protected ?string $imprint = null;
    protected ?string $help = null;
    protected ?string $contact = null;
    protected ?string $favicon = null;
    protected ?string $returnSuccess = null;
    protected ?string $returnPending = null;
    protected ?string $returnFailure = null;
    protected ?string $returnCancel = null;

    public function getTermsAndCondition(): ?string
    {
        return $this->termsAndCondition;
    }

    public function setTermsAndCondition(?string $termsAndCondition): Urls
    {
        $this->termsAndCondition = $termsAndCondition;
        return $this;
    }

    public function getPrivacyPolicy(): ?string
    {
        return $this->privacyPolicy;
    }

    public function setPrivacyPolicy(?string $privacyPolicy): Urls
    {
        $this->privacyPolicy = $privacyPolicy;
        return $this;
    }

    public function getImprint(): ?string
    {
        return $this->imprint;
    }

    public function setImprint(?string $imprint): Urls
    {
        $this->imprint = $imprint;
        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): Urls
    {
        $this->help = $help;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): Urls
    {
        $this->contact = $contact;
        return $this;
    }

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    public function setFavicon(?string $favicon): Urls
    {
        $this->favicon = $favicon;
        return $this;
    }

    public function getReturnSuccess(): ?string
    {
        return $this->returnSuccess;
    }

    public function setReturnSuccess(?string $returnSuccess): Urls
    {
        $this->returnSuccess = $returnSuccess;
        return $this;
    }

    public function getReturnPending(): ?string
    {
        return $this->returnPending;
    }

    public function setReturnPending(?string $returnPending): Urls
    {
        $this->returnPending = $returnPending;
        return $this;
    }

    public function getReturnFailure(): ?string
    {
        return $this->returnFailure;
    }

    public function setReturnFailure(?string $returnFailure): Urls
    {
        $this->returnFailure = $returnFailure;
        return $this;
    }

    public function getReturnCancel(): ?string
    {
        return $this->returnCancel;
    }

    public function setReturnCancel(?string $returnCancel): Urls
    {
        $this->returnCancel = $returnCancel;
        return $this;
    }
}