<?php

namespace ride\web\cms\orm\filter;

use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Implementation for a boolean filter for the content overview widget
 */
class BooleanContentOverviewFilter extends AbstractContentOverviewFilter {

    /**
     * Gets the available options for the filter
     * @param array $filters Filters to update
     * @param \ride\library\orm\model\Model $model
     * @param string $name Name of the filter
     * @param string $locale Code of the current locale
     * @param string $baseUrl Base URL
     * @return null
     */
    public function setVariables(array &$filters, Model $model, $name, $locale, $baseUrl) {
        $filters[$name]['entries'] = array();
        $filters[$name]['options'] = array(1 => $name);
        $filters[$name]['urls'] = array($name => $this->getUrl($baseUrl, $filters, $name, 1));
        $filters[$name]['values'] = array($name => '1');
        $filters[$name]['empty'] = $this->getUrl($baseUrl, $filters, $name, null);
    }

    /**
     * Applies the filter to the provided query
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\query\ModelQuery $query
     * @param string $fieldName Name of the filter field
     * @param string|array $value Submitted value
     * @return string|array Value of the filter
     */
    public function applyQuery(Model $model, ModelQuery $query, $fieldName, $value = null) {
        if (!$value) {
            return null;
        }

        $query->addCondition('{' . $fieldName . '} = %1%', $value);

        return $value;
    }

}
