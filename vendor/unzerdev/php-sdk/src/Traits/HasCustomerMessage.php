<?php
/**
 * This trait adds the message properties to a resource class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use UnzerSDK\Resources\EmbeddedResources\Message;

trait HasCustomerMessage
{
    /** @var Message $message */
    private $message;

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        if (!$this->message instanceof Message) {
            $this->message = new Message();
        }

        return $this->message;
    }
}
