<?php

namespace Henrik\Framework;

use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\AttributesAndParsersAwareInterface;
use Henrik\Contracts\ComponentInterfaces\DependsOnAwareInterface;
use Henrik\Contracts\ComponentInterfaces\EventSubscriberAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnBootstrapAwareInterface;

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
     * @var array<string, array<mixed>>
     */
    protected array $services = [];

    /**
     * @param array<string> $components
     */
    public function initialize(array $components): void
    {

        foreach ($components as $component) {
            /** @var ComponentInterface $componentInstance */
            $componentInstance = new $component();

            if ($componentInstance instanceof DependsOnAwareInterface) {
                $this->initialize($componentInstance->dependsOn());
            }

            $this->getComponentDefinitions($componentInstance);
        }
    }

    public function getComponentDefinitions(ComponentInterface $componentInstance): void
    {
        $this->services = array_merge_recursive($this->services, $componentInstance->getServices());

        if ($componentInstance instanceof EventSubscriberAwareInterface) {

            $this->eventSubscribers = array_merge_recursive($this->eventSubscribers, $componentInstance->getEventSubscribers());
        }

        if ($componentInstance instanceof AttributesAndParsersAwareInterface) {
            $this->attrParsers = array_merge_recursive($this->attrParsers, $componentInstance->getAttributesAndParsers());
        }

        if ($componentInstance instanceof OnBootstrapAwareInterface) {
            $this->onBootstrapEvents = array_merge_recursive($this->onBootstrapEvents, $componentInstance->onBootstrapDispatchEvents());
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
}