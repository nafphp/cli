<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BinaryTest extends TestCase
{
    public function testComposerBinaryFindsTheApplicationOutsideItsWorkingDirectory(): void
    {
        $root = dirname(__DIR__, 2);
        $code = sprintf(
            '$GLOBALS["_composer_autoload_path"] = %s; $argv = ["vendor/bin/naf"]; require %s;',
            var_export($root . '/vendor/bin/../autoload.php', true),
            var_export($root . '/bin/naf', true),
        );
        $process = proc_open(
            [PHP_BINARY, '-r', $code],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            sys_get_temp_dir(),
        );

        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($process), $stderr);
        self::assertStringContainsString('Registered commands', $stdout);
        self::assertStringNotContainsString('deprecated', strtolower($stdout . $stderr));
    }
}
