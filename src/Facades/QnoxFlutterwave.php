<?php

namespace Qnox\QnoxFlutterwave\Facades;

use Illuminate\Support\Facades\Facade;

class QnoxFlutterwave extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'qnox_flutterwave';
    }
}
