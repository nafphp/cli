<?php

declare(strict_types=1);

namespace Naf\CLI\Core;

abstract class AbstractCommand
{
    public const NAME = null;

    protected const int SUCCESS = 0;
    protected const int ERROR   = 1;

    private string $title       = '';
    private string $description = '';

    private array $arguments = [];
    private array $options   = [];

    public function __construct()
    {
        $this->configure();
    }

    /**
     * @return array
     */
    public function getDefinition(): array
    {
        return [
            'arguments' => $this->arguments,
            'options'   => $this->options,
        ];
    }

    protected function addArgument(string $name, bool $optional = false): self
    {
        $this->arguments[$name] = $optional ? 'optional' : 'required';

        return $this;
    }

    protected function addOption(string $name, ?string $shortcut = '', bool $expectsValue = false): self
    {
        $this->options[$name] = $expectsValue ? 'value' : 'flag';

        if ($shortcut) {
            $this->options[$shortcut] = $expectsValue ? 'value' : 'flag';
        }

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    protected function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $description
     * @return $this
     */
    protected function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Print the command's usage, arguments and options
     *
     * Output has no markup language: tags such as `<info>` were printed literally,
     * so the help uses the line types Output knows and plain text.
     */
    public function showHelp(Output $output): void
    {
        $definition = $this->getDefinition();
        $arguments  = $definition['arguments'] ?? [];
        $options    = $this->describeOptions($definition['options'] ?? []);

        $usage = 'vendor/bin/naf ' . static::NAME;
        foreach ($arguments as $name => $mode) {
            $usage .= $mode === 'optional' ? " [<$name>]" : " <$name>";
        }
        if ($options !== []) {
            $usage .= ' [options]';
        }

        $output->writeEmptyLine();
        $output->writeLine($this->getTitle() ?: (string) static::NAME, 'title');
        if ($this->getDescription() !== '') {
            $output->writeLine('  ' . $this->getDescription());
        }
        $output->writeEmptyLine();

        $output->writeLine('Usage:', 'headline');
        $output->writeLine('  ' . $usage);

        if ($arguments !== []) {
            $output->writeEmptyLine();
            $output->writeLine('Arguments:', 'headline');
            foreach ($arguments as $name => $mode) {
                $output->writeLine('  ' . $name . ($mode === 'optional' ? ' (optional)' : ''));
            }
        }

        if ($options !== []) {
            $output->writeEmptyLine();
            $output->writeLine('Options:', 'headline');
            foreach ($options as $option) {
                $output->writeLine('  ' . $option);
            }
        }

        $output->writeEmptyLine();
    }

    /**
     * One line per option, with its shortcut in front
     *
     * addOption() records a shortcut as its own entry right after the option.
     *
     * @param array<string, string> $options Option or shortcut name => 'flag' or 'value'
     *
     * @return list<string>
     */
    private function describeOptions(array $options): array
    {
        $lines    = [];
        $previous = null;

        foreach ($options as $name => $mode) {
            $name = (string) $name;

            if (strlen($name) === 1 && $previous !== null) {
                $lines[array_key_last($lines)] = "-$name, " . $lines[array_key_last($lines)];
                $previous                      = null;
                continue;
            }

            $lines[]  = '--' . $name . ($mode === 'value' ? '=VALUE' : '');
            $previous = $name;
        }

        return $lines;
    }

    abstract protected function configure(): void;

    abstract public function run(Input $input, Output $output): int;
}
