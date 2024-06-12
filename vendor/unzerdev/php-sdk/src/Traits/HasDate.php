<?php
/**
 * This trait adds the date property to a resource class.
 *
 * @link  https://docs.unzer.com/
 *
 */

namespace UnzerSDK\Traits;

use DateTime;
use Exception;

trait HasDate
{
    /** @var DateTime $date */
    private $date;

    /**
     * This returns the date of the Transaction as string.
     *
     * @return string|null
     */
    public function getDate(): ?string
    {
        $date = $this->date;
        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    /**
     * @param string $date
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setDate(string $date): self
    {
        $this->date = new DateTime($date);
        return $this;
    }
}
