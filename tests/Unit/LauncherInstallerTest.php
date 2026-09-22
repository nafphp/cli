<?php

declare(strict_types=1);

namespace Tests\Unit;

use Naf\CLI\Support\LauncherInstaller;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use stdClass;

final class LauncherInstallerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/naf launcher ' . bin2hex(random_bytes(6));
        mkdir($this->root);
        file_put_contents($this->root . '/composer.json', '{"name":"test/host","scripts":{"post-autoload-dump":"@existing"},"extra":{}}');
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testInstallsWithoutApplicationBootstrapAndPreservesExistingHooks(): void
    {
        LauncherInstaller::install($this->root);
        $first = file_get_contents($this->root . '/composer.json');
        LauncherInstaller::install($this->root);
        self::assertSame($first, file_get_contents($this->root . '/composer.json'));
        self::assertTrue(is_executable($this->root . '/bin/naf'));
        $manifest = json_decode($first);
        self::assertSame('@existing', $manifest->scripts->{'post-autoload-dump'}[0]);
        self::assertCount(2, $manifest->scripts->{'post-autoload-dump'});
        self::assertInstanceOf(stdClass::class, $manifest->extra);
    }

    public function testExistingExecutableNeedsNoPermissionOrManifestChangesDuringTheHook(): void
    {
        LauncherInstaller::install($this->root);
        $target = $this->root . '/bin/naf';
        chmod($target, 0555);
        chmod($this->root . '/composer.json', 0444);
        LauncherInstaller::install($this->root, false);
        clearstatcache();
        self::assertSame(0555, fileperms($target) & 0777);
        self::assertSame(0444, fileperms($this->root . '/composer.json') & 0777);
    }

    public function testNeverOverwritesAnExistingLauncherOrChangesItsManifest(): void
    {
        mkdir($this->root . '/bin');
        file_put_contents($this->root . '/bin/naf', 'owner command');
        $manifest = file_get_contents($this->root . '/composer.json');

        try {
            LauncherInstaller::install($this->root);
            self::fail('Expected a collision.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('left unchanged', $exception->getMessage());
        }
        self::assertSame('owner command', file_get_contents($this->root . '/bin/naf'));
        self::assertSame($manifest, file_get_contents($this->root . '/composer.json'));
    }

    public function testNeverFollowsAnExistingDanglingSymlink(): void
    {
        mkdir($this->root . '/bin');
        symlink($this->root . '/missing', $this->root . '/bin/naf');
        $this->expectException(RuntimeException::class);
        LauncherInstaller::install($this->root);
    }

    public function testRunsFromOutsideTheApplicationAndPreservesArgumentsInputAndExitCode(): void
    {
        LauncherInstaller::install($this->root);
        mkdir($this->root . '/vendor/bin', 0777, true);
        file_put_contents($this->root . '/vendor/bin/naf', '<?php echo json_encode([$argv, getcwd(), trim(stream_get_contents(STDIN))]); exit(23);');
        [$status, $stdout, $stderr] = $this->runProcess([$this->root . '/bin/naf', 'a b', '$(touch nope)', "quote'", '', '--value=x y'], 'hello');
        self::assertSame(23, $status, $stderr);
        [$arguments, $cwd, $input] = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['vendor/bin/naf', 'a b', '$(touch nope)', "quote'", '', '--value=x y'], $arguments);
        self::assertSame($this->root, $cwd);
        self::assertSame('hello', $input);
    }

    public function testOptionalRuntimeAdapterReceivesTheOriginalArguments(): void
    {
        LauncherInstaller::install($this->root);
        file_put_contents($this->root . '/bin/naf-runtime', "#!/bin/sh\nprintf '%s\\n' \"\$@\"\nexit 17\n");
        chmod($this->root . '/bin/naf-runtime', 0755);
        [$status, $stdout] = $this->runProcess([$this->root . '/bin/naf', 'hello world', '--flag']);
        self::assertSame(17, $status);
        self::assertSame("hello world\n--flag\n", $stdout);
    }

    public function testBrokenRuntimeAdapterNeverFallsBackToLocalExecution(): void
    {
        LauncherInstaller::install($this->root);
        file_put_contents($this->root . '/bin/naf-runtime', '#!/bin/sh');
        [$status, , $stderr] = $this->runProcess([$this->root . '/bin/naf']);
        self::assertSame(1, $status);
        self::assertStringContainsString('must be an executable file', $stderr);
    }

    public function testMissingDependenciesProduceAnActionableError(): void
    {
        LauncherInstaller::install($this->root);
        [$status, , $stderr] = $this->runProcess([$this->root . '/bin/naf']);
        self::assertSame(1, $status);
        self::assertStringContainsString('composer install', $stderr);
    }

    public function testProjectRelativeCustomBinaryDirectoryIsSupported(): void
    {
        file_put_contents($this->root . '/composer.json', '{"config":{"bin-dir":"tools/it\u0027s bin"}}');
        LauncherInstaller::install($this->root);
        mkdir($this->root . "/tools/it's bin", 0777, true);
        file_put_contents($this->root . "/tools/it's bin/naf", '<?php echo "custom";');
        [$status, $stdout, $stderr] = $this->runProcess([$this->root . '/bin/naf']);
        self::assertSame(0, $status, $stderr);
        self::assertSame('custom', $stdout);
    }

    public function testHookIsHarmlessAfterThePackageIsRemoved(): void
    {
        LauncherInstaller::install($this->root);
        $manifest            = json_decode(file_get_contents($this->root . '/composer.json'), true);
        $hook                = $manifest['scripts']['post-autoload-dump'][1];
        [$status, , $stderr] = $this->runProcess(['sh', '-c', str_replace('@php', escapeshellarg(PHP_BINARY), $hook)], '', $this->root);
        self::assertSame(0, $status, $stderr);
    }

    private function runProcess(array $command, string $input = '', ?string $cwd = null): array
    {
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $cwd ?? sys_get_temp_dir());
        self::assertIsResource($process);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }
}
