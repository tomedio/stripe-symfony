<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Enum;

enum Currency: string
{
    case USD = 'usd';
    case EUR = 'eur';
    case GBP = 'gbp';
    case JPY = 'jpy';
    case CAD = 'cad';
    case AUD = 'aud';
    case CHF = 'chf';
    case CNY = 'cny';
    case PLN = 'pln';
    // Add more currencies as needed
}
