<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Naf\CLI\Commands\PluginDebugCommand;
use Naf\CLI\Core\Input;
use Naf\CLI\Core\Output;
use Naf\Core\App;
use Naf\Support\AppHolder;
use PHPUnit\Framework\TestCase;

use function Naf\app;

final class PluginDebugCommandTest extends TestCase
{
    public function testExplainsPrerequisitesAndMissingOptionalTargets(): void
    {
        $before = app();
        AppHolder::set(new class extends App {
            public function __construct()
            {
            }

            public function getPluginBootPlan(): array
            {
                return [
                    'test/cli'    => ['after' => [], 'ignored' => []],
                    'test/plugin' => [
                        'after'   => ['test/cli' => ['test/plugin extra.naf.boot.after']],
                        'ignored' => ['after optional/plugin (not installed)'],
                    ],
                ];
            }
        });

        try {
            $lines  = [];
            $output = $this->createMock(Output::class);
            $output->expects($this->exactly(4))->method('writeLine')->willReturnCallback(
                static function (string $line) use (&$lines): void {
                    $lines[] = $line;
                },
            );
            $status = (new PluginDebugCommand())->run($this->createStub(Input::class), $output);
            $this->assertSame(0, $status);
            $this->assertSame([
                '1. test/cli',
                '2. test/plugin',
                '   after test/cli (test/plugin extra.naf.boot.after)',
                '   skipped: after optional/plugin (not installed)',
            ], $lines);
        } finally {
            AppHolder::set($before);
        }
    }
}
