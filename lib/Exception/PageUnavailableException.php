<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Exception;

use RuntimeException;

class PageUnavailableException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
