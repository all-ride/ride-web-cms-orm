<?php

namespace ride\web\cms\controller;

use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\FormTable;

use ride\web\cms\decorator\TextUsageDecorator;
use ride\web\orm\controller\ScaffoldController;

class TextScaffoldController extends ScaffoldController {

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

        $table->addDecorator(new ValueDecorator(null, new TextUsageDecorator($nodeModel, $this->locale, $nodeUrl)));
    }

}
