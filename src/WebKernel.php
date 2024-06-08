<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\ControllerAwareInterface;
use Henrik\Contracts\ComponentInterfaces\TemplateAwareInterface;

class WebKernel extends BaseKernel
{
    /** @var array<string> */
    protected array $controllerPaths = [];

    /** @var array<string> */
    protected array $templatePaths = [];

    /**
     * @return array<string>
     */
    public function geTemplatePaths(): array
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
            $this->templatePaths = array_merge($this->templatePaths, $componentInstance->getTemplatesPath());
        }

        if ($componentInstance instanceof ControllerAwareInterface) {
            $this->controllerPaths = array_merge($this->controllerPaths, $componentInstance->getControllersPath());
        }

    }
}