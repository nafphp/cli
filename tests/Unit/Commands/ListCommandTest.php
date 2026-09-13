<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Naf\CLI\Commands\ListCommand;
use Naf\CLI\Core\AbstractCommand;
use Naf\CLI\Core\Input;
use Naf\CLI\Core\Output;
use Naf\CLI\Exception\ConsoleException;
use Tests\NafTestCase;

class TestCommand1 extends AbstractCommand
{
    public const string NAME = 'test:command1';
    
    protected function configure(): void
    {
        $this->setTitle('Test Command 1')
            ->setDescription('Test command 1 description');
    }
    
    public function run(Input $input, Output $output): int
    {
        return self::SUCCESS;
    }
}

class TestCommand2 extends AbstractCommand
{
    public const string NAME = 'test:command2';
    
    protected function configure(): void
    {
        $this->setTitle('Test Command 2')
            ->setDescription('Test command 2 description');
    }
    
    public function run(Input $input, Output $output): int
    {
        return self::SUCCESS;
    }
}

class InvalidCommand
{
    // This class has no name constant
    public function someMethod()
    {
        return true;
    }
}

class ListCommandTest extends NafTestCase
{
    private ListCommand $command;
    private array $commands;

    protected function setUp(): void
    {
        $this->commands = [
            TestCommand1::class,
            TestCommand2::class,
            'Naf\CLI\Commands\ListCommand',
        ];
        
        $this->command = new ListCommand();
        $this->command->setCommands($this->commands);
    }

    public function testConfigureSetsCorrectTitleAndDescription(): void
    {
        $this->assertSame('Registered commands', $this->command->getTitle());
        $this->assertSame('List all registered commands.', $this->command->getDescription());
    }

    public function testRunSkipsListCommandItself(): void
    {
        $output = $this->createMock(Output::class);
        $input = $this->createStub(Input::class);

        $output->expects($this->exactly(4))
            ->method('writeLine');
        
        $output->expects($this->exactly(2))
            ->method('writeEmptyLine');
        
        $result = $this->command->run($input, $output);
        
        $this->assertSame(0, $result);
    }

    public function testRunThrowsExceptionForInvalidCommandClass(): void
    {
        $this->expectException(ConsoleException::class);
        
        $invalidCommands = [
            InvalidCommand::class,
        ];
        
        $command = new ListCommand();
        $command->setCommands($invalidCommands);
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);
        
        $command->run($input, $output);
    }
}
