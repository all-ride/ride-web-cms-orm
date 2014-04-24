<?php

namespace ride\web\cms\text\io;

use ride\library\cms\node\NodeModel;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\mvc\message\Message;
use ride\library\mvc\Response;
use ride\library\orm\OrmManager;
use ride\library\widget\WidgetProperties;

use ride\web\cms\controller\widget\TextWidget;
use ride\web\cms\orm\model\TextData;
use ride\web\cms\orm\model\TextModel;
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
     * Instance of the node model
     * @var \ride\library\cms\node\NodeModel
     */
    protected $nodeModel;

    /**
     * Instance of the response
     * @var \ride\library\mvc\Response
     */
    protected $response;

    /**
     * Constructs a new text IO
     * @param \ride\library\orm\OrmManager $orm
     * @param \ride\library\cms\node\NodeModel $nodeModel
     * @param \ride\library\mvc\Response $response
     * @return null
     */
    public function __construct(OrmManager $orm, NodeModel $nodeModel, Response $response) {
        $this->orm = $orm;
        $this->nodeModel = $nodeModel;
        $this->response = $response;
    }

    /**
     * Processes the properties form to update the editor for this IO
     * @param \ride\library\widget\WidgetProperties $widgetProperties Instance
     * of the widget properties
     * @param string $locale Current locale
     * @param \ride\library\i18n\translator\Translator $translator Instance of
     * the translator
     * @param \ride\web\cms\text\Text $text Instance of the text
     * @param \ride\library\form\FormBuilder $formBuilder Form builder for the
     * text properties
     * @return null
     */
    public function processForm(WidgetProperties $widgetProperties, $locale, Translator $translator, Text $text, FormBuilder $formBuilder) {
        $model = $this->getModel();

        $this->warnAboutUsedText($widgetProperties, $model, $text, $translator->translate('warning.cms.text.used'));

        $formBuilder->addRow('existing', 'select', array(
            'label' => $translator->translate('label.text.existing'),
            'options' => array('' => '---') + $model->getDataList(array('locale' => $locale)),
            'default' => $text->id,
        ));
        $formBuilder->addRow('existing-new', 'option', array(
            'label' => '',
            'description' => $translator->translate('label.text.existing.new'),
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

        if (!$text instanceof TextData) {
            $data = $this->getModel()->createData();
            $data->setFormat($text->getFormat());
            $data->setText($text->getText());

            $text = $data;
        }

        if (isset($submittedData['existing']) && $submittedData['existing'] && $submittedData['existing'] != $text->id) {
            if ($submittedData['existing-new']) {
                $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, 0);
                $version = 0;
            } else {
                $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, $submittedData['existing']);
            }
        }

        $text->id = (integer) $widgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT);

        foreach ($locales as $locale) {
            $text->dataLocale = $locale;
            $text->setVersion($version);

            $this->getModel()->save($text);

            $version = $text->getVersion();
        }

        $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, $text->id);
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
     * Adds a warning to the response if the provided text is used in another
     * widget instance
     * @param \ride\library\widget\WidgetProperties $widgetProperties
     * @param \ride\web\cms\orm\model\TextModel $textModel
     * @param \ride\web\cms\text\Text $text Instance of the text
     * @param string $warning
     * @return null
     */
    protected function warnAboutUsedText(WidgetProperties $widgetProperties, TextModel $model, Text $text, $warning) {
        if (!isset($text->id) || !$text->id) {
            return;
        }

        $widgetId = $widgetProperties->getWidgetId();
        $node = $widgetProperties->getNode();
        $rootNode = $node->getRootNode();

        $nodes = $this->nodeModel->getNodesForWidget(TextWidget::NAME, $rootNode->getId());
        foreach ($nodes as $node) {
            $nodeWidgetId = $node->getWidgetId();
            if ($nodeWidgetId == $widgetId) {
                continue;
            }

            $nodeWidgetProperties = $node->getWidgetProperties($nodeWidgetId);

            if ($nodeWidgetProperties->getWidgetProperty('io') !== self::NAME) {
                continue;
            }

            if ($nodeWidgetProperties->getWidgetProperty('text') !== $text->id) {
                continue;
            }

            $message = new Message($warning, Message::TYPE_WARNING);

            $this->response->addMessage($message);

            break;
        }
    }

    /**
     * Gets the text model
     * @return \ride\web\cms\orm\model\TextModel
     */
    protected function getModel() {
        return $this->orm->getTextModel();
    }

}
