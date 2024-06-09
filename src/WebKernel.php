<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\ControllerAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnBootstrapAwareInterface;
use Henrik\Contracts\ComponentInterfaces\TemplateAwareInterface;

class WebKernel extends ConsoleKernel
{
    /** @var array<string> */
    protected array $controllerPaths = [];

    /** @var array<string> */
    protected array $templatePaths = [];

    /**
     * @return array<string>
     */
    public function getTemplatePaths(): array
    {
        return $this->templatePaths;
    }

    /**
     * @return array<string>
     */
    public function getControllerPaths(): array
    {
        return $this->controllerPaths;
    }

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        parent::getComponentDefinitions($componentInstance);

        if ($componentInstance instanceof TemplateAwareInterface) {
            $this->templatePaths[] = $componentInstance->getTemplatesPath();
        }

        if ($componentInstance instanceof ControllerAwareInterface) {
            $this->controllerPaths[] = $componentInstance->getControllersPath();
        }

        if ($componentInstance instanceof OnBootstrapAwareInterface) {
            $this->onBootstrapEvents = array_merge_recursive($this->onBootstrapEvents, $componentInstance->onBootstrapDispatchEvents());
        }
    }
}