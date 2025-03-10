<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Enum;

enum CreditTransactionType: string
{
    case PURCHASE = 'purchase';
    case USAGE = 'usage';
    case EXPIRATION = 'expiration';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
    case GIFT = 'gift';
}
