<?php

namespace ride\web\cms\orm\model;

use ride\library\orm\model\Model;

/**
 * Searchable content mapper for models defined with the detail widget
 */
interface SearchableModel extends Model {

    /**
     * Gets the search results
     * @param string $site Id of the site
     * @param string $locale Code of the current locale
     * @param string $query Full search query
     * @param string $queryTokens Full search query parsed in tokens
     * @param integer $page number of the result page (optional)
     * @param integer $pageItems number of items per page (optional)
     * @return \ride\library\cms\content\ContentResult
     */
    public function searchContent($site, $locale, $query, array $queryTokens, $page = null, $pageItems = null);

}