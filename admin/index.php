<?php
/**
 * Admin index page for the analytics plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import Required glFusion libraries */
require_once('../../../lib-common.php');
require_once('../../auth.inc.php');
use Analytics\Config;
USES_lib_admin();

$content = '';
$action = '';
$expected = array(
    // Actions to perform
    'saveconfig', 'installtracker', 'uninstall',
    // Views to display
    'list', 'config',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

$view = 'list';     // Default if no correct view specified

switch ($action) {
case 'installtracker':
    $Tracker = Analytics\Tracker::create($actionval);
    if ($Tracker !== NULL) {
        if ($Tracker->Install()) {
            COM_setMsg("Gateway \"$actionval\" installed successfully");
        } else {
            COM_setMsg("Failed to install the \"$actionval\" gateway");
        }
    }
    echo COM_refresh(Config::get('admin_url') . '/index.php');
    break;

case 'uninstall':
    $Tracker = Analytics\Tracker::create($actionval);
    if ($Tracker !== NULL && $Tracker->isInstalled()) {
        if ($Tracker->Install()) {
            COM_setMsg("Gateway \"$actionval\" installed successfully");
        } else {
            COM_setMsg("Failed to install the \"$actionval\" gateway");
        }
    }
    echo COM_refresh(Config::get('admin_url') . '/index.php');
    break;


case 'saveconfig':
    $tracker_id = isset($_POST['tracker_id']) ? $_POST['tracker_id'] : '';
    if (!empty($tracker_id)) {
        $Tracker = Analytics\Tracker::create($tracker_id);
        if (!$Tracker->saveConfig($_POST)) {
            COM_setMsg($LANG_UA['msg_nochange']);
            echo COM_refresh(Config::get('admin_url') . '/index.php?config=' . $tracker_id);
        } else {
            COM_setMsg($LANG_UA['msg_updated']);
            COM_refresh(Config::get('admin_url') . '/index.php?list');
        }
    }
    break;

default:
    $view = $action;
    break;
}

switch ($view) {
case 'config':
    $Tracker = \Analytics\Tracker::create($actionval);
    if ($Tracker !== NULL) {
        $content .= $Tracker->Configure();
    }
    break;

case 'list':
default:
    $content .= Analytics\Tracker::adminList();
    break;
}

$display = COM_siteHeader();
$display .= \Analytics\Menu::Admin($view);
$display .= $content;
$display .= COM_siteFooter();
echo $display;
exit;

