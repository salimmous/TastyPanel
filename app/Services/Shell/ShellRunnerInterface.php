<?php

namespace App\Services\Shell;

interface ShellRunnerInterface
{
    public function run(string $command): ShellResult;
}
