<?php

namespace RebelCode\Wpra\Filtering\Modules;

use Psr\Container\ContainerInterface;
use RebelCode\Wpra\Core\Modules\ModuleInterface;

/**
 * The module for the Keyword Filtering add-on.
 *
 * @since 1.7
 */
class AddonModule implements ModuleInterface
{
    /**
     * @inheritdoc
     *
     * @since 1.7
     */
    public function getFactories()
    {
        return [];
    }

    /**
     * @inheritdoc
     *
     * @since 1.7
     */
    public function getExtensions()
    {
        return [];
    }

    /**
     * @inheritdoc
     *
     * @since 1.7
     */
    public function run(ContainerInterface $c)
    {
    }
}
