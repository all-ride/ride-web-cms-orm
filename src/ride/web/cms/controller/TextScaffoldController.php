<?php

namespace ride\web\cms\controller;

use ride\library\form\Form;
use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\FormTable;

use ride\web\cms\decorator\TextUsageDecorator;
use ride\web\orm\controller\ScaffoldController;

class TextScaffoldController extends ScaffoldController {

    /**
     * Sets the index view for the scaffolding to the response
     * @param \ride\library\html\table\FormTable $table Table with the model data
     * @param \ride\library\form\Form $form Form of the table
     * @param array $locales Available locale codes
     * @param string $locale Code of the current locale
     * @param array $actions Array with the URL of the action as key and the label for the action as value
     * @return null
     */
    protected function setIndexView(FormTable $table, Form $form, array $locales, $locale, array $actions = null) {
        $view = parent::setIndexView($table, $form, $locales, $locale, $actions);
        $view->addJavascript('js/text.admin.js');

        return $view;
    }

    /**
     * Adds the table decorators
     * @param \ride\library\html\table\FormTable $table
     * @param string $detailAction URL to the detail of the
     * @return null
     */
    protected function addTableDecorators(FormTable $table, $detailAction) {
        parent::addTableDecorators($table, $detailAction);

        $nodeModel = $this->dependencyInjector->get('ride\\library\\cms\\node\\NodeModel');
        $nodeUrl = $this->getUrl('cms.node.layout.region', array('site' => '%site%', 'node' => '%node%', 'region' => '%region%', 'locale' => $this->locale));

        $decorator = new ValueDecorator(null, new TextUsageDecorator($nodeModel, $this->locale, $nodeUrl));
        $decorator->setCellClass('text-usage');

        $table->addDecorator($decorator);
    }

}
