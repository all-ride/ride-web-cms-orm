<?php

namespace pallo\web\cms\orm\filter;

use pallo\library\orm\model\Model;
use pallo\library\orm\query\ModelQuery;

/**
 * Interface of a filter for the content overview widget
 */
interface ContentOverviewFilter {

    /**
     * Gets the available options for the filter
     * @param array $filters Filters to update
     * @param pallo\library\orm\model\Model $model
     * @param string $field Name of the filter field
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $field, $locale, $baseUrl);

    /**
     * Applies the filter to the provided query
     * @param pallo\library\orm\model\Model $model
     * @param pallo\library\orm\query\ModelQuery $query
     * @param string $field Name of the filter field
     * @param string|array $value Submitted value
     * @return string|array Value of the filter
     */
    public function applyQuery(Model $model, ModelQuery $query, $field, $value = null);

}