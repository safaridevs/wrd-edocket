<?php

namespace App\Exceptions;

use RuntimeException;

class DocumentFileNotFound extends RuntimeException
{
    public static function forKey(?string $key): self
    {
        return new self('Document file not found: ' . ($key ?: '(no storage key)'));
    }
}
