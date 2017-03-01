<?php

namespace ride\web\cms\orm\processor;

use ride\library\orm\query\ModelQuery;
use ride\library\http\Request;

/**
 * Interface to process a behaviour
 */
class PublishBehaviourProcessor implements BehaviourProcessor {

    /**
     * Processes the query for a behaviour
     * @param \ride\library\orm\query\ModelQuery $query
     * @param \ride\library\http\Request $request
     * @return null
     */
    public function processQuery(ModelQuery $query, Request $request = null) {
        $query->addCondition('{isPublished} = 1 AND ({datePublishedFrom} IS NULL OR {datePublishedFrom} <= %1%) AND ({datePublishedTill} IS NULL OR {datePublishedTill} > %1%)', time());
    }

}
