<?php

namespace Henrik\Framework;

use Henrik\Contracts\AttributeParser\AttributesParserProcessorInterface;
use Henrik\Contracts\Enums\InjectorModes;
use Henrik\Contracts\Enums\ServiceScope;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\EventDispatcherInterface;
use Henrik\DI\DependencyInjector;
use Henrik\Env\Environment;
use Henrik\Filesystem\Exceptions\FileNotFoundException;
use Henrik\Filesystem\Filesystem;
use ReflectionClass;

class App
{
    public const DEFAULT_ENV   = 'dev';
    private ?string $configDir = null;

    private DependencyInjector $dependencyInjector;

    private EnvironmentInterface $environment;

    public function __construct()
    {
        $this->dependencyInjector = DependencyInjector::instance();
        $this->dependencyInjector->setMode(InjectorModes::AUTO_REGISTER);
        $rootServices = require 'config/baseServices.php';
        $this->dependencyInjector->load(array_merge_recursive($this->getServices(), $rootServices));
        /** @var Environment $environment */
        $this->environment = $this->dependencyInjector->get(EnvironmentInterface::class);

        $this->dependencyInjector->load($this->getBaseParams());

    }

    public function run(): void
    {
        $this->environment->load($this->getEnvironmentFile());
        $this->environment->load($this->getEnvironmentFile($this->environment->get('app')['env']));

        /** @var Kernel $kernel */
        $kernel = $this->dependencyInjector->get(KernelInterface::class);
        $kernel->initialize($this->getComponents());
        $kernel->load();

        $onBootstrapEvents = $kernel->getOnBootstrapEvents();

        $this->loadProjectSourceClasses();

        if ($onBootstrapEvents) {

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

    public function getConfigDir(): string
    {
        return $this->getDir('config');
    }

    public function getProjectDir(): string
    {
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        if ($docRoot !== '') {
            return realpath($docRoot . '/..');
        }

        return getcwd();
    }

    public function getOutputDirectory(): string
    {
        return $this->getDir('var');
    }

    public function getEnvironmentFile(?string $prefix = null): string
    {

        if ($prefix) {
            $prefix .= '.';
        }

        $filepath = $this->getProjectDir() . DIRECTORY_SEPARATOR . $prefix . 'env.ini';

        if (file_exists($filepath)) {
            return $filepath;
        }

        throw new FileNotFoundException($filepath);
    }

    private function getBaseParams(): array
    {
        $env = self::DEFAULT_ENV;

        if ($this->environment->has('app') && is_array($this->environment->get('app'))) {
            $env = $this->environment->get('app')['env'];
        }

        return [
            ServiceScope::PARAM->value => [
                'viewDirectory'     => $this->getDir('templates'),
                'assetsDir'         => $this->getDir('public'),
                'sessionSavePath'   => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/session/',
                'cachePath'         => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/cache/',
                'logsSaveDirectory' => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/logs/',
            ],
        ];

    }

    private function getDir(?string $dir = null): string
    {
        $dir = $dir ? DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '';
        if (!is_dir($this->getProjectDir() . $dir)) {
            Filesystem::mkdir($this->getProjectDir() . $dir);
        }

        return $this->configDir ?? realpath($this->getProjectDir() . $dir);

    }

    private function getServices()
    {
        $file = $this->getConfigDir() . DIRECTORY_SEPARATOR . 'services.php';
        if (file_exists($file)) {
            return require $file;
        }

        return [];
    }

    private function getComponents()
    {
        $file = $this->getConfigDir() . DIRECTORY_SEPARATOR . 'components.php';
        if (file_exists($file)) {
            return require $file;
        }

        return [];
    }

    private function getSourcesRootPath(): string
    {
        return $this->getDir('src');
    }

    private function loadProjectSourceClasses(): void
    {

        $classes = Filesystem::getPhpClassesFromDirectory($this->getSourcesRootPath(), $this->getRootNamespace(), $this->getExcludedPaths());

        if ($this->dependencyInjector->has(AttributesParserProcessorInterface::class)) {
            /** @var AttributesParserProcessorInterface $attributeParserProcessor */
            $attributeParserProcessor = $this->dependencyInjector->get(AttributesParserProcessorInterface::class);

            foreach ($classes as $classOrClasses) {

                if (is_array($classOrClasses)) {
                    foreach ($classOrClasses as $class) {
                        $this->processAttributes($class, $attributeParserProcessor);
                    }
                }

                if (is_string($classOrClasses)) {
                    $this->processAttributes($classOrClasses, $attributeParserProcessor);
                }
            }
        }
    }

    private function processAttributes(string $class, AttributesParserProcessorInterface $attributeParserProcessor): void
    {
        $reflectionClass = new ReflectionClass($class);
        $attributes      = $reflectionClass->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeParserProcessor->process($attribute->newInstance(), $reflectionClass);
        }
    }

    private function getRootNamespace(): string
    {
        return $this->environment->get('app')['appNamespace'];
    }

    /**
     * @return array<string>
     */
    private function getExcludedPaths(): array
    {
        $excludedPaths = $this->environment->get('app')['serviceExcludedPaths'];
        $paths         = [];

        foreach ($excludedPaths as $excludedPath) {
            $paths[] = sprintf('%s/%s', $this->getSourcesRootPath(), $excludedPath);
        }

        return $paths;
    }
}