<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case PAID = 'paid';
    case UNCOLLECTIBLE = 'uncollectible';
    case VOID = 'void';
}
