<?php

namespace ride\web\cms\text\io;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;
use ride\library\widget\WidgetProperties;

use ride\web\cms\controller\widget\TextWidget;
use ride\web\cms\text\TextData;
use ride\web\cms\text\TextModel;
use ride\web\cms\text\Text;

/**
 * ORM model implementation for input/output of the text widget
 */
class OrmTextIO extends AbstractTextIO {

    /**
     * Machine name of this IO
     * @var string
     */
    const NAME = 'orm';

    /**
     * Instance of the orm manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Constructs a new text IO
     * @param \ride\library\orm\OrmManager $orm
     * @return null
     */
    public function __construct(OrmManager $orm) {
        $this->orm = $orm;
    }

    /**
     * Processes the properties form to update the editor for this io
     * @param \ride\library\form\FormBuilder $formBuilder Form builder for the
     * text properties
     * @param \ride\library\i18n\translator\Translator $translator Instance of
     * the translator
     * @param string $locale Current locale
     * @param \ride\web\cms\text\Text $text
     * @return null
     */
    public function processForm(FormBuilder $formBuilder, Translator $translator, $locale, Text $text) {
        $model = $this->getModel();

        $formBuilder->addRow('existing', 'select', array(
            'label' => $translator->translate('label.text.existing'),
            'options' => array('' => '---') + $model->getDataList(array('locale' => $locale)),
            'default' => $text->id,
        ));
        $formBuilder->addRow('version', 'hidden', array(
            'default' => $text->getVersion(),
        ));
    }

    /**
     * Store the text in the data source
     * @param \ride\library\widget\WidgetProperties $widgetProperties Instance
     * of the widget properties
     * @param string|array $locale Code of the current locale
     * @param \ride\web\cms\text\Text $text Instance of the text
     * @param array $data Submitted data
     * @return null
     */
    public function setText(WidgetProperties $widgetProperties, $locales, Text $text, array $submittedData) {
        $locales = (array) $locales;

        if (isset($submittedData['version'])) {
            $version = $submittedData['version'];
        } else {
            $version = 0;
        }

        if (isset($submittedData['existing']) && $submittedData['existing'] && $submittedData['existing'] != $text->id) {
            $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, $submittedData['existing']);
        } else {
            foreach ($locales as $locale) {
                if (!$text instanceof TextData) {
                    $data = $this->getModel()->createData();
                    $data->setFormat($text->getFormat());
                    $data->setText($text->getText());

                    $text = $data;
                }

                $text->id = (integer) $widgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT);
                $text->dataLocale = $locale;
                $text->setVersion($version);

                $this->getModel()->save($text);


                $version = $text->getVersion();
            }

            $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, $text->id);
        }
    }

    /**
     * Gets the text from the data source
     * @param \ride\library\widget\WidgetProperties $widgetProperties Instance of
     * the widget properties
     * @param string $locale Code of the current locale
     * @return \ride\web\cms\text\Text
     */
    public function getText(WidgetProperties $widgetProperties, $locale) {
        $model = $this->getModel();

        $text = null;

        $textId = $widgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT);
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
     * @return \ride\web\cms\orm\model\TextModel
     */
    protected function getModel() {
        return $this->orm->getTextModel();
    }

}
