<?php

namespace ride\web\cms\orm\filter;

/**
 * Implementation for a date value filter for the content overview widget
 */
abstract class AbstractContentOverviewFilter implements ContentOverviewFilter {

    /**
     * Gets the URL for the provided filter
     * @param string $baseUrl
     * @param array $filters
     * @param string $name
     * @param string $value
     * @return string
     */
    protected function getUrl($baseUrl, $filters, $name, $value) {
        $query = array();

        foreach ($filters as $filterName => $filter) {
            if ($filterName == $name) {
                if ($value && $filter['value'] != $value) {
                    $query[$filterName] = $filterName . '=' . $value;
                }
            } elseif (isset($filter['value'])) {
                if (is_array($filter['value'])) {
                    foreach ($filter['value'] as $filterValue) {
                        $query[$filterName] = $filterName . '[]=' . $filterValue;
                    }
                } else {
                    $query[$filterName] = $filterName . '=' . $filter['value'];
                }
            }
        }

        if (!$query) {
            return $baseUrl;
        } else {
            return $baseUrl . '?' . implode('&', $query);
        }
    }

}
