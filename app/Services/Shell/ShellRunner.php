<?php

namespace App\Services\Shell;

class ShellRunner implements ShellRunnerInterface
{
    public function run(string $command): ShellResult
    {
        $output = [];
        $exitCode = 0;
        $lastLine = exec($command, $output, $exitCode);

        return new ShellResult(
            exitCode: $exitCode,
            output: $output,
            lastLine: $lastLine
        );
    }
}
