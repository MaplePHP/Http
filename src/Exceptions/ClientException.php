<?php

namespace MaplePHP\Http\Exceptions;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Every HTTP client related exception MUST implement this interface.
 */
class ClientException extends Exception implements ClientExceptionInterface
{
}
