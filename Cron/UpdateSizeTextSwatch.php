<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Cron;

use Throwable;
use Vendic\RoheconApi\Service\SwatchTextUpdater;

class UpdateSizeTextSwatch
{
    private const string ATTRIBUTE = 'auto_maat';

    public function __construct(private readonly SwatchTextUpdater $swatchTextUpdater)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(): void
    {
        $this->swatchTextUpdater->execute(self::ATTRIBUTE);
    }
}
