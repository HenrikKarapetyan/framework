<?php

use Henrik\Contracts\Enums\ServiceScope;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\Environment\EnvironmentParserInterface;
use Henrik\Env\Environment;
use Henrik\Env\IniEnvironmentParser;
use Henrik\Framework\Kernel;
use Henrik\Framework\KernelInterface;

return [
    ServiceScope::SINGLETON->value => [
        [
            'id'    => EnvironmentInterface::class,
            'class' => Environment::class,
        ],

        [
            'id'    => EnvironmentParserInterface::class,
            'class' => IniEnvironmentParser::class,
        ],

        [
            'id'    => KernelInterface::class,
            'class' => Kernel::class,
        ],
    ],
];