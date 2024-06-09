<?php

namespace Henrik\Framework;

class ConsoleKernel extends BaseKernel
{
    /**
     * @param int           $argc
     * @param array<string> $argv
     */
    public function __construct(protected int $argc = 0, protected array $argv = []) {}

    public function getArgc(): int
    {
        return $this->argc;
    }

    /**
     * @return string[]
     */
    public function getArgv(): array
    {
        return $this->argv;
    }
}