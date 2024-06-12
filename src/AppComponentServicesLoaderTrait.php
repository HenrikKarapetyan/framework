<?php

namespace Henrik\Framework;

use Henrik\Container\Exceptions\KeyAlreadyExistsException;
use Henrik\Container\Exceptions\KeyNotFoundException;
use Henrik\Contracts\AttributeParser\AttributesParserProcessorInterface;
use Henrik\Contracts\EventDispatcherInterface;
use Henrik\Contracts\EventSubscriberInterface;
use Henrik\DI\Exceptions\ClassNotFoundException;
use Henrik\DI\Exceptions\ServiceNotFoundException;
use Henrik\DI\Exceptions\UnknownScopeException;
use Henrik\Filesystem\Filesystem;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

trait AppComponentServicesLoaderTrait
{
    /**
     * @param array<string, array<string>> $eventSubscribers
     *
     * @throws KeyAlreadyExistsException
     * @throws KeyNotFoundException
     * @throws ClassNotFoundException
     * @throws ServiceNotFoundException
     * @throws UnknownScopeException
     *
     * @return void
     */
    protected function loadComponentsEventSubscribers(array $eventSubscribers): void
    {

        foreach ($eventSubscribers as $eventDispatcherDefinitionId => $eventSubscriberItems) {

            if ($this->dependencyInjector->has($eventDispatcherDefinitionId)) {
                /** @var EventDispatcherInterface $eventDispatcher */
                $eventDispatcher = $this->dependencyInjector->get($eventDispatcherDefinitionId);

                if (!is_array($eventSubscriberItems)) {
                    throw new InvalidArgumentException(sprintf('Given value must be array `%s` given!', gettype($eventSubscriberItems)));
                }

                foreach ($eventSubscriberItems as $eventSubscriberId) {
                    /** @var EventSubscriberInterface $eventSubscriber */
                    $eventSubscriber = $this->dependencyInjector->get($eventSubscriberId);
                    $eventDispatcher->addSubscriber($eventSubscriber);
                }

            }
        }
    }

    /**
     * @param array<string> $attrParsers
     *
     * @throws ClassNotFoundException
     * @throws KeyAlreadyExistsException
     * @throws KeyNotFoundException
     * @throws ServiceNotFoundException
     * @throws UnknownScopeException
     *
     * @return void
     */
    protected function loadComponentsAttributesAndParsers(array $attrParsers): void
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
    protected function loadTemplatePath(array $templatePaths): void
    {
        $this->templatePaths = array_merge_recursive($templatePaths, $this->templatePaths);
    }

    /**
     * @param array<string> $sourceRootPaths
     *
     * @throws KeyNotFoundException
     * @throws ServiceNotFoundException
     * @throws UnknownScopeException|ReflectionException
     * @throws ClassNotFoundException|Exceptions\ConfigurationException
     * @throws KeyAlreadyExistsException
     *
     * @return void
     */
    private function loadProjectSourceClasses(array $sourceRootPaths = []): void
    {

        $classes = Filesystem::getPhpClassesFromDirectory($this->getSourcesRootPath(), $this->getRootNamespace(), $this->getExcludedPaths());

        /**
         * Here we're merging component sources classes with project source classes.
         */
        if (!empty($sourceRootPaths)) {
            $classes = array_merge($classes, $this->getComponentSourceClasses($sourceRootPaths));
        }

        if ($this->dependencyInjector->has(AttributesParserProcessorInterface::class)) {

            /** @var AttributesParserProcessorInterface $attributeParserProcessor */
            $attributeParserProcessor = $this->dependencyInjector->get(AttributesParserProcessorInterface::class);

            foreach ($classes as $classOrClasses) {

                //                if (is_array($classOrClasses)) {
                //                    foreach ($classOrClasses as $class) {
                //                        $this->processAttributes($class, $attributeParserProcessor);
                //                    }
                //                }
                if (is_string($classOrClasses)) {
                    $this->processAttributes($classOrClasses, $attributeParserProcessor);
                }
            }
        }
    }

    /**
     * @param class-string                       $class
     * @param AttributesParserProcessorInterface $attributeParserProcessor
     *
     * @throws ReflectionException
     *
     * @return void
     */
    private function processAttributes(string $class, AttributesParserProcessorInterface $attributeParserProcessor): void
    {
        $reflectionClass = new ReflectionClass($class);
        $attributes      = $reflectionClass->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeParserProcessor->process($attribute->newInstance(), $reflectionClass);
        }
    }
}