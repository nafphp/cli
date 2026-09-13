<?php

declare(strict_types=1);

namespace Naf\CLI\Core;

use Naf\CLI\Commands\ListCommand;
use Naf\CLI\Exception\ConsoleException;
use Naf\CLI\Support\CommandRegistry;
use function Naf\app;

class Console
{
    /**
     * Conventional shell statuses. A command is free to return another value and
     * it is passed through unchanged; these are what the console itself uses.
     */
    public const int SUCCESS = 0;
    public const int ERROR   = 1;

    /**
     * @param CommandRegistry $registry
     */
    public function __construct(
        private readonly CommandRegistry $registry
    ) {
    }

    /**
     * Runs a command and reports how it went.
     *
     * The status a command returns is the status the shell gets. Discarding it
     * meant every invocation looked successful — a failed migration, a diagnosis
     * that found problems, a command that does not exist — which is exactly the
     * thing a script or a CI step reads to decide what happens next.
     *
     * @param array $parameters
     *
     * @return int Exit status: zero when the command succeeded, non-zero otherwise.
     */
    public function run(array $parameters): int
    {
        // The first argument is the bin/console command itself
        array_shift($parameters);

        $commandName = array_shift($parameters);

        if (empty($commandName)) {
            $commandName = 'command:list';
        }

        $commandClass = $this->registry->get($commandName);

        try {

            if (null === $commandClass) {
                throw new ConsoleException(
                    sprintf('Command "%s" not found', $commandName)
                );
            }

            if ($commandClass === ListCommand::class || $commandName === ListCommand::NAME) {
                $object = new ListCommand();
                $object->setCommands($this->registry->all());
            } else {
                /** @var AbstractCommand $object */
                $object = app()->container()->make($commandClass);
            }

            $definition = $object->getDefinition();

            $input = new Input($parameters, $definition);
            $output = new Output();

            if ($object->getTitle()) {
                $output->drawStroke(strlen($object->getTitle()) + 3, '-');
                $output->writeLine(sprintf(' %s', $object->getTitle()), 'title');
                $output->drawStroke(strlen($object->getTitle()) + 3, '-');
                $output->writeEmptyLine();
            }

            $status = $object->run($input, $output);

        } catch (\Exception $e) {
            print PHP_EOL;
            print $e->getMessage();
            print PHP_EOL;

            // It printed something, but it did not do what it was asked to.
            $status = self::ERROR;
        }

        print PHP_EOL;

        return $status;
    }

}