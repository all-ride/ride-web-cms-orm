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

    /**
     * Function to handle the sending of contact Email
     * @param $data
     * @param $recipient
     * @param Transport $transport
     * @throws \ride\library\mail\exception\MailException
     */
    public function sendMail($data, $recipient) {
       parent::sendMail($data, $recipient);

       $contactEntry = $this->model->createEntry($data);
       $this->model->save($contactEntry);
   }

}
