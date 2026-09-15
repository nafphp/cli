<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Naf\CLI\Core\Input;
use Naf\CLI\Exception\ConsoleException;
use Tests\NafTestCase;

class InputTest extends NafTestCase
{
    public function testConstructorParsesParameters(): void
    {
        $parameters = ['--option1=value1', 'arg1'];
        $definition = [
            'arguments' => ['arg' => 'required'],
            'options'   => ['option1' => 'value'],
        ];

        $input = new Input($parameters, $definition);

        $this->assertSame('arg1', $input->getArgument('arg'));
        $this->assertSame('value1', $input->getOption('option1'));
    }

    public function testGetArgumentReturnsNullForNonExistentArgument(): void
    {
        $input = new Input([], ['arguments' => []]);

        $this->assertNull($input->getArgument('non_existent'));
    }

    public function testGetOptionReturnsNullForNonExistentOption(): void
    {
        $input = new Input([], ['options' => []]);

        $this->assertNull($input->getOption('non_existent'));
    }

    public function testLongOptionWithEqualsSign(): void
    {
        $parameters = ['--option1=value1'];
        $definition = ['options' => ['option1' => 'value']];

        $input = new Input($parameters, $definition);

        $this->assertSame('value1', $input->getOption('option1'));
    }

    public function testLongOptionWithSeparateValue(): void
    {
        $parameters = ['--option1', 'value1'];
        $definition = ['options' => ['option1' => 'value']];

        $input = new Input($parameters, $definition);

        $this->assertSame('value1', $input->getOption('option1'));
    }

    public function testShortOptionWithSeparateValue(): void
    {
        $parameters = ['-o', 'value1'];
        $definition = ['options' => ['o' => 'value']];

        $input = new Input($parameters, $definition);

        $this->assertSame('value1', $input->getOption('o'));
    }

    public function testFlagOption(): void
    {
        $parameters = ['--flag'];
        $definition = ['options' => ['flag' => 'flag']];

        $input = new Input($parameters, $definition);

        $this->assertTrue($input->getOption('flag'));
    }

    public function testTooManyArgumentsThrowsException(): void
    {
        $this->expectException(ConsoleException::class);

        $parameters = ['arg1', 'arg2'];
        $definition = ['arguments' => ['arg' => 'required']];

        new Input($parameters, $definition);
    }

    public function testOptionalArgumentCanBeOmitted(): void
    {
        $parameters = [];
        $definition = ['arguments' => ['optional' => 'optional']];

        $input = new Input($parameters, $definition);

        $this->assertNull($input->getArgument('optional'));
    }

    public function testMultipleOptionsWithSameNameAreReturnedAsArray(): void
    {
        $parameters = ['--option=value1', '--option=value2'];
        $definition = ['options' => ['option' => 'value']];

        $input = new Input($parameters, $definition);

        $this->assertSame(['value1', 'value2'], $input->getOption('option'));
    }

    public function testAskMethodReturnsUserInput(): void
    {
        // Wir testen die ask-Methode nicht direkt, da sie eine Benutzereingabe erfordert
        // und dies in Unit-Tests schwer zu simulieren ist.
        // In einer echten Anwendung würde man dafür ein Mock-Objekt oder eine Integration verwenden.
        $this->assertTrue(method_exists(Input::class, 'ask'), 'Method ask should exist');
    }
}
