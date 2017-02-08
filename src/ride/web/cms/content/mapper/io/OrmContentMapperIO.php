<?php

namespace ride\web\cms\content\mapper\io;

use ride\library\cms\content\mapper\io\ContentMapperIO;
use ride\library\cms\exception\CmsException;

use ride\web\cms\orm\ContentService;

/**
 * Implementation to load content mappers from the dependency injector
 */
class OrmContentMapperIO implements ContentMapperIO {

    /**
     * Instance of the content service
     * @var \ride\web\cms\orm\ContentService
     */
    protected $contentService;

    /**
     * Constructs a new ORM content mapper IO
     * @param \ride\web\cms\orm\ContentService $contentService
     * @return null
     */
    public function __construct(ContentService $contentService) {
        $this->contentService = $contentService;
    }

    /**
     * Gets a content mapper
     * @return \ride\library\cms\content\mapper\ContentMapper|null
     */
    public function getContentMapper($type) {
        try {
            $contentMapper = $this->contentService->getContentMapper($type, false);
        } catch (CmsException $exception) {
            $contentMapper = null;
        }

        return $contentMapper;
    }

    /**
     * Gets the available mappers
     * @return array Array with ContentMapper objects
     * @see \ride\library\cms\content\mapper\ContentMapper
     */
    public function getContentMappers() {
        return $this->contentService->getContentMappers();
    }

}
