<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Vendic\RoheconApi\Service\SwatchTextUpdater;

class UpdateSizeSwatchTextCommand extends Command
{
    private const string OPTION_ATTRIBUTE = 'attribute';

    public function __construct(
        private readonly SwatchTextUpdater $swatchTextUpdater,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('vendic:rohecom:update-size-swatch-text')
            ->setDescription('Backfill text swatch values for an attribute across all store views')
            ->addOption(
                self::OPTION_ATTRIBUTE,
                null,
                InputOption::VALUE_REQUIRED,
                'Attribute code'
            );

        parent::configure();
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $attribute = (string)$input->getOption(self::OPTION_ATTRIBUTE);

        $this->swatchTextUpdater->execute($attribute);

        $output->writeln(sprintf('<info>Updated text swatches for "%s".</info>', $attribute));

        return Command::SUCCESS;
    }
}
