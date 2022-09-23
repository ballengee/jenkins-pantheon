<?php

namespace RebelCode\Wpra\Filtering\Modules;

use Psr\Container\ContainerInterface;
use RebelCode\Wpra\Core\Modules\ModuleInterface;
use RebelCode\Wpra\Filtering\Templates\FilteringFeedTemplate;

/**
 * The module that extends the core template system.
 *
 * @since 1.7
 */
class TemplatesModule implements ModuleInterface
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
        return [
            /*
             * Extends the core master template with a decorator that can handle keyword filtering context args.
             *
             * @since 1.7
             */
            'wpra/feeds/templates/master_template' => function (ContainerInterface $c, $prev) {
                $collection = $c->get('wpra/feeds/templates/feed_item_collection');

                return new FilteringFeedTemplate($prev, $collection);
            },
        ];
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
