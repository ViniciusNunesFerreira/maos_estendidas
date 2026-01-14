<?php

namespace App\Exceptions;

use Exception;

class FilhoNotActiveException extends Exception
{
    protected $code = 403;
}