<?php

namespace ride\web\cms\orm\processor;

use ride\library\orm\OrmManager;

use ride\web\mvc\view\TemplateView;
use ride\web\orm\taxonomy\TaxonomyTerm;

/**
 * View processor for a cloud content overview
 */
class CloudViewProcessor implements ViewProcessor {
    protected $orm;

    protected $steps;

    /**
     * Constructs a new taxonomy term cloud view
     * @param \ride\library\orm\OrmManager $orm Instance of the ORM
     * @return null
     */
    public function __construct(OrmManager $orm, $steps = 10) {
        $this->orm = $orm;
        $this->steps = $steps;
    }

    /**
     * Processes the view for a specific template
     * @parma \ride\web\mvc\view\TemplateView $view
     * @return null
     */
    public function processView(TemplateView $view) {
        $template = $view->getTemplate();
        $result = $template->get('result');
        if (!$result) {
            return;
        }

        $model = $this->orm->getTaxonomyTermModel();

        $minWeight = 999999;
        $maxWeight = -1;

        foreach ($result as $index => $content) {
            if (!$content->data instanceof TaxonomyTerm) {
                unset($result[$index]);

                continue;
            }

            $content->data->weight = $model->calculateCloudWeight($content->data);

            $minWeight = min($minWeight, $content->data->weight);
            $maxWeight = max($maxWeight, $content->data->weight);
        }

        foreach ($result as $content) {
            $content->data->weightClass = $this->calculateWeightClass($content->data->weight, $minWeight, $maxWeight);
        }

        $template->set('result', $result);
    }

    /**
     * Calculates a style class for the provided weight
     * @param integer $weight
     * @param integer $minimum
     * @param integer $maximum
     * @return string
     */
    protected function calculateWeightClass($weight, $minimum, $maximum) {
        $diff = $maximum - $minimum;
        $step = ceil($diff / $this->steps);
        $weight = ceil(($weight - $minimum) / $step);

        return 'weight-' . $weight;
    }

}
