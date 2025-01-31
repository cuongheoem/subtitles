<?php

declare(strict_types=1);

namespace Done\Subtitles\Converters;

use function explode;
use function floor;
use function fmod;
use function gmdate;
use function implode;
use function round;
use function str_pad;
use function strtotime;
use function trim;

use const STR_PAD_RIGHT;

class SubConverter implements ConverterInterface
{
    /**
     * Converts file's content (.srt) to library's "internal format" (array)
     *
     * @param string $fileContent Content of file that will be converted
     * @return array                    Internal format
     */
    public function fileContentToInternalFormat($fileContent)
    {
        $internalFormat = []; // array - where file content will be stored

        $blocks = explode("\n\n", trim($fileContent)); // each block contains: start and end times + text
        foreach ($blocks as $block) {
            $lines = explode("\n", $block); // separate all block lines
            $times = explode(',', $lines[0]); // one the second line there is start and end times

            $internalFormat[] = [
                'start' => static::srtTimeToInternal($times[0]),
                'end' => static::srtTimeToInternal($times[1]),
                'lines' => explode('[br]', $lines[1]), // get all the remaining lines from block (if multiple lines of text)
            ];
        }

        return $internalFormat;
    }

    /**
     * Convert library's "internal format" (array) to file's content
     *
     * @param array $internalFormat Internal format
     * @return string                   Converted file content
     */
    public function internalFormatToFileContent(array $internalFormat)
    {
        $fileContent = '';

        foreach ($internalFormat as $k => $block) {
            $start = static::internalTimeToSrt($block['start']);
            $end = static::internalTimeToSrt($block['end']);
            $lines = implode("[br]", $block['lines']);

            $fileContent .= $start . ',' . $end . "\r\n";
            $fileContent .= $lines . "\r\n";
            $fileContent .= "\r\n";
        }

        $fileContent = trim($fileContent);

        return $fileContent;
    }

    /**
     * Convert .srt file format to internal time format (float in seconds)
     * Example: 00:02:17,440 -> 137.44
     *
     * @param string $subTime
     * @return float
     */
    protected static function srtTimeToInternal($subTime)
    {
        $parts = explode('.', $subTime);

        $onlySeconds = strtotime("1970-01-01 {$parts[0]} UTC");
        $milliseconds = (float) '0.' . $parts[1];

        return $onlySeconds + $milliseconds;
    }

    /**
     * Convert internal time format (float in seconds) to .srt time format
     * Example: 137.44 -> 00:02:17,440
     *
     * @param float $internalTime
     * @return string
     */
    protected static function internalTimeToSrt($internalTime)
    {
        $seconds = floor($internalTime);
        $remainder = fmod($internalTime, 1);
        $remainderString = round($remainder, 2) * 100;
        $remainderString = str_pad((string) $remainderString, 2, '0', STR_PAD_RIGHT);

        return gmdate("H:i:s", (int) $seconds) . '.' . $remainderString;
    }
}
