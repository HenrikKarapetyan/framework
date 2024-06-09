<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\OnCommandAwareInterface;

class ConsoleKernel extends BaseKernel
{
    /** @var string[] */
    protected array $commandPaths = [];

    /**
     * @param int           $argc
     * @param array<string> $argv
     */
    public function __construct(protected int $argc = 0, protected array $argv = []) {}

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        parent::getComponentDefinitions($componentInstance);

        if ($componentInstance instanceof OnCommandAwareInterface) {
            $this->commandPaths[] = $componentInstance->getCommandsPath();
        }
    }

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

    /**
     * @return array<string>
     */
    public function getCommandPaths(): array
    {
        return $this->commandPaths;
    }
}