<?php

namespace App\Services;

class LogReaderService
{
    public function tail(string $path, int $lines = 200): string
    {
        if (!file_exists($path)) {
            return '';
        }

        $lines = max(1, min($lines, 2000));
        $handle = fopen($path, 'r');
        if (!$handle) {
            return '';
        }

        $buffer = '';
        $chunkSize = 4096;
        $pos = -1;
        $lineCount = 0;
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle) ?: 0;

        while ($fileSize > 0 && $lineCount <= $lines) {
            $seek = max($fileSize - $chunkSize, 0);
            $readSize = $fileSize - $seek;
            fseek($handle, $seek, SEEK_SET);
            $chunk = fread($handle, $readSize);
            if ($chunk === false) {
                break;
            }
            $buffer = $chunk . $buffer;
            $lineCount = substr_count($buffer, "\n");
            if ($seek === 0) {
                break;
            }
            $fileSize = $seek;
        }

        fclose($handle);

        $linesArray = explode("\n", trim($buffer));
        $tail = array_slice($linesArray, -$lines);
        return implode("\n", $tail);
    }
}
