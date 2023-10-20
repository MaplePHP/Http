<?php

namespace PHPFuse\Http\Exceptions;

use PHPFuse\Http\Interfaces\ClientExceptionInterface;
use Exception;

/**
 * Every HTTP client related exception MUST implement this interface.
 */
class ClientException extends Exception implements ClientExceptionInterface
{
}