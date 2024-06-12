<?php

namespace Henrik\Framework;

use Henrik\Contracts\Enums\ServiceScope;
use Henrik\Contracts\Session\CookieInterface;
use Henrik\Filesystem\Exceptions\FileNotFoundException;
use Henrik\Filesystem\Filesystem;
use Henrik\Framework\Exceptions\ConfigurationException;
use Henrik\Session\Cookie;

/**
 * @SuppressWarnings(PHPMD.Superglobals)
 */
trait AppConfigurationsTrait
{
    public function getConfigDir(): bool|string
    {
        $configDir = 'config';
        if (isset($this->configDir)) {
            $configDir = $this->configDir;
        }

        return $this->getDir($configDir);
    }

    public function setConfigDir(string $dir): void
    {
        $this->configDir = $dir;
    }

    public function getProjectDir(): bool|string
    {
        $docRoot = $_SERVER['PWD'];
        if ($docRoot !== '') {
            return realpath($docRoot);
        }

        return getcwd();
    }

    public function getOutputDirectory(): bool|string
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

    /**
     * @return array<string, array<string>>
     */
    private function getServices(): array
    {
        $file = $this->getConfigDir() . DIRECTORY_SEPARATOR . 'services.php';
        if (file_exists($file)) {
            return require $file;
        }

        return [];
    }

    private function getDir(?string $dir = null, bool $createIfNotExists = true): bool|string
    {
        $dir = $dir ? DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '';

        // here we're creating directory if it's not exists
        if ($createIfNotExists && !is_dir($this->getProjectDir() . $dir)) {
            Filesystem::mkdir($this->getProjectDir() . $dir);
        }

        return $this->configDir ?? realpath($this->getProjectDir() . $dir);

    }

    private function getRootNamespace(): string
    {
        $namespace = $this->environment->get('app.appNamespace');
        if (!is_string($namespace)) {
            throw new ConfigurationException(sprintf('The app.appNamespace must be a string `%s` given', gettype($namespace)));
        }

        return $namespace;
    }

    /**
     * @return array<string>
     */
    private function getExcludedPaths(): array
    {
        /** @var string[] $excludedPaths */
        $excludedPaths = $this->environment->get('app.serviceExcludedPaths');
        $paths         = [];

        foreach ($excludedPaths as $excludedPath) {
            $paths[] = sprintf('%s/%s', $this->getSourcesRootPath(), $excludedPath);
        }

        return $paths;
    }

    /**
     * @return array<string>
     */
    private function getComponents(): array
    {
        $file = $this->getConfigDir() . DIRECTORY_SEPARATOR . 'components.php';
        if (file_exists($file)) {
            return require $file;
        }

        return [];
    }

    private function getSourcesRootPath(): string
    {
        /** @var string $sourcesDir */
        $sourcesDir = $this->environment->get('app.sourcesDir', 'src');

        $this->environment->get('app.env');

        $sourcesPath = $this->getDir($sourcesDir);

        if (!is_string($sourcesPath)) {
            throw new ConfigurationException(sprintf('The sources root path `%s` is not exists!', $sourcesDir));
        }

        return $sourcesPath;
    }

    /**
     * @return array<string, CookieInterface>
     */
    private function getCookies(): array
    {

        $cookiesArray = [];
        $cookieParams = $this->environment->get('cookies', []);
        if (is_array($cookieParams)) {

            /**
             * @var string $name
             * @var array{
             *     value: string|null,
             *      httpOnly: bool|null,
             *      expire: int|null,
             *      domain: string|null,
             *      secure: bool|null,
             *      path: string|null
             * } $cookie
             * */
            foreach ($cookieParams as $name => $cookie) {
                $cookieObject = new Cookie();
                $cookieObject->setName($name);
                $cookieObject->setValue($cookie['value'] ?? '');
                $cookieObject->setHttpOnly(!isset($cookie['httpOnly']) || (bool) $cookie['httpOnly']);
                $cookieObject->setExpire(isset($cookie['expire']) ? (int) $cookie['expire'] : 3600);
                $cookieObject->setPath($cookie['path'] ?? '/');
                $cookieObject->setDomain($cookie['domain'] ?? '');
                $cookieObject->setSecure(!isset($cookie['secure']) || (bool) $cookie['secure']);
                $cookiesArray[$name] = $cookieObject;
            }
        }

        return $cookiesArray;
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function getBaseParams(): array
    {
        $env = $this->environment->get('app.env', self::DEFAULT_ENV);

        $sessionName = $this->environment->get('session.name');

        return [
            ServiceScope::PARAM->value => [
                'viewDirectory'     => $this->getDir('templates'),
                'assetsDir'         => $this->getDir('public'),
                'sessionSavePath'   => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/session/',
                'cachePath'         => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/cache/',
                'logsSaveDirectory' => $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $env . '/logs/',
                'sessionCookies'    => $this->getCookies(),
                'sessionName'       => $sessionName,
            ],
        ];

    }
}