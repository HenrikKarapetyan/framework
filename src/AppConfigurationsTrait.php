<?php

namespace Henrik\Framework;

use Henrik\Contracts\Enums\ServiceScope;
use Henrik\Filesystem\Exceptions\FileNotFoundException;
use Henrik\Filesystem\Filesystem;

trait AppConfigurationsTrait
{
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

    private function getServices()
    {
        $file = $this->getConfigDir() . DIRECTORY_SEPARATOR . 'services.php';
        if (file_exists($file)) {
            return require $file;
        }

        return [];
    }

    private function getDir(?string $dir = null): string
    {
        $dir = $dir ? DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '';
        if (!is_dir($this->getProjectDir() . $dir)) {
            Filesystem::mkdir($this->getProjectDir() . $dir);
        }

        return $this->configDir ?? realpath($this->getProjectDir() . $dir);

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
}