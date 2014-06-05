<?php

namespace ride\web\cms\controller\backend;

use ride\library\cms\layout\LayoutModel;
use ride\library\cms\node\NodeModel;
use ride\library\cms\theme\ThemeModel;
use ride\library\i18n\I18n;
use ride\library\image\ImageUrlGenerator;
use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

class EntryController extends AbstractNodeTypeController {

    public function formAction(I18n $i18n, $locale, ImageUrlGenerator $imageUrlGenerator, LayoutModel $layoutModel, ThemeModel $themeModel, NodeModel $nodeModel, OrmManager $orm, $site, $node = null) {
        $locales = $i18n->getLocaleCodeList();
        $translator = $i18n->getTranslator();
        $themes = $themeModel->getThemes();
        $layouts = $layoutModel->getLayouts();

        if ($node) {
            if (!$this->resolveNode($nodeModel, $site, $node, 'entry')) {
                return;
            }

            $this->setLastAction('edit');
        } else {
            if (!$this->resolveNode($nodeModel, $site)) {
                return;
            }

            $node = $nodeModel->createNode('entry');
            $node->setParentNode($site);
        }

        // gather data
        $data = array(
            'name' => $node->getName($locale),
            'model' => $node->getEntryModel(),
            'entry' => $node->getEntryId(),
            'route' => $node->getRoute($locale, false),
            'layout' => $node->getLayout($locale),
            'theme' => $this->getThemeValueFromNode($node),
            'availableLocales' => $this->getLocalesValueFromNode($node),
        );

        $entryOptions = array('' => '---');
        if ($data['model']) {
            $model = $orm->getModel($data['model']);

            $entries = $model->find(null, $locale);
            $entryOptions += $model->getOptionsFromEntries($entries);
        }

        // build form
        $form = $this->createFormBuilder($data);
        $form->addRow('model', 'select', array(
            'label' => $translator->translate('label.model'),
            'description' => $translator->translate('label.model.description'),
            'options' => $this->getModelOptions($orm),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('entry', 'select', array(
            'label' => $translator->translate('label.entry'),
            'description' => $translator->translate('label.entry.description'),
            'options' => $entryOptions,
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.entry.name.description'),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->addRow('route', 'string', array(
            'label' => $translator->translate('label.route'),
            'description' => $translator->translate('label.route.description'),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->addRow('theme', 'select', array(
            'label' => $translator->translate('label.theme'),
            'description' => $translator->translate('label.theme.description'),
            'options' => $this->getThemeOptions($node, $translator, $themes),
        ));
        $form->addRow('layout', 'option', array(
            'label' => $translator->translate('label.layout'),
            'description' => $translator->translate('label.layout.description'),
            'options' => $this->getLayoutOptions($imageUrlGenerator, $translator, $layouts),
            'validators' => array(
                'required' => array(),
            )
        ));
        $form->addRow('availableLocales', 'select', array(
            'label' => $translator->translate('label.locales'),
            'description' => $translator->translate('label.locales.available.description'),
            'options' => $this->getLocalesOptions($node, $translator, $locales),
            'multiple' => true,
            'validators' => array(
                'required' => array(),
            ),
        ));

        // process form
        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                if (!$data['name']) {
                    $data['name'] = $entryOptions[$data['entry']];
                }

                $node->setName($locale, $data['name']);
                $node->setRoute($locale, $data['route']);
                $node->setLayout($locale, $data['layout']);
                $node->setTheme($this->getOptionValueFromForm($data['theme']));
                $node->setAvailableLocales($this->getOptionValueFromForm($data['availableLocales']));
                $node->setEntry($data['model'], $data['entry']);

                $nodeModel->setNode($node, (!$node->getId() ? 'Created new entry ' : 'Updated entry ') . $node->getName());

                $this->addSuccess('success.node.saved', array(
                    'node' => $node->getName($locale),
                ));

                $this->response->setRedirect($this->getUrl(
                    'cms.entry.edit', array(
                        'locale' => $locale,
                        'site' => $site->getId(),
                        'node' => $node->getId(),
                    )
                ));

                return;
            } catch (ValidationException $validationException) {
                $this->setValidationException($validationException, $form);
            }
        }

        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->getUrl('cms.site.detail.locale', array(
                'site' => $site->getId(),
                'locale' => $locale,
            ));
        }

        // show view
        $view = $this->setTemplateView('cms/backend/entry.form', array(
            'site' => $site,
            'node' => $node,
            'referer' => $referer,
            'form' => $form->getView(),
            'locale' => $locale,
            'locales' => $locales,
        ));
        $view->addJavascript('js/cms/orm.js');
        $view->addInlineJavascript('initializeNodeEntryForm("' . $this->getUrl('api.orm.list', array('model' => '%model%')) . '");');
    }

    /**
     * Gets the options for the model field
     * @param \ride\library\orm\OrmManager $orm Instance of the ORM manager
     * @return array Array with the model names which are flagged to be a node
     */
    protected function getModelOptions(OrmManager $orm) {
        $translator = $this->getTranslator();
        $options = array('' => '---');

        $models = $orm->getModels(true);
        foreach ($models as $modelName => $model) {
            $meta = $model->getMeta();
            if (!$meta->getOption('cms.node')) {
                continue;
            }

            $title = $meta->getOption('scaffold.title');
            if ($title) {
                $title = $translator->translate($title);
            } else {
                $title = $modelName;
            }

            $options[$modelName] = $title;
        }

        return $options;
    }

}
