<?php
/*
 *  Test company owner class for B2B customer.
 *
 *  @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\test\unit\Resources\EmbeddedResources;

use UnzerSDK\Resources\EmbeddedResources\CompanyOwner;
use UnzerSDK\test\BasePaymentTest;

class CompanyOwnerTest extends BasePaymentTest
{
    /**
     * Verify setter and getter functionality.
     *
     * @test
     */
    public function settersAndGettersShouldWork(): void
    {
        $owner = new CompanyOwner();
        $this->assertNull($owner->getFirstname());
        $this->assertNull($owner->getLastname());
        $this->assertNull($owner->getBirthdate());

        $owner->setFirstname('firstname')
            ->setLastname('lastname')
            ->setBirthdate('01.01.1999');

        $this->assertEquals('firstname', $owner->getFirstname());
        $this->assertEquals('lastname', $owner->getLastname());
        $this->assertEquals('01.01.1999', $owner->getBirthdate());
    }
}
