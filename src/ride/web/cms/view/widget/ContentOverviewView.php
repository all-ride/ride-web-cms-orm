<?php

namespace ride\web\cms\view\widget;

use ride\library\html\Pagination;
use ride\library\mvc\view\View;

use ride\web\cms\orm\ContentProperties;

/**
 * Interface for a content overview view
 */
interface ContentOverviewView extends View {

    /**
     * Sets the content
     * @param string $locale Code of the current locale
     * @param integer $widgetId Id of the widget
     * @param array $result Array with Content objects
     * @param \ride\web\cms\orm\ContentProperties $contentProperties Properties
     * for the view
     * @param array $filters Filters for the data
     * @param \ride\library\html\Pagination $pagination Properties for the
     * pagination
     * @param string $moreUrl URL for the more link
     * @return null
     */
	public function setContent($locale, $widgetId, array $result, ContentProperties $contentProperties, array $filters, Pagination $pagination = null, $moreUrl = null);

}