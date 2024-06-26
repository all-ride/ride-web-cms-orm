<?php

namespace ride\web\cms\decorator;

use ride\library\cms\node\NodeModel;
use ride\library\decorator\Decorator;
use ride\library\html\Anchor;

/**
 * Decorator to show the usage of a text
 */
class TextUsageDecorator implements Decorator {
    protected $nodes;
    protected $locale;
    protected $url;

    /**
     * Constructs a new decorator
     * @return null
     */
    public function __construct(NodeModel $nodeModel, $locale, $nodeUrl) {
        $this->nodes = $nodeModel->getNodesForWidget('text', null, 'draft');
        $this->locale = $locale;
        $this->url = $nodeUrl;
    }

    /**
     * Decorates the value
     * @param mixed $value Value to decorate
     * @return string Decorated value
     */
    public function decorate($value) {
        if (!is_object($value)) {
            return $value;
        }

        $textNodes = array();
        $textId = $value->getId();

        foreach ($this->nodes as $node) {
            if (isset($textNodes[$node->getId()])) {
                continue;
            }

            $widgetProperties = $node->getWidgetProperties($node->getWidgetId());
            if ($widgetProperties->getWidgetProperty('text') !== $textId) {
                continue;
            }

            $blockInfo = $node->getWidgetBlockInfo($node->getWidgetId());
            if (!$blockInfo) {
                continue;
            }

            $url = $this->url;
            $url = str_replace('%25site%25', $node->getRootNodeId(), $url);
            $url = str_replace('%25revision%25', $node->getRevision(), $url);
            $url = str_replace('%25node%25', $node->getId(), $url);
            $url = str_replace('%25region%25', $blockInfo['region'], $url);
            $url = str_replace('%25section%25', $blockInfo['section'], $url);
            $url = str_replace('%25block%25', $blockInfo['block'], $url);

            $anchor = new Anchor($node->getName($this->locale), $url);

            $textNodes[$node->getId()] = $anchor->getHtml();
        }

        if (!$textNodes) {
            return;
        }

        return '<ul><li>' . implode('</li><li>', $textNodes) . '</li></ul>';
    }

}
