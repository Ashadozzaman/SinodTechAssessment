<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    public static function forProduct(Product $product, int $requested, int $available): self
    {
        return new self(
            "Insufficient stock for \"{$product->name}\": requested {$requested}, only {$available} available."
        );
    }
}
