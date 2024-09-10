<?php

declare(strict_types=1);

namespace Netgen\IbexaSearchExtra\Exception;

use RuntimeException;

class IndexPageUnavailableException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
