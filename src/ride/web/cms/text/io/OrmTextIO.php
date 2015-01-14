<?php

namespace ride\web\cms\text\io;

use ride\library\cms\node\NodeModel;
use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\mvc\message\Message;
use ride\library\mvc\view\View;
use ride\library\mvc\Response;
use ride\library\orm\OrmManager;
use ride\library\widget\WidgetProperties;

use ride\web\cms\controller\widget\TextWidget;
use ride\web\cms\orm\entry\TextEntry;
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
        $textModel = $this->orm->getTextModel();

        $texts = $textModel->find(null, $locale);
        $existingOptions = array('' => '---') + $textModel->getOptionsFromEntries($texts);

        $formBuilder->addRow('existing', 'select', array(
            'label' => $translator->translate('label.text.existing'),
            'options' => $existingOptions,
        ));
        $formBuilder->addRow('existing-new', 'option', array(
            'label' => '',
            'description' => $translator->translate('label.text.existing.new'),
        ));
        $formBuilder->addRow('version', 'hidden');
    }

    /**
     * Hook to process the form data
     * @param \ride\web\cms\text\Text $text Instance of the text
     * @param array $data Data to preset the form
     * @return null
     */
    public function processFormData(Text $text, array &$data) {
        if ($text instanceof TextEntry) {
            $data['existing'] = $text->getId();
            $data['version'] = $text->getVersion();
        }
    }

    /**
     * Hook to process the form view
     * @param \ride\library\widget\WidgetProperties $widgetProperties Instance
     * of the widget properties
     * @param \ride\library\i18n\translator\Translator $translator Instance of
     * the translator
     * @param \ride\web\cms\text\Text $text Instance of the text
     * @param \ride\library\mvc\view\View $view Instance of the properties view
     * @return null
     */
    public function processFormView(WidgetProperties $widgetProperties, Translator $translator, Text $text, View $view) {
        $this->warnAboutUsedText($widgetProperties, $this->orm->getTextModel(), $text, $translator->translate('warning.cms.text.used'));
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
        $textModel = $this->orm->getTextModel();
        $ctaModel = $this->orm->getTextCtaModel();

        $locales = (array) $locales;

        if (isset($submittedData['version'])) {
            $version = $submittedData['version'];
        } else {
            $version = 0;
        }

        if (!$text instanceof TextEntry) {
            if (isset($submittedData['existing']) && $submittedData['existing']) {
                $entry = $textModel->createProxy($submittedData['existing']);
            } else {
                $entry = $textModel->createEntry();
            }

            $entry->setFormat($text->getFormat());
            $entry->setTitle($text->getTitle());
            $entry->setSubtitle($text->getSubtitle());
            $entry->setBody($text->getBody());
            $entry->setImage($text->getImage());
            $entry->setImageAlignment($text->getImageAlignment());

            $text = $entry;
        }

        if ($submittedData['existing-new']) {
            $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, 0);
            $version = 0;
        } elseif ($submittedData['existing']) {
            $widgetProperties->setWidgetProperty(TextWidget::PROPERTY_TEXT, $submittedData['existing']);

            if ($text->id != $submittedData['existing']) {
                $entry = $textModel->createProxy($submittedData['existing']);

                $entry->setFormat($text->getFormat());
                $entry->setTitle($text->getTitle());
                $entry->setSubtitle($text->getSubtitle());
                $entry->setBody($text->getBody());
                $entry->setImage($text->getImage());
                $entry->setImageAlignment($text->getImageAlignment());

                $text = $entry;
            }
        }

        $text->id = (integer) $widgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT);

        foreach ($locales as $locale) {
            $cta = array();
            if (isset($submittedData[TextWidget::PROPERTY_CTA])) {
                foreach ($submittedData[TextWidget::PROPERTY_CTA] as $index => $action) {
                    $ctaEntry = $ctaModel->createEntry();
                    $ctaEntry->setId($action['id']);
                    $ctaEntry->setLabel($action['label']);
                    $ctaEntry->setUrl($action['url']);
                    $ctaEntry->setLocale($locale);

                    if (isset($action['node'])) {
                        $ctaEntry->setNode($action['node']);
                    }
                    if (isset($action['type'])) {
                        $ctaEntry->setType($action['type']);
                    }

                    $cta[$index] = $ctaEntry;
                }
            }

            $text->setCallToActions($cta);
            $text->setLocale($locale);
            $text->setVersion($version);

            $textModel->save($text);

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
        $textModel = $this->orm->getTextModel();

        $text = null;

        $textId = $widgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT);
        if ($textId) {
            $query = $textModel->createQuery($locale);
            $query->setIncludeUnlocalized(true);
            $query->addCondition('{id} = %1%', $textId);

            $text = $query->queryFirst();
        }

        if (!$text) {
            $text = $textModel->createEntry();
            $text->setLocale($locale);
        }

        return $text;
    }

    /**
     * Gets an existing text from the data source
     * @param \ride\library\widget\WidgetProperties $widgetProperties Instance
     * of the widget properties
     * @param string $locale Code of the current locale
     * @param string $textId Identifier of the text
     * @param boolean $isNew Flag to see if this text will be a new text
     * @return \ride\web\cms\text\Text Instance of the text
     */
    public function getExistingText(WidgetProperties $widgetProperties, $locale, $textId, $isNew) {
        $textModel = $this->orm->getTextModel();

        $text = null;

        if ($textId) {
            $query = $textModel->createQuery($locale);
            $query->setIncludeUnlocalized(true);
            $query->addCondition('{id} = %1%', $textId);

            $text = $query->queryFirst();
        }

        if (!$text) {
            $text = $textModel->createEntry();
            $text->setLocale($locale);
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
    protected function warnAboutUsedText(WidgetProperties $widgetProperties, TextModel $textModel, Text $text, $warning) {
        if (!$text->id) {
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

            if ($nodeWidgetProperties->getWidgetProperty(TextWidget::PROPERTY_IO) !== self::NAME) {
                continue;
            }

            if ($nodeWidgetProperties->getWidgetProperty(TextWidget::PROPERTY_TEXT) !== $text->id) {
                continue;
            }

            $message = new Message($warning, Message::TYPE_WARNING);

            $this->response->addMessage($message);

            break;
        }
    }

}
