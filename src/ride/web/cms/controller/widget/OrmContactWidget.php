<?php

namespace ride\web\cms\controller\widget;

use ride\library\mail\transport\Transport;
use ride\library\orm\model\GenericModel;
use ride\library\orm\OrmManager;

/**
 * Widget to handle a contact form
 */
class OrmContactWidget extends ContactWidget {

    protected $ormManager;

    public function __construct(GenericModel $model) {
        $this->model = $model;
    }

    public function sendMail($data, $recipient, Transport $transport) {
       parent::sendMail($data, $recipient, $transport);

       $contactEntry = $this->model->createEntry($data);
       $this->model->save($contactEntry);
   }

}
