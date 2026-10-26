<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Service;

use Composer\InstalledVersions;

/**
 * Resolves the module version from Composer's installed metadata; empty string when not Composer-installed.
 */
class ModuleVersion
{
    private const string PACKAGE_NAME = 'vendic/rohecom-api';

    /**
     * Set version to 2.0.6 since integration of rohecom software based on hardcoded 2.0.6 version.
     *
     * @var string|null
     */
    private ?string $version = '2.0.6';

    public function get(): string
    {
        if ($this->version === null) {
            $this->version = $this->resolveVersion();
        }

        return $this->version;
    }

    private function resolveVersion(): string
    {
        if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled(self::PACKAGE_NAME)) {
            return '';
        }

        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) ?? '';
    }
}
