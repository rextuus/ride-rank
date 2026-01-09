<?php

declare(strict_types=1);

namespace App\Service\Rcdb\Exception;

class IsParcEntryException extends RcdbException
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, private ?string $parkName = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getParkName(): ?string
    {
        return $this->parkName;
    }
}
