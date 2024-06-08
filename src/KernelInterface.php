<?php

namespace Henrik\Framework;

interface KernelInterface
{
    /**
     * @param array<string> $components
     */
    public function initialize(array $components): void;

    public function getEventSubscribers(): array;

    public function getAttrParsers(): array;

    public function getServices(): array;
}