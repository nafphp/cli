<?php

declare(strict_types=1);

namespace Naf\CLI\Commands;

use Naf\CLI\Core\AbstractCommand;
use Naf\CLI\Core\Input;
use Naf\CLI\Core\Output;

use function Naf\app;

final class PluginDebugCommand extends AbstractCommand
{
    public const string NAME = 'plugins:debug';

    protected function configure(): void
    {
        $this->setTitle('Plugin boot order')
            ->setDescription('Displays plugin boot order, declared prerequisites and absent optional targets.');
    }

    public function run(Input $input, Output $output): int
    {
        $app = app();
        if (!method_exists($app, 'getPluginBootPlan')) {
            $output->writeLine('Boot dependency diagnostics require a framework with automatic plugin ordering.', 'error');

            return 1;
        }
        $position = 0;
        foreach ($app->getPluginBootPlan() as $package => $details) {
            $output->writeLine(sprintf('%d. %s', ++$position, $package));
            foreach ($details['after'] as $prerequisite => $sources) {
                $output->writeLine('   after ' . $prerequisite . ' (' . implode('; ', $sources) . ')');
            }
            foreach ($details['ignored'] as $relation) {
                $output->writeLine('   skipped: ' . $relation);
            }
        }

        return self::SUCCESS;
    }
}
