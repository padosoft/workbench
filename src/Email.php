<?php
/**
 * Copyright (c) Padosoft.com 2016.
 */

namespace Padosoft\Workbench;

use Padosoft\Workbench\Traits\Enumerable;
use Validator;

class Email implements IEnumerable
{
    use Enumerable {
        Enumerable::isValidValue as isValidValueTrait;
    }

    public static function isValidValue($valore)
    {
        return Validator::make(
            [
                'email' => $valore
            ],
            [
                'email' => 'email'
            ]
        )->passes();

    }
}