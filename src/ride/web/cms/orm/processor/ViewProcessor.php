<?php

namespace ride\web\cms\orm\processor;

use ride\web\mvc\view\TemplateView;

/**
 * Interface to process a view
 */
interface ViewProcessor {

    /**
     * Processes the view for a specific template
     * @parma \ride\web\mvc\view\TemplateView $view
     * @return null
     */
    public function processView(TemplateView $view);

}
