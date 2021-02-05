<?php
/**
 * Bootstrap custom Patient Privacy module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2020 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OpenEMR\Menu\MenuEvent;


function oe_module_custom_add_menu_item(MenuEvent $event)
{
    $menu = $event->getMenu();

    $menuItem = new stdClass();
    $menuItem->requirement = 0;
    $menuItem->target = 'adm';
    $menuItem->menu_id = 'adm0';
    $menuItem->label = xlt("Custom Menu Item");
    $menuItem->url = "/interface/modules/custom_modules/oe-custom-module-tpl/index.php";
    $menuItem->children = [];
    $menuItem->acl_req = ["admin", "super"];

    foreach ($menu as $item) {
        if ($item->menu_id == 'admimg') {
            array_unshift($item->children, $menuItem);
            break;
        }
    }

    $event->setMenu($menu);

    return $event;
}

// Listen for the menu update event so we can dynamically add our patient privacy menu item
$eventDispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_custom_add_menu_item');
