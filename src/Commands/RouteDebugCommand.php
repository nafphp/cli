<?php

declare(strict_types=1);

namespace Naf\CLI\Commands;

use Naf\CLI\Core\AbstractCommand;
use Naf\CLI\Core\Input;
use Naf\CLI\Core\Output;

use function Naf\route;

class RouteDebugCommand extends AbstractCommand
{
    public const string NAME = 'route:debug';

    protected function configure(): void
    {
        $this->setTitle('Route debug')
            ->setDescription('Displays current routes for an application.');
    }

    public function run(Input $input, Output $output): int
    {
        $routes       = route()->all();
        $longestChars = 0;
        $data         = [];

        foreach ($routes as $name => $route) {

            if (is_array($route['action'])) {
                [$class, $action] = $route['action'];
            } elseif (is_callable($route['action'])) {
                $class  = 'Anonymous';
                $action = 'Closure';
            } else {
                $class  = 'none';
                $action = 'none';
            }

            $path   = $route['path'];
            $method = $route['method'];

            $methodAndPath       = '[' . $method . '] ' . $path;
            $methodAndPathLength = strlen($methodAndPath);

            if ($methodAndPathLength > $longestChars) {
                $longestChars = $methodAndPathLength;
            }

            $data[$name] = [
                'name'   => $name,
                'path'   => $path,
                'method' => $method,
                'class'  => $class,
                'action' => $action,
            ];

            usort($data, function ($a, $b) {
                return strlen($a['path']) <=> strlen($b['path']);
            });

        }

        foreach ($data as $route) {

            $output->writeLine($route['name']);
            $output->writeLine(str_pad('[' . $route['method'] . '] ' . $route['path'], $longestChars + 5) . $route['class'] . '::' . $route['action']);
            $output->writeEmptyLine();

        }

        return self::SUCCESS;

    }
}
