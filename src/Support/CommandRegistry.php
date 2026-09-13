<?php

declare(strict_types=1);

namespace Naf\CLI\Support;

class CommandRegistry
{
    protected array $commands = [];

    public function __construct(array $commands = [])
    {
        $this->commands = $commands;
    }

    public function add(string $class): void
    {
        if (!in_array($class, $this->commands, true)) {
            $this->commands[$class::NAME] = $class;
        }
    }

    public function get(string $name): ?string
    {
        return $this->commands[$name] ?? null;
    }

    public function all(): array
    {
        return $this->commands;
    }

    public function clear(): void
    {
        $this->commands = [];
    }
}
