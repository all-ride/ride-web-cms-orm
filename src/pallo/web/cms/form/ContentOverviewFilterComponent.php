<?php

namespace pallo\web\cms\form;

use pallo\library\form\component\AbstractComponent;
use pallo\library\form\FormBuilder;

/**
 * Form component for a title
 */
class ContentOverviewFilterComponent extends AbstractComponent {

    /**
     * Available fields, name as key and label as value
     * @var array
     */
    private $fields;

    /**
     * Available filter types, name as key and label as value
     * @var array
     */
    private $types;

    /**
     * Sets the field options
     * @param array $fields
     */
    public function setFields(array $fields) {
        $this->fields = $fields;
    }

    /**
     * Sets the type options
     * @param array $types
     */
    public function setTypes(array $types) {
        $this->types = $types;
    }

    /**
     * Prepares the form by adding row definitions
     * @param pallo\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $translator = $options['translator'];

        $builder->addRow('field', 'select', array(
            'label' => $translator->translate('label.field'),
            'options' => $this->fields,
            'validators' => array(
                'required' => array(),
            )
        ));
        $builder->addRow('type', 'select', array(
            'label' => $translator->translate('label.type'),
            'options' => $this->types,
            'validators' => array(
                'required' => array(),
            )
        ));
    }

}