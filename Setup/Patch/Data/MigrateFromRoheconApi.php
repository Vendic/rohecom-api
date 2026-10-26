<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Setup\Patch\Data;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Carries data over from the module's former name Vendic_RoheconApi: the ACL resources granted to
 * the UniShop integration and the module configuration. No-op on a fresh install.
 */
class MigrateFromRoheconApi implements DataPatchInterface
{
    private const string OLD_MODULE = 'Vendic_RoheconApi';
    private const string NEW_MODULE = 'Vendic_RohecomApi';
    private const string OLD_CONFIG_SECTION = 'vendic_rohecon_api/';
    private const string NEW_CONFIG_SECTION = 'vendic_rohecom_api/';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();

        $connection->update(
            $this->moduleDataSetup->getTable('authorization_rule'),
            ['resource_id' => $this->replaceExpression('resource_id', self::OLD_MODULE, self::NEW_MODULE)],
            ['resource_id LIKE ?' => self::OLD_MODULE . '::%']
        );

        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['path' => $this->replaceExpression('path', self::OLD_CONFIG_SECTION, self::NEW_CONFIG_SECTION)],
            ['path LIKE ?' => self::OLD_CONFIG_SECTION . '%']
        );

        $connection->delete(
            $this->moduleDataSetup->getTable('setup_module'),
            ['module = ?' => self::OLD_MODULE]
        );

        return $this;
    }

    private function replaceExpression(string $column, string $search, string $replace): Expression
    {
        $connection = $this->moduleDataSetup->getConnection();

        return new Expression(sprintf(
            'REPLACE(%s, %s, %s)',
            $connection->quoteIdentifier($column),
            $connection->quote($search),
            $connection->quote($replace)
        ));
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
