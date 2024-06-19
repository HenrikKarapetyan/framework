<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\OnBootstrapAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnTemplateAwareInterface;

class WebKernel extends ConsoleKernel
{
    /** @var array<string> */
    protected array $templatePaths = [];

    /**
     * @return array<string>
     */
    public function getTemplatePaths(): array
    {
        return $this->templatePaths;
    }

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        parent::getComponentDefinitions($componentInstance);

        if ($componentInstance instanceof OnTemplateAwareInterface) {
            $this->templatePaths[] = $componentInstance->getTemplatesPath();
        }

        if ($componentInstance instanceof OnBootstrapAwareInterface) {
            $this->onBootstrapEvents = array_merge_recursive($this->onBootstrapEvents, $componentInstance->onBootstrapDispatchEvents());
        }
    }
}