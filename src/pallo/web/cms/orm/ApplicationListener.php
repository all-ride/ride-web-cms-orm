<?php

namespace pallo\web\cms\orm;

use pallo\library\event\Event;
use pallo\library\orm\OrmManager;

use pallo\web\base\view\MenuItem;
use pallo\web\base\view\Menu;

class ApplicationListener {

    public function prepareContentMenu(Event $event, OrmManager $ormManager) {
        $locale = $event->getArgument('locale');
        $menu = $event->getArgument('menu');

        $models = $ormManager->getModels(true);
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