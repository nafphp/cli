<?php

declare(strict_types=1);

namespace Naf\CLI\Core;

class Output
{
    private const string OUTPUT_TYPE_OK    = 'ok';
    private const string OUTPUT_TYPE_ERROR = 'error';

    private const array COLOR_MAPPING = [
        self::OUTPUT_TYPE_OK    => 'green',
        self::OUTPUT_TYPE_ERROR => 'darkred',
    ];

    private array $foregroundColors = [];

    private array $backgroundColors = [];

    public function __construct()
    {
        $this->foregroundColors['black']       = '0;30';
        $this->foregroundColors['darkGray']    = '1;30';
        $this->foregroundColors['blue']        = '0;34';
        $this->foregroundColors['lightBlue']   = '1;34';
        $this->foregroundColors['green']       = '0;32';
        $this->foregroundColors['lightGreen']  = '1;32';
        $this->foregroundColors['cyan']        = '0;36';
        $this->foregroundColors['lightCyan']   = '1;36';
        $this->foregroundColors['red']         = '0;31';
        $this->foregroundColors['lightRed']    = '1;31';
        $this->foregroundColors['purple']      = '0;35';
        $this->foregroundColors['lightPurple'] = '1;35';
        $this->foregroundColors['brown']       = '0;33';
        $this->foregroundColors['yellow']      = '0;33';
        $this->foregroundColors['lightGray']   = '0;37';
        $this->foregroundColors['boldGray']    = '1;30';
        $this->foregroundColors['white']       = '0;37';

        $this->backgroundColors['black']     = '40';
        $this->backgroundColors['red']       = '41';
        $this->backgroundColors['green']     = '42';
        $this->backgroundColors['yellow']    = '43';
        $this->backgroundColors['blue']      = '44';
        $this->backgroundColors['magenta']   = '45';
        $this->backgroundColors['cyan']      = '46';
        $this->backgroundColors['lightGray'] = '47';
    }

    /**
     * @param string $message
     * @param string|null $type
     * @return void
     */
    public function writeLine(string $message, ?string $type = null): void
    {
        $text = match ($type) {
            'ok'       => $this->createColoredLine($message, 'green'),
            'error'    => $this->createColoredLine($message, 'red'),
            'warning'  => $this->createColoredLine($message, 'yellow'),
            'title'    => $this->createColoredLine($message . ' ', 'lightGreen', 'black'),
            'headline' => $this->createColoredLine($message . ' ', 'lightBlue', 'black'),
            default    => $this->createColoredLine($message, 'white'),
        };

        print $text . PHP_EOL;
    }

    /**
     * @return void
     */
    public function writeEmptyLine(): void
    {
        print PHP_EOL;
    }

    /**
     * @param int $length
     * @param string $char
     */
    public function drawStroke(int $length, string $char = '-'): void
    {
        print str_pad(' ', $length, $char) . PHP_EOL;
    }

    /**
     * @param string $message
     * @param string|null $foregroundColor
     * @param string|null $backgroundColor
     * @return string|null
     */
    private function createColoredLine(
        string $message,
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
    ): ?string {
        $coloredString = "";

        // A null colour is "no colour", not a lookup: using null as an array
        // offset is deprecated, and the framework turns that into an exception.
        if (null !== $foregroundColor && isset($this->foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[" . $this->foregroundColors[$foregroundColor] . "m";
        }
        // Check if given background color found
        if (null !== $backgroundColor && isset($this->backgroundColors[$backgroundColor])) {
            $coloredString .= "\033[" . $this->backgroundColors[$backgroundColor] . "m";
        }

        // Add string and end coloring
        $coloredString .= ' ' . $message . "\033[0m";

        return $coloredString;
    }

    /**
     * @param string $message
     * @return string
     */
    private function createTitle(string $message): string
    {
        $result = '';
        $result .= $this->createColoredLine(sprintf('%s', $message), 'lightBlue', 'black');


        //        $formattedMessage = $this->createColoredLine(sprintf('| %s |', $message), 'lightBlue');
        //        $length = strlen($message) + 3;
        //        $lineX = $this->createColoredLine(str_pad(' ', $length, '-'), 'lightBlue');
        //        $result .= $lineX;
        //        $result .= PHP_EOL;
        //        $result .= $formattedMessage;
        //        $result .= PHP_EOL;
        //        $result .= $lineX;
        //$result .= PHP_EOL;
        //$result .= PHP_EOL;
        //$result .= $this->createColoredLine(str_pad('', $length + 2, '-'), 'boldGray');

        return $result;
    }
}
