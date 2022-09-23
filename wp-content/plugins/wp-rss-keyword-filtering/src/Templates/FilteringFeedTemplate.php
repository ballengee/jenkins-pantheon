<?php

namespace RebelCode\Wpra\Filtering\Templates;

use Dhii\Output\TemplateInterface;
use RebelCode\Wpra\Core\Data\Collections\CollectionInterface;

/**
 * A decorator for the WP RSS Aggregator feed template, adding the ability to filter items by keywords.
 *
 * @since 1.7
 */
class FilteringFeedTemplate implements TemplateInterface
{
    /**
     * The inner template.
     *
     * @since 1.7
     *
     * @var TemplateInterface
     */
    protected $inner;

    /**
     * The collection of feed items.
     *
     * @since 1.7
     *
     * @var CollectionInterface
     */
    protected $itemsCollection;

    /**
     * Constructor.
     *
     * @since 1.7
     *
     * @param TemplateInterface   $inner           The inner template.
     * @param CollectionInterface $itemsCollection The collection of feed items.
     */
    public function __construct($inner, $itemsCollection)
    {
        $this->inner = $inner;
        $this->itemsCollection = $itemsCollection;
    }

    /**
     * @inheritdoc
     *
     * @since 1.7
     */
    public function render($ctx = null)
    {
        $arrCtx = (is_array($ctx) || is_object($ctx)) ? (array) $ctx : $ctx;

        $items = (empty($ctx['items']) || !($ctx['items'] instanceof CollectionInterface))
            ? $this->itemsCollection
            : $ctx['items'];

        if (isset($arrCtx['filter'])) {
            $filter = $arrCtx['filter'];
            $filter = is_array($filter) ? implode(' ', $filter) : $filter;

            $items = $items->filter(['s' => $filter]);
        }

        $ctx['items'] = $items;

        return $this->inner->render($ctx);
    }
}
