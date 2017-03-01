<?php

namespace ride\web\cms\controller\widget;

use ride\library\dependency\exception\DependencyNotFoundException;
use ride\library\http\Request;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

use ride\web\cms\orm\ContentProperties;

/**
 * Abstract ORM widget with helpers for the query
 */
class AbstractOrmWidget extends AbstractWidget {

    /**
     * Instance of the model
     * @var \ride\library\orm\model\Model
     */
    protected $model;

    /**
     * Creates the query for the provided model query
     * @param \ride\web\cms\orm\ContentProperties $contentProperties
     * @param string $locale
     * @return \ride\library\orm\query\ModelQuery
     */
    protected function createQuery(ContentProperties $contentProperties, $locale) {
        // create query
        $query = $this->model->createQuery($locale);
        $query->setRecursiveDepth($contentProperties->getRecursiveDepth());
        $query->setFetchUnlocalized($contentProperties->getIncludeUnlocalized());

        // select fields
        $modelFields = $contentProperties->getModelFields();
        if ($modelFields) {
            foreach ($modelFields as $fieldName) {
                $query->addFields('{' . $fieldName . '}');
            }
        }

        // apply order
        $order = $contentProperties->getOrder();
        if ($order) {
            $query->addOrderBy($order);
        }

        // apply condition
        $this->applyBehaviourProcessors($query, $this->request);

        return $query;
    }

    /**
     * Applies the behaviour processors on the query
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\orm\query\ModelQuery $query
     * @param \ride\library\http\Request
     * @return null
     */
    protected function applyBehaviourProcessors(ModelQuery $query, Request $request) {
        $options = $this->model->getMeta()->getOptions();
        foreach ($options as $key => $value) {
            if (strpos($key, 'behaviour.') !== 0) {
                continue;
            }

            $behaviour = substr($key, 10);

            try {
                $processor = $this->dependencyInjector->get('ride\\web\\cms\\orm\\processor\\BehaviourProcessor', $behaviour);
            } catch (DependencyNotFoundException $exception) {
                continue;
            }

            $processor->processQuery($query, $request);
        }
    }

}
