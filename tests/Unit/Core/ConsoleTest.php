<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Naf\CLI\Core\AbstractCommand;
use Naf\CLI\Core\Console;
use Naf\CLI\Core\Input;
use Naf\CLI\Core\Output;
use Naf\CLI\Support\CommandRegistry;
use Tests\NafTestCase;

class TestConsoleCommand extends AbstractCommand
{
    public const NAME = 'test:command';

    protected function configure(): void
    {
        $this->setTitle('Test Command')
            ->setDescription('Test command description');
    }

    public function run(Input $input, Output $output): int
    {
        // Einfacher Testbefehl
        return self::SUCCESS;
    }
}

class ErrorConsoleCommand extends AbstractCommand
{
    public const NAME = 'error:command';

    protected function configure(): void
    {
        // Keine Konfiguration notwendig für Test
    }

    public function run(Input $input, Output $output): int
    {
        throw new \Exception('Test error');
    }
}

class FailingConsoleCommand extends AbstractCommand
{
    public const NAME = 'failing:command';

    protected function configure(): void
    {
    }

    public function run(Input $input, Output $output): int
    {
        return self::ERROR;
    }
}

class ConsoleTest extends NafTestCase
{
    private CommandRegistry $registry;
    private Console $console;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(CommandRegistry::class);
        $this->console = new Console($this->registry);
    }

    public function testRunWithEmptyCommandCallsListCommand(): void
    {
        // Mock für CommandRegistry vorbereiten
        $this->registry->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->registry->expects($this->once())
            ->method('get')
            ->with('command:list')
            ->willReturn(TestConsoleCommand::class);

        // Ausgabe puffern
        ob_start();
        $this->console->run(['script.php']);
        ob_end_clean();
    }

    public function testRunWithCommandName(): void
    {
        // Mock für CommandRegistry vorbereiten
        $this->registry->expects($this->never())
            ->method('all');

        $this->registry->expects($this->once())
            ->method('get')
            ->with('test:command')
            ->willReturn(TestConsoleCommand::class);

        // Ausgabe puffern
        ob_start();
        $this->console->run(['script.php', 'test:command']);
        ob_end_clean();
    }

    public function testRunWithListCommandName(): void
    {
        // Mock für CommandRegistry vorbereiten
        $this->registry->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->registry->expects($this->once())
            ->method('get')
            ->with('command:list')
            ->willReturn(TestConsoleCommand::class);

        // Ausgabe puffern
        ob_start();
        $this->console->run(['script.php', 'command:list']);
        ob_end_clean();
    }

    public function testRunWithNonExistentCommandShowsError(): void
    {
        // Mock für CommandRegistry vorbereiten
        $this->registry->expects($this->once())
            ->method('get')
            ->with('non:existent')
            ->willReturn(null);

        // Ausgabe puffern
        ob_start();
        $this->console->run(['script.php', 'non:existent']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Command "non:existent" not found', $output);
    }

    public function testRunWithExceptionInCommandShowsError(): void
    {
        // Mock für CommandRegistry vorbereiten
        $this->registry->expects($this->once())
            ->method('get')
            ->with('error:command')
            ->willReturn(ErrorConsoleCommand::class);

        // Ausgabe puffern
        ob_start();
        $this->console->run(['script.php', 'error:command']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test error', $output);
    }

    // ------------------------------------------------------- Exit statuses

    public function testASuccessfulCommandReportsSuccess(): void
    {
        $this->registry->expects($this->once())
            ->method('get')
            ->with('test:command')
            ->willReturn(TestConsoleCommand::class);

        ob_start();
        $status = $this->console->run(['script.php', 'test:command']);
        ob_end_clean();

        $this->assertSame(Console::SUCCESS, $status);
    }

    public function testAFailingCommandReportsItsOwnStatus(): void
    {
        // Whatever a command returns is what the shell gets. Discarding it made
        // every invocation look successful, which is the one thing a script or a
        // CI step reads to decide what happens next.
        $this->registry->expects($this->once())
            ->method('get')
            ->with('failing:command')
            ->willReturn(FailingConsoleCommand::class);

        ob_start();
        $status = $this->console->run(['script.php', 'failing:command']);
        ob_end_clean();

        $this->assertNotSame(Console::SUCCESS, $status);
    }

    public function testACommandThatThrowsDoesNotLookSuccessful(): void
    {
        $this->registry->expects($this->once())
            ->method('get')
            ->with('error:command')
            ->willReturn(ErrorConsoleCommand::class);

        ob_start();
        $status = $this->console->run(['script.php', 'error:command']);
        ob_end_clean();

        $this->assertSame(Console::ERROR, $status);
    }

    public function testAnUnknownCommandDoesNotLookSuccessful(): void
    {
        $this->registry->expects($this->once())
            ->method('get')
            ->with('non:existent')
            ->willReturn(null);

        ob_start();
        $status = $this->console->run(['script.php', 'non:existent']);
        ob_end_clean();

        $this->assertSame(Console::ERROR, $status);
    }
}
