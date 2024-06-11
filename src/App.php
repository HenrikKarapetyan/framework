<?php

namespace Henrik\Framework;

use Henrik\Console\Interfaces\CommandRunnerInterface;
use Henrik\Contracts\Enums\InjectorModes;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\EventDispatcherInterface;
use Henrik\Contracts\Session\SessionInterface;
use Henrik\DI\DependencyInjector;
use Henrik\Env\Environment;
use Henrik\Filesystem\Filesystem;

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

    public function __construct(private readonly KernelInterface $kernel)
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
        $this->environment->load($this->getEnvironmentFile());
        $this->environment->load($this->getEnvironmentFile($this->environment->get('app')['env']));
        $this->dependencyInjector->load($this->getBaseParams());

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
     * @return string[]
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