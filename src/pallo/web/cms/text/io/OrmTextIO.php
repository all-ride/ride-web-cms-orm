<?php

namespace pallo\web\cms\text\io;

use pallo\library\form\FormBuilder;
use pallo\library\i18n\translator\Translator;
use pallo\library\orm\OrmManager;
use pallo\library\widget\WidgetProperties;

use pallo\web\cms\text\TextData;
use pallo\web\cms\text\TextModel;
use pallo\web\cms\text\Text;

/**
 * ORM model implementation for input/output of the text widget
 */
class OrmTextIO implements TextIO {

    /**
     * Name of the text property
     * @var string
     */
    const PROPERTY_TEXT = 'text';

    /**
     * Instance of the orm manager
     * @var pallo\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Constructs a new text IO
     * @param pallo\library\orm\OrmManager $orm
     * @return null
     */
    public function __construct(OrmManager $orm) {
        $this->orm = $orm;
    }


    /**
     * Processes the properties form to update the editor for this io
     * @param pallo\library\form\FormBuilder $formBuilder Form builder for the
     * text properties
     * @param pallo\library\i18n\translator\Translator $translator Instance of
     * the translator
     * @param string $locale Current locale
     * @param pallo\web\cms\text\Text $text
     * @return null
     */
    public function processForm(FormBuilder $formBuilder, Translator $translator, $locale, Text $text) {
        $formBuilder->addRow('version', 'hidden', array(
            'default' => $text->getVersion(),
        ));
    }

    /**
     * Store the text in the data source
     * @param pallo\library\widget\WidgetProperties $widgetProperties Instance
     * of the widget properties
     * @param string|array $locale Code of the current locale
     * @param pallo\web\cms\text\Text $text Instance of the text
     * @param array $data Submitted data
     * @return null
     */
    public function setText(WidgetProperties $widgetProperties, $locale, Text $text, array $submittedData) {
        $locale = (array) $locale;

        foreach ($locale as $l) {
            if (!$text instanceof TextData) {
                $data = $this->getModel()->createData();
                $data->setFormat($text->getFormat());
                $data->setText($text->getText());

                $text = $data;
            }

            $text->setVersion($submittedData['version']);

            $text->id = (integer) $widgetProperties->getWidgetProperty(self::PROPERTY_TEXT);
            $text->dataLocale = $l;

            $this->getModel()->save($text);

            $widgetProperties->setWidgetProperty(self::PROPERTY_TEXT, $text->id);
        }
    }

    /**
     * Gets the text from the data source
     * @param pallo\library\widget\WidgetProperties $widgetProperties Instance of
     * the widget properties
     * @param string $locale Code of the current locale
     * @return pallo\web\cms\text\Text
     */
    public function getText(WidgetProperties $widgetProperties, $locale) {
        $model = $this->getModel();

        $text = null;

        $textId = $widgetProperties->getWidgetProperty(self::PROPERTY_TEXT);
        if ($textId) {
            $query = $model->createQuery($locale);
            $query->setRecursiveDepth(0);
            $query->addCondition('{id} = %1%', $textId);

            $text = $query->queryFirst();
        }

        if (!$text) {
            $text = $model->createData();
            $text->dataLocale = $locale;
        }

        return $text;
    }

    /**
     * Gets the text model
     * @return pallo\web\cms\orm\model\TextModel
     */
    protected function getModel() {
        return $this->orm->getTextModel();
    }

}