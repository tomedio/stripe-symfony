<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle;

use Tomedio\StripeBundle\DependencyInjection\StripeBundleExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class StripeBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new StripeBundleExtension();
        }

        return $this->extension;
    }
}
