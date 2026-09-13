<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Naf\CLI\Core\Output;
use Tests\NafTestCase;

class OutputTest extends NafTestCase
{
    private Output $output;

    protected function setUp(): void
    {
        $this->output = new Output();
    }

    public function testWriteLineMethodExists(): void
    {
        // Da die Ausgabe direkt auf das Terminal geht, testen wir nur, ob die Methode existiert
        $this->assertTrue(method_exists($this->output, 'writeLine'));
    }

    public function testWriteEmptyLineMethodExists(): void
    {
        $this->assertTrue(method_exists($this->output, 'writeEmptyLine'));
    }

    public function testDrawStrokeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->output, 'drawStroke'));
    }

    public function testOutputClassHasCorrectProperties(): void
    {
        $reflection = new \ReflectionClass(Output::class);
        
        $this->assertTrue($reflection->hasProperty('foregroundColors'));
        $this->assertTrue($reflection->hasProperty('backgroundColors'));
        
        $constants = $reflection->getConstants();
        $this->assertArrayHasKey('OUTPUT_TYPE_OK', $constants);
        $this->assertArrayHasKey('OUTPUT_TYPE_ERROR', $constants);
        $this->assertArrayHasKey('COLOR_MAPPING', $constants);
    }

    public function testOutputMethodsDoNotThrowExceptions(): void
    {
        // Stellen Sie sicher, dass keine Ausnahmen ausgelöst werden
        $this->expectNotToPerformAssertions();
        
        // Puffern Sie die Ausgabe, um sie zu erfassen, ohne den Testlauf zu stören
        ob_start();
        
        $this->output->writeLine('Test message');
        $this->output->writeLine('Test ok message', 'ok');
        $this->output->writeLine('Test error message', 'error');
        $this->output->writeLine('Test warning message', 'warning');
        $this->output->writeLine('Test title', 'title');
        $this->output->writeLine('Test headline', 'headline');
        
        $this->output->writeEmptyLine();
        $this->output->drawStroke(10, '-');
        
        // Bereinigen Sie den Ausgabepuffer
        ob_end_clean();
    }

    public function testColoredOutputsHaveDifferentFormattingCodes(): void
    {
        // Erfassen Sie die Ausgabe für verschiedene Typen
        ob_start();
        $this->output->writeLine('Test message', 'ok');
        $okOutput = ob_get_clean();
        
        ob_start();
        $this->output->writeLine('Test message', 'error');
        $errorOutput = ob_get_clean();
        
        ob_start();
        $this->output->writeLine('Test message', 'warning');
        $warningOutput = ob_get_clean();
        
        ob_start();
        $this->output->writeLine('Test message', 'title');
        $titleOutput = ob_get_clean();
        
        // Stellen Sie sicher, dass die Ausgaben unterschiedlich sind
        $this->assertNotEquals($okOutput, $errorOutput);
        $this->assertNotEquals($okOutput, $warningOutput);
        $this->assertNotEquals($okOutput, $titleOutput);
        $this->assertNotEquals($errorOutput, $warningOutput);
        $this->assertNotEquals($errorOutput, $titleOutput);
        $this->assertNotEquals($warningOutput, $titleOutput);
    }
}
