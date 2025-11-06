<?php
/**
 * This exception is thrown whenever the api returns an error.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Exceptions;

use Exception;

class UnzerApiException extends Exception
{
    public const MESSAGE = 'The payment api returned an error!';
    public const CLIENT_MESSAGE = 'The payment api returned an error!';

    /** @var string $clientMessage */
    protected $clientMessage;

    /** @var string */
    private $errorId;

    /**
     * UnzerApiException constructor.
     *
     * @param string      $merchantMessage
     * @param string      $clientMessage
     * @param string|null $code
     * @param string|null $errorId
     */
    public function __construct($merchantMessage = '', $clientMessage = '', $code = null, ?string $errorId = null)
    {
        $merchantMessage = empty($merchantMessage) ? static::MESSAGE : $merchantMessage;
        $this->clientMessage = empty($clientMessage) ? static::CLIENT_MESSAGE : $clientMessage;
        parent::__construct($merchantMessage);
        $this->code = empty($code) ? 'No error code provided' : $code;
        $this->errorId = empty($errorId) ? 'No error id provided' : $errorId;
    }

    /**
     * @return string
     */
    public function getClientMessage(): string
    {
        return $this->clientMessage;
    }

    /**
     * @return string
     */
    public function getMerchantMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * @return string
     */
    public function getErrorId(): string
    {
        return $this->errorId;
    }
}
