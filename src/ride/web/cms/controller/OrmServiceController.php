<?php

namespace ride\web\cms\controller;

use \ride\library\http\Response;
use \ride\library\image\ImageUrlGenerator;
use \ride\library\orm\OrmManager;

use \ride\web\base\controller\AbstractController;
use \ride\web\cms\Cms;
use \ride\web\cms\orm\ContentService;

class OrmServiceController extends AbstractController {

    public function contentAction(OrmManager $orm, Cms $cms, ContentService $contentService, ImageUrlGenerator $imageUrlGenerator, $model, $id, $locale) {
        $model = $orm->getModel($model);

        $entry = $model->getById($id);
        if (!$entry) {
            $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

            return;
        }

        $site = $cms->getCurrentSite($this->request->getBaseUrl(), $locale);
        $content = $contentService->getContentForEntry($model, $entry, $site->getId(), $locale);

        if ($content->image) {
            $transformation = $this->request->getQueryParameter('transformation', 'crop');
            $options = array(
                'width' => $this->request->getQueryParameter('width', 100),
                'height' => $this->request->getQueryParameter('height', 100),
            );

            $content->image = $imageUrlGenerator->generateUrl($content->image, $transformation, $options);
        }

        $this->setJsonView(array(
            'type' => $content->type,
            'title' => $content->title,
            'teaser' => $content->teaser,
            'image' => $content->image,
            'date' => $content->date,
            'url' => $content->url,
        ));
    }

}
