<?php

namespace ride\web\cms\controller;

use ride\library\form\Form;
use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\FormTable;
use ride\library\orm\model\Model;

use ride\web\cms\decorator\TextUsageDecorator;
use ride\web\orm\controller\ScaffoldController;

class TextScaffoldController extends ScaffoldController {

    /**
     * Constructs a new scaffold controller
     * @param string $modelName Name of the model to scaffold, if not provided the name will be retrieved from the class name
     * @param boolean|array $search Boolean to enable or disable the search functionality, an array of field names to query is also allowed to enable the search
     * @param boolean|array $order Boolean to enable or disable the order functionality, an array of field names to order is also allowed to enable the order
     * @param boolean|array $pagination Boolean to enable or disable the pagination functionality, an array of pagination options is also allowed to enable the pagination
     * @return null
     */
    public function __construct(Model $model, $search = true, $order = true, $pagination = true) {
        parent::__construct($model, $search, $order, $pagination);

        $this->templateIndex = 'orm/scaffold/index.text';
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
        $nodeUrl = $this->getUrl('cms.node.content.region', array('site' => '%site%', 'revision' => '%revision%', 'node' => '%node%', 'region' => '%region%', 'locale' => $this->locale));

        $decorator = new ValueDecorator(null, new TextUsageDecorator($nodeModel, $this->locale, $nodeUrl));
        $decorator->setCellClass('text-usage');

        $table->addDecorator($decorator);
    }

}
