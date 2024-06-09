<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\CommandAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnBootstrapAwareInterface;

class ConsoleKernel extends BaseKernel
{
    /** @var string[] */
    protected array $commandPaths = [];

    /**
     * @param int           $argc
     * @param array<string> $argv
     */
    public function __construct(protected int $argc, protected array $argv) {}

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        parent::getComponentDefinitions($componentInstance);

        if ($componentInstance instanceof CommandAwareInterface) {
            $this->commandPaths = array_merge($componentInstance->getCommands(), $this->commandPaths);
        }

        if ($componentInstance instanceof OnBootstrapAwareInterface) {
            $this->onBootstrapEvents = array_merge_recursive($this->onBootstrapEvents, $componentInstance->onBootstrapDispatchEvents());
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
    protected function getCommandPaths(): array
    {
        return $this->commandPaths;
    }
}