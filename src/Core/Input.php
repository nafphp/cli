<?php

declare(strict_types=1);

namespace Naf\CLI\Core;

use Naf\CLI\Exception\ConsoleException;

class Input
{
    private array $definition;
    private array $arguments = [];
    private array $options = [];

    /**
     * @param array $parameters
     * @param array $definition
     *
     * @throws ConsoleException
     */
    public function __construct(array $parameters, array $definition)
    {
        $this->definition = $definition;
        $this->parse($parameters);
    }

    /**
     * @param array $parameters
     *
     * @return void
     * @throws ConsoleException
     */
    private function parse(array $parameters): void
    {
        $options   = [];
        $arguments = [];

        $i = 0;
        while ($i < count($parameters)) {
            $parameter = $parameters[$i];

            if (str_starts_with($parameter, '--')) {
                // --option or --option=value
                $parts = explode('=', substr($parameter, 2), 2);
                $key   = $parts[0];
                $value = $parts[1] ?? null;

                if ($value === null && isset($parameters[$i + 1]) && !str_starts_with($parameters[$i + 1], '-')) {
                    $value = $parameters[++$i];
                }

                $options[$key][] = $value ?? true;
            } elseif (str_starts_with($parameter, '-')) {
                // -o value
                $key = substr($parameter, 1);
                $value = null;

                if (isset($parameters[$i + 1]) && !str_starts_with($parameters[$i + 1], '-')) {
                    $value = $parameters[++$i];
                }

                $options[$key][] = $value ?? true;
            } else {
                $arguments[] = $parameter;
            }

            $i++;
        }

        // Argumente aus Definition zuweisen
        $definitionArgs = $this->definition['arguments'] ?? [];

        $argumentMap = [];
        $index = 0;
        foreach ($definitionArgs as $name => $isOptional) {
            if (array_key_exists($index, $arguments)) {
                $argumentMap[$name] = $arguments[$index++];
            } elseif (!$isOptional) {
                throw new ConsoleException("Argument <$name> is required but missing.");
            } else {
                $argumentMap[$name] = null;
            }
        }

        if ($index < count($arguments)) {
            throw new ConsoleException("Too many arguments provided.");
        }

        $this->arguments = $argumentMap;

        // Optionen aus Definition zuweisen
        $definitionOpts = $this->definition['options'] ?? [];
        foreach ($options as $name => $values) {
            if (array_key_exists($name, $definitionOpts)) {
                $this->options[$name] = is_array($values)
                    ? (count($values) === 1 ? $values[0] : $values)
                    : $values;
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getArgument(string $name): ?string
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @param string $name
     *
     * @return string|array|bool|null
     */
    public function getOption(string $name): string|array|bool|null
    {
        if (!isset($this->options[$name])) {
            return null;
        }

        $value = $this->options[$name];

        if (is_array($value)) {
            return count($value) === 1 ? $value[0] : $value;
        }

        return $value;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function ask(string $message): string
    {
        echo $message . ' ';
        $input = trim(fgets(STDIN));
        if ($input !== '' && function_exists('readline_add_history')) {
            readline_add_history($input);
        }
        return $input;
    }
}