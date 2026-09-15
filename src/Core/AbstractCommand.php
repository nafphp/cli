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

    public function showHelp(Output $output): void
    {
        $output->writeLine('');
        $output->writeLine('  <info>' . ($this->getTitle() ?? static::NAME) . '</info>');
        $output->writeLine('  ' . $this->getDescription());
        $output->writeLine('');

        $definition = $this->getDefinition();

        if (!empty($definition['arguments'])) {
            $output->writeLine('  <comment>Arguments:</comment>');
            foreach ($definition['arguments'] as $name => $mode) {
                $optional = $mode === 'optional' ? ' (optional)' : '';
                $output->writeLine("    <info>$name</info>$optional");
            }
            $output->writeLine('');
        }

        if (!empty($definition['options'])) {
            $output->writeLine('  <comment>Options:</comment>');
            foreach ($definition['options'] as $name => $mode) {
                $expectsValue = $mode === 'value' ? ' [=value]' : '';
                $output->writeLine("    <info>--$name</info>$expectsValue");
            }
            $output->writeLine('');
        }

        $output->writeLine('  <comment>Help:</comment>');
        $output->writeLine('    Use <info>php cli.php command-name --help</info> for this screen.');
        $output->writeLine('');
    }

    abstract protected function configure(): void;

    abstract public function run(Input $input, Output $output): int;
}
