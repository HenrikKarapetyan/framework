<?php

namespace Henrik\Framework;

interface KernelInterface
{
    /**
     * @param array<string> $components
     */
    public function initialize(array $components): void;

    /**
     * @return array<string, array<string>>
     */
    public function getEventSubscribers(): array;

    /**
     * @return array<string, string>
     */
    public function getAttrParsers(): array;

    /**
     * @return array<string, array<string, int|string>>
     */
    public function getServices(): array;

    /**
     * @return array<string, array<string, string>>
     */
    public function getOnBootstrapEvents(): array;

    /**
     * @return array<string, string>
     */
    public function getSourceRootPaths(): array;
}