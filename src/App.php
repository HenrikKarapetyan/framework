<?php

namespace Henrik\Framework;

use Henrik\Contracts\Enums\InjectorModes;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\EventDispatcherInterface;
use Henrik\DI\DependencyInjector;
use Henrik\Env\Environment;

class App
{
    use AppConfigurationsTrait;
    use AppComponentServicesLoaderTrait;

    public const DEFAULT_ENV = 'dev';

    private DependencyInjector $dependencyInjector;

    private EnvironmentInterface $environment;

    /**
     * @var array<string>
     */
    private array $controllerPaths = [];

    /**
     * @var array<string>
     */
    private array $templatePaths = [];
    private ?string $configDir   = null;

    public function __construct(private KernelInterface $kernel)
    {
        $this->dependencyInjector = DependencyInjector::instance();
        $this->dependencyInjector->setMode(InjectorModes::AUTO_REGISTER);
        $rootServices = require 'config/baseServices.php';
        $this->dependencyInjector->load(array_merge_recursive($this->getServices(), $rootServices));
        /** @var Environment $environment */
        $this->environment = $this->dependencyInjector->get(EnvironmentInterface::class);
    }

    public function run(): void
    {
        $this->dependencyInjector->load($this->kernel->getServices());
        $this->loadComponentsEventSubscribers($this->kernel->getEventSubscribers());
        $this->loadComponentsAttributesAndParsers($this->kernel->getAttrParsers());

        if ($this->kernel instanceof WebKernel) {
            $this->loadControllersByPath($this->kernel->getControllerPaths());
            $this->loadTemplatePath($this->kernel->geTemplatePaths());
        }

        $onBootstrapEvents = $this->kernel->getOnBootstrapEvents();

        foreach ($onBootstrapEvents as $eventDispatcherDefinitionId => $eventNamePairs) {

            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->dependencyInjector->get($eventDispatcherDefinitionId);

            foreach ($eventNamePairs as $event => $name) {
                /** @var object $eventObject */
                $eventObject = $this->dependencyInjector->get($event);

                $eventDispatcher->dispatch($eventObject, $name);

            }
        }
    }
}