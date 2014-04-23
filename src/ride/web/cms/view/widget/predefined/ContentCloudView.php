<?php

namespace ride\web\cms\view\widget\predefined;

use ride\library\orm\OrmManager;

use ride\web\cms\view\widget\AbstractContentOverviewView;
use ride\web\orm\taxonomy\TaxonomyTerm;

/**
 * View for a taxonomy term cloud
 */
class ContentCloudView extends AbstractContentOverviewView {

    /**
     * Path to the template of this view
     * @var string
     */
    const TEMPLATE = 'cms/widget/orm/cloud';

    /**
     * Constructs a new taxonomy term cloud view
     * @param \ride\library\orm\OrmManager $orm Instance of the ORM
     * @return null
     */
    public function __construct(OrmManager $orm) {
        $this->orm = $orm;
        $this->steps = 10;

        parent::__construct();
    }

    /**
     * Hook to process the content set to this view
     * @return null
     */
    protected function processContent() {
        $model = $this->orm->getTaxonomyTermModel();

        $minWeight = 999999;
        $maxWeight = -1;

        $result = $this->template->get('result');
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

        $this->template->set('result', $result);
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
