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
     * @var array
     */
    protected array $onBootstrapEvents = [];

    protected array $eventSubscribers = [];
    protected array $attrParsers      = [];
    protected array $services         = [];

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

    public function getOnBootstrapEvents(): array
    {
        return $this->onBootstrapEvents;
    }

    public function getEventSubscribers(): array
    {
        return $this->eventSubscribers;
    }

    public function setEventSubscribers(array $eventSubscribers): void
    {
        $this->eventSubscribers = $eventSubscribers;
    }

    public function getAttrParsers(): array
    {
        return $this->attrParsers;
    }

    public function setAttrParsers(array $attrParsers): void
    {
        $this->attrParsers = $attrParsers;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): void
    {
        $this->services = $services;
    }
}