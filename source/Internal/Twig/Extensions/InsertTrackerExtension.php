<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

/**
 * Class InsertTrackerExtension
 *
 * @package OxidEsales\EshopCommunity\Internal\Twig\Extensions
 */
class InsertTrackerExtension extends AbstractExtension
{

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [new TwigFunction('insert_tracker', [$this, 'insertTracker'], ['needs_environment' => true])];
    }

    /**
     * @param Environment $env
     * @param array       $params
     *
     * @return string
     */
    public function insertTracker(Environment $env = null, $params = []): string
    {
        return '';
    }
}
