<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Cron;

use Throwable;
use Vendic\RohecomApi\Service\SwatchTextUpdater;

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
