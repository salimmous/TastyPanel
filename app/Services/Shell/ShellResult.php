<?php

namespace App\Services\Shell;

readonly class ShellResult
{
    public function __construct(
        public int $exitCode,
        public array $output,
        public string|false $lastLine = false
    ) {}

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }
}
