<?php

namespace Henrik\Framework;

use Henrik\Console\Interfaces\CommandRunnerInterface;
use Henrik\Container\Exceptions\KeyAlreadyExistsException;
use Henrik\Container\Exceptions\KeyNotFoundException;
use Henrik\Container\Exceptions\UndefinedModeException;
use Henrik\Contracts\Enums\InjectorModes;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\EventDispatcherInterface;
use Henrik\DI\DependencyInjector;
use Henrik\DI\Exceptions\ClassNotFoundException;
use Henrik\DI\Exceptions\ServiceNotFoundException;
use Henrik\DI\Exceptions\UnknownConfigurationException;
use Henrik\DI\Exceptions\UnknownScopeException;
use Henrik\Filesystem\Exceptions\FileNotFoundException;
use Henrik\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
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
    private array $templatePaths = [];

    private ?string $configDir = null;

    /**
     * @param KernelInterface $kernel
     *
     * @throws UnknownScopeException
     * @throws KeyNotFoundException
     * @throws UndefinedModeException
     * @throws ServiceNotFoundException
     * @throws KeyAlreadyExistsException
     * @throws ClassNotFoundException
     * @throws UnknownConfigurationException
     */
    public function __construct(private readonly KernelInterface $kernel)
    {
        $this->dependencyInjector = DependencyInjector::instance();
        $this->dependencyInjector->setMode(InjectorModes::AUTO_REGISTER);
        $rootServices = require 'config/baseServices.php';
        $this->dependencyInjector->load(array_merge_recursive($this->getServices(), $rootServices));
        /** @var EnvironmentInterface $environment */
        $environment       = $this->dependencyInjector->get(EnvironmentInterface::class);
        $this->environment = $environment;
    }

    /**
     * @throws UndefinedModeException
     * @throws UnknownConfigurationException
     * @throws FileNotFoundException
     * @throws UnknownScopeException
     * @throws ServiceNotFoundException
     * @throws KeyNotFoundException
     * @throws KeyAlreadyExistsException
     * @throws ClassNotFoundException
     */
    public function run(): void
    {
        $this->environment->load($this->getEnvironmentFile());
        /** @var string $currentEnvironment */
        $currentEnvironment = $this->environment->get('app.env');
        $this->environment->load($this->getEnvironmentFile($currentEnvironment));
        $this->dependencyInjector->load($this->getBaseParams()); // @phpstan-ignore-line

        $components = $this->getComponents();
        $this->kernel->initialize($components);

        $this->dependencyInjector->load($this->kernel->getServices());
        $this->loadComponentsEventSubscribers($this->kernel->getEventSubscribers());
        $this->loadComponentsAttributesAndParsers($this->kernel->getAttrParsers());

        $this->loadProjectSourceClasses($this->kernel->getSourceRootPaths());

        if ($this->kernel instanceof WebKernel) {
            $this->runWebKernel($this->kernel);

            return;
        }

        if ($this->kernel instanceof ConsoleKernel) {
            $this->runConsoleKernel($this->kernel);
        }
    }

    /**
     * @param array<string, string> $sourceRootPaths
     *
     * @return class-string[]
     */
    public function getComponentSourceClasses(array $sourceRootPaths): array
    {

        $classes = [];
        foreach ($sourceRootPaths as $namespace => $path) {
            $classes = array_merge($classes, Filesystem::getPhpClassesFromDirectory($path, $namespace));
        }

        return $classes;
    }

    private function runWebKernel(WebKernel $kernel): void
    {
        $this->loadTemplatePath($kernel->getTemplatePaths());

        $this->runBootstrapEvents($kernel);
    }

    private function runConsoleKernel(ConsoleKernel $kernel): void
    {
        /** @var CommandRunnerInterface $commandRunner */
        $commandRunner = $this->dependencyInjector->get(CommandRunnerInterface::class);
        $commandRunner->run($kernel->getArgc(), $kernel->getArgv());
        $this->runBootstrapEvents($kernel);
    }

    private function runBootstrapEvents(KernelInterface $kernel): void
    {
        $onBootstrapEvents = $kernel->getOnBootstrapEvents();

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