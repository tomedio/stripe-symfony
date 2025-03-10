<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Enum;

enum BillingInterval: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
