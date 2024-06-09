<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\CommandAwareInterface;

class ConsoleKernel extends BaseKernel
{
    /** @var array<string> */
    protected array $commandPaths = [];

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        parent::getComponentDefinitions($componentInstance);

        if ($componentInstance instanceof CommandAwareInterface) {
            $this->commandPaths = array_merge($componentInstance->getCommands(), $this->commandPaths);
        }
    }

    /**
     * @return array<string>
     */
    protected function getCommandPaths(): array
    {
        return $this->commandPaths;
    }
}