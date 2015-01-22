<?php

namespace ride\web\cms\orm\filter;

/**
 * Abstract implementation for a filter of the content overview widget
 */
abstract class AbstractContentOverviewFilter implements ContentOverviewFilter {

    /**
     * Gets the URL for the provided filter value
     * @param string $baseUrl Base URL
     * @param array $filters All filters with the name as key and properties as
     * value
     * @param string $name Name of the filter for which a value is provided
     * @param string $value Value for the filter of the provided name
     * @return string Url for the provided filter value
     */
    protected function getUrl($baseUrl, $filters, $name, $value) {
        $query = array();

        foreach ($filters as $filterName => $filter) {
            if ($filterName == $name) {
                $queryValue = $this->getQueryValue($filter, $filterName, $value);
                if ($queryValue) {
                    $query[$filterName] = $queryValue;
                }
            } elseif (isset($filter['value'])) {
                $queryValue = $this->getQueryValue($filter, $filterName, $filter['value']);
                if ($queryValue) {
                    $query[$filterName] = $queryValue;
                }
            }
        }

        if (!$query) {
            return $baseUrl;
        } else {
            return $baseUrl . '?' . implode('&', $query);
        }
    }

    /**
     * Gets the query string for the provided filter
     * @param array $filter Filter properties
     * @param string $name Name of the filter
     * @param mixed $value Value for the filter
     * @return string Query string for the provided filter
     */
    protected function getQueryValue($filter, $name, $value) {
        $query = null;

        if ($value && is_array($value)) {
            $query = '';
            foreach ($value as $filterValue) {
                $query .= ($query ? '&' : '') . $name . '[]=' . $filterValue;
            }
        } elseif ($value && $filter['value'] != $value) {
            $query = $name . '=' . $value;
        }

        return $query;
    }

}
