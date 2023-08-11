<?php


namespace Mi2\Import;


use Mi2\Import\Events\ImportBootEvent;
use Mi2\Import\Events\RegisterServices;
use OpenEMR\Menu\MenuEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventHandler
{
    protected $eventDispatcher = null;
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Initialize the modules event handlers
     */
    public function init()
    {
//        $container = $GLOBALS["kernel"]->getContainer();
//        $container->set('sftp_api', new Api());

        $container = $GLOBALS["kernel"]->getContainer();
        $importManager = new ImportManager();
        $container->set('import-manager', $importManager);

        // Listen for the menu update event so we can dynamically add our "Import"" menu item
        $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'mainMenuUpdate'], 10);

        $this->eventDispatcher->addListener(ImportBootEvent::IMPORT_BOOTED, [$this, 'importBooted']);
    }

    public function importBooted(ImportBootEvent $importBootEvent)
    {
        // error_log("Import Booted");
        $importManager = $GLOBALS["kernel"]->getContainer()->get('import-manager');
        $registerServicesEvent = new RegisterServices($importBootEvent->getKey(), $importManager);
        $registerServicesEvent = $this->eventDispatcher->dispatch( $registerServicesEvent,RegisterServices::REGISTER, 10);
    }

    public function mainMenuUpdate(MenuEvent $event)
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'import';
        $menuItem->menu_id = 'import0';
        $menuItem->label = xlt("Import");
        $menuItem->url = "/interface/modules/custom_modules/oe-import/index.php?action=import!import";
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
}
