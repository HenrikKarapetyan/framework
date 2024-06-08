<?php

use Henrik\Contracts\Enums\ServiceScope;
use Henrik\Contracts\Environment\EnvironmentInterface;
use Henrik\Contracts\Environment\EnvironmentParserInterface;
use Henrik\Env\Environment;
use Henrik\Env\IniEnvironmentParser;

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
    ],
];