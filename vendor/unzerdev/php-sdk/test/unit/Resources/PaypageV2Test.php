<?php

namespace UnzerSDK\test\unit\Resources;

use UnzerSDK\Resources\V2\Paypage;
use UnzerSDK\test\BasePaymentTest;
use UnzerSDK\test\Fixtures\JsonProvider;

/**
 * @group CC-1309
 * @group CC-1377
 */
class PaypageV2Test extends BasePaymentTest
{
    /**
     * @test
     */
    public function verifyHandleResponse()
    {
        // when
        $getResponse = JsonProvider::getJsonFromFile('paypage/getResponse.json');
        $paypage = new Paypage(0, '', '');
        $paypage->handleResponse(json_decode($getResponse));

        // then
        $this->assertEquals(null, $paypage->getId());
        $this->assertEquals(2, $paypage->getTotal());
        $this->assertCount(2, $paypage->getPayments());

        $firstPayment = $paypage->getPayments()[0];
        $this->assertNotNull($firstPayment);
        $this->assertNotNull($firstPayment->getMessages());
        $this->assertEquals('s-pay-1337', $firstPayment->getPaymentId());
        $this->assertEquals('pending', $firstPayment->getTransactionStatus());
        $this->assertEquals('2024-08-16T14:36:15.923Z', $firstPayment->getCreationDate());

        // verify messages
        $messages1 = $firstPayment->getMessages()[0];
        $this->assertEquals('COR.000.200.000', $messages1->getCode());
        $this->assertEquals('Your payment is currently pending. Please contact us for more information.', $messages1->getCustomer());
        $this->assertEquals('Transaction pending', $messages1->getMerchant());


        $secondPayment = $paypage->getPayments()[1];
        $this->assertNotNull($secondPayment);
        $this->assertNotNull($secondPayment->getMessages());
        $this->assertEquals('s-pay-1338', $secondPayment->getPaymentId());
        $this->assertEquals('success', $secondPayment->getTransactionStatus());
        $this->assertEquals('2024-08-16T14:36:54.517Z', $secondPayment->getCreationDate());

        // verify messages
        $messages2 = $secondPayment->getMessages()[0];
        $this->assertEquals('COR.000.100.112', $messages2->getCode());
        $this->assertEquals('Your payments have been successfully processed in sandbox mode.', $messages2->getCustomer());
        $this->assertEquals("Request successfully processed in 'Merchant in Connector Test Mode'", $messages2->getMerchant());
    }
}