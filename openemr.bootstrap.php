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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mi2\Import\EventHandler;

$eventDispatcher = $GLOBALS["kernel"]->getEventDispatcher();
$eventHandler = new \Mi2\Import\EventHandler($eventDispatcher);
$eventHandler->init();

