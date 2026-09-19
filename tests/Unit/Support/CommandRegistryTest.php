<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Naf\CLI\Support\CommandRegistry;
use Tests\NafTestCase;

class TestRegistryCommand
{
    public const NAME = 'test:registry';
}

class SecondTestRegistryCommand
{
    public const NAME = 'second:registry';
}

class CommandRegistryTest extends NafTestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CommandRegistry();
    }

    public function testAddCommand(): void
    {
        $this->registry->add(TestRegistryCommand::class);

        $this->assertSame(
            TestRegistryCommand::class,
            $this->registry->get(TestRegistryCommand::NAME),
        );
    }

    public function testAddCommandTwiceDoesNotDuplicate(): void
    {
        $this->registry->add(TestRegistryCommand::class);
        $this->registry->add(TestRegistryCommand::class);

        $commands = $this->registry->all();

        $this->assertCount(1, $commands);
        $this->assertSame(
            TestRegistryCommand::class,
            $commands[TestRegistryCommand::NAME],
        );
    }

    public function testGetReturnsNullForNonExistentCommand(): void
    {
        $this->assertNull($this->registry->get('non:existent'));
    }

    public function testGetAllReturnsAllCommands(): void
    {
        $this->registry->add(TestRegistryCommand::class);
        $this->registry->add(SecondTestRegistryCommand::class);

        $commands = $this->registry->all();

        $this->assertCount(2, $commands);
        $this->assertSame(
            TestRegistryCommand::class,
            $commands[TestRegistryCommand::NAME],
        );
        $this->assertSame(
            SecondTestRegistryCommand::class,
            $commands[SecondTestRegistryCommand::NAME],
        );
    }

    public function testClearRemovesAllCommands(): void
    {
        $this->registry->add(TestRegistryCommand::class);
        $this->registry->add(SecondTestRegistryCommand::class);

        $this->registry->clear();

        $this->assertEmpty($this->registry->all());
    }
}
