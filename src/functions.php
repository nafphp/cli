<?php

declare(strict_types=1);

namespace Naf\CLI;

use Naf\CLI\Support\CommandRegistry;
use function Naf\app;

function command(): CommandRegistry
{
    return app()->container()->get(CommandRegistry::class);
}