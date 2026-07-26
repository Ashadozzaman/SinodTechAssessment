<?php

namespace App\Exceptions;

use App\Models\Customer;
use Exception;

class DuplicateActiveAssignmentException extends Exception
{
    public static function forCustomer(Customer $customer): self
    {
        return new self("\"{$customer->name}\" already has an active assignment.");
    }
}
