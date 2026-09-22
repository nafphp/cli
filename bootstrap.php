<?php

declare(strict_types=1);

use Naf\CLI\Commands\ListCommand;
use Naf\CLI\Commands\PluginDebugCommand;
use Naf\CLI\Commands\RouteDebugCommand;
use Naf\CLI\Support\CommandRegistry;

use function Naf\app;

app()->container()->set(CommandRegistry::class, function () {
    $commandRegistry = new CommandRegistry();
    $commandRegistry->add(ListCommand::class);
    $commandRegistry->add(RouteDebugCommand::class);
    $commandRegistry->add(PluginDebugCommand::class);

    return $commandRegistry;
});
