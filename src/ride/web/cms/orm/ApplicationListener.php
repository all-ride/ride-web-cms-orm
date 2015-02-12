<?php

namespace ride\web\cms\orm;

use ride\library\event\Event;
use ride\library\orm\OrmManager;

use ride\web\base\menu\MenuItem;

class ApplicationListener {

    public function prepareContentMenu(Event $event, OrmManager $ormManager) {
        $locale = $event->getArgument('locale');
        $menu = $event->getArgument('menu');

        $models = $ormManager->getModels();
        foreach ($models as $model) {
            $meta = $model->getMeta();

            $option = $meta->getOption('scaffold.expose');
            if (!$option) {
                continue;
            }

            $menuItem = new MenuItem();

            $title = $meta->getOption('scaffold.title');
            if ($title) {
                $menuItem->setTranslation($title);
            } else {
                $menuItem->setLabel($meta->getName());
            }

            $menuItem->setRoute('system.orm.scaffold.index', array('locale' => $locale, 'model' => $meta->getName()));

            $menu->addMenuItem($menuItem);
        }
    }

}
