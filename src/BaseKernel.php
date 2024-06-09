<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\OnAttributesAndParsersAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnDependsAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnEventSubscriberAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnSourcesAwareInterface;

class BaseKernel implements KernelInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $onBootstrapEvents = [];

    /**
     * @var array<string, array<string>>
     */
    protected array $eventSubscribers = [];

    /**
     * @var array<string, string>
     */
    protected array $attrParsers = [];

    /**
     * @var array<string, array<string, int|string>>
     */
    protected array $services = [];

    /** @var array<string, string> */
    protected array $sourceRootPaths = [];

    /**
     * @param array<string> $components
     */
    public function initialize(array $components): void
    {

        foreach ($components as $component) {
            /** @var ComponentInterface $componentInstance */
            $componentInstance = new $component();

            if ($componentInstance instanceof OnDependsAwareInterface) {
                $this->initialize($componentInstance->dependsOn());
            }

            $this->getComponentDefinitions($componentInstance);
        }
    }

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        $this->services = array_merge_recursive($this->services, $componentInstance->getServices());

        if ($componentInstance instanceof OnEventSubscriberAwareInterface) {

            $this->eventSubscribers = array_merge_recursive($this->eventSubscribers, $componentInstance->getEventSubscribers());
        }

        if ($componentInstance instanceof OnSourcesAwareInterface) {
            $this->sourceRootPaths = array_merge($this->sourceRootPaths, $componentInstance->getSourcesDirectories());
        }

        if ($componentInstance instanceof OnAttributesAndParsersAwareInterface) {
            $this->attrParsers = array_merge_recursive($this->attrParsers, $componentInstance->getAttributesAndParsers());
        }

    }

    /**
     * {@inheritDoc}
     */
    public function getOnBootstrapEvents(): array
    {
        return $this->onBootstrapEvents;
    }

    /**
     * {@inheritDoc}
     */
    public function getEventSubscribers(): array
    {
        return $this->eventSubscribers;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttrParsers(): array
    {
        return $this->attrParsers;
    }

    /**
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceRootPaths(): array
    {
        return $this->sourceRootPaths;
    }
}