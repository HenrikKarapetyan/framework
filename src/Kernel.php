<?php

namespace Henrik\Framework;

use Henrik\Contracts\AttributeParser\AttributesParserProcessorInterface;
use Henrik\Contracts\ComponentInterface;
use Henrik\Contracts\ComponentInterfaces\AttributesAndParsersAwareInterface;
use Henrik\Contracts\ComponentInterfaces\ControllerAwareInterface;
use Henrik\Contracts\ComponentInterfaces\DependsOnAwareInterface;
use Henrik\Contracts\ComponentInterfaces\EventSubscriberAwareInterface;
use Henrik\Contracts\ComponentInterfaces\OnBootstrapAwareInterface;
use Henrik\Contracts\ComponentInterfaces\TemplateAwareInterface;
use Henrik\Contracts\DependencyInjectorInterface;
use Henrik\Contracts\EventDispatcherInterface;
use InvalidArgumentException;

class Kernel implements KernelInterface
{
    /**
     * @var array
     */
    private array $onBootstrapEvents = [];

    /** @var array<string> */
    private array $templatePaths = [];

    /** @var array<string> */
    private array $controllerPaths = [];

    private array $eventSubscribers = [];
    private array $attrParsers      = [];
    private array $services         = [];

    public function __construct(private readonly DependencyInjectorInterface $dependencyInjector) {}

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

    public function load(): void
    {
        $this->dependencyInjector->load($this->services);

        $this->loadComponentsEventSubscribers($this->eventSubscribers);
        $this->loadComponentsAttributesAndParsers($this->attrParsers);
        $this->loadControllersByPath($this->controllerPaths);
    }

    public function getOnBootstrapEvents(): array
    {
        return $this->onBootstrapEvents;
    }

    /**
     * @param array<string, array<string>> $eventSubscribers
     *
     * @return void
     */
    private function loadComponentsEventSubscribers(array $eventSubscribers): void
    {

        foreach ($eventSubscribers as $eventDispatcherDefinitionId => $eventSubscriberItems) {

            if ($this->dependencyInjector->has($eventDispatcherDefinitionId)) {
                /** @var EventDispatcherInterface $eventDispatcher */
                $eventDispatcher = $this->dependencyInjector->get($eventDispatcherDefinitionId);

                if (!is_array($eventSubscriberItems)) {
                    throw new InvalidArgumentException(sprintf('Given value must be array `%s` given!', gettype($eventSubscriberItems)));
                }

                foreach ($eventSubscriberItems as $eventSubscriber) {
                    $eventDispatcher->addSubscriber($this->dependencyInjector->get($eventSubscriber));
                }

            }
        }
    }

    /**
     * @param array<string> $attrParsers
     *
     * @return void
     */
    private function loadComponentsAttributesAndParsers(array $attrParsers): void
    {
        if ($this->dependencyInjector->has(AttributesParserProcessorInterface::class)) {
            /** @var AttributesParserProcessorInterface $attributeParserProcessor */
            $attributeParserProcessor = $this->dependencyInjector->get(AttributesParserProcessorInterface::class);

            foreach ($attrParsers as $attributeClass => $parserClass) {

                $attributeParserProcessor->addParser($attributeClass, $parserClass);
            }
        }
    }

    /**
     * @param array<string> $templatePaths
     *
     * @return void
     */
    private function setTemplatesPath(array $templatePaths): void
    {
        $this->templatePaths = array_merge_recursive($templatePaths, $this->templatePaths);
    }

    /**
     * @param array<string> $controllerPaths
     *
     * @return void
     */
    private function loadControllersByPath(array $controllerPaths): void
    {
        $this->controllerPaths = array_merge_recursive($controllerPaths, $this->controllerPaths);

    }
}