# Working on naf/cli

NAF is a small PHP framework with optional Composer plugins. Its core owns boot,
configuration, the service container, routing, events and PSR-7 responses. Prefer existing
NAF helpers, services and extension interfaces; keep application business rules in the host.
This package declares `type: naf-plugin` and is discovered after installation in a NAF host.
The plugin repository itself is not the application's web root.

Before changing code, read the [shared contribution workflow](https://github.com/nafphp/docs/blob/main/AGENT_WORKFLOW.md)
and [release procedure](https://github.com/nafphp/docs/blob/main/RELEASING.md).
In the multi-repository workspace, the same documents are in the sibling `docs/` checkout;
use the linked copies when working from a standalone clone. Preserve other contributors' work.
Review and update user documentation with every behavior change. Source fixes use an RC branch;
verified documentation-only changes can be merged and published by the agent.

## What this plugin does

`naf/cli` provides the host application's console, input/output helpers and command registry.
Install with `composer require naf/cli`; run `vendor/bin/naf command:list` from the host root.
Check `composer.json` for PHP and `ext-readline` requirements. Reuse this console for commands
provided by other plugins instead of adding independent command dispatchers.

## Use it

Create a PSR-4-autoloaded host class, for example `app/Commands/PingCommand.php`:

```php
<?php
namespace App\Commands;

use Naf\CLI\Core\{AbstractCommand, Input, Output};

final class PingCommand extends AbstractCommand
{
    public const string NAME = 'app:ping';

    protected function configure(): void
    {
        $this->setTitle('Ping')->setDescription('Check that the command is registered.');
    }

    public function run(Input $input, Output $output): int
    {
        $output->writeLine('pong');
        return self::SUCCESS;
    }
}
```

In host or plugin bootstrap, after the CLI plugin has booted:

```php
<?php
use App\Commands\PingCommand;
use function Naf\CLI\command;

command()->add(PingCommand::class);
```

Run `vendor/bin/naf app:ping`. Commands are built through the default container. If adding
an injected constructor, call `parent::__construct()` so `configure()` still runs.

## Change it here

[Console](src/Core/Console.php) owns dispatch, [Input](src/Core/Input.php) parsing,
[Output](src/Core/Output.php) formatting, and [CommandRegistry](src/Support/CommandRegistry.php)
registration. Inspect the existing commands for argument, option and help conventions.
Keep `NAME`, registration, help text and documented invocations aligned. Plugin discovery
is not proof its command-registry binding has already booted; respect dependency order.

## Verify

Run `composer test` and `composer validate --strict`. Exercise changed commands through the
console in a bootstrapped host, including help, bad input, exit codes and constructor injection.
The [tests](tests/) provide examples. No `analyse` script is declared.

User docs: [Console](https://nafphp.github.io/docs/console/).

Follow the shared [PHP code style](https://github.com/nafphp/docs/blob/main/CODE_STYLE.md)
and `.php-cs-fixer.dist.php`. Run `composer style:check`; `composer style:fix` applies the rules.
Keep logical steps and local names readable, preserving public signatures and template output.

`vendor/bin/naf plugins:debug` explains the computed plugin order and absent optional
targets when the host framework supports automatic plugin ordering. On older frameworks
it returns a clear unsupported diagnostic and a nonzero exit code.
