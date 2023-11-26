<?php

namespace MaplePHP\Http\Exceptions;

use MaplePHP\Http\Interfaces\ClientExceptionInterface;
use Exception;

/**
 * Every HTTP client related exception MUST implement this interface.
 */
class ClientException extends Exception implements ClientExceptionInterface
{
}
