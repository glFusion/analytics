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
use Analytics\Tracker;
use Analytics\Menu;

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
    $Tracker = Tracker::create($actionval);
    if ($Tracker !== NULL) {
        if ($Tracker->Install()) {
            COM_setMsg($LANG_UA['tracker_installed']);
        } else {
            COM_setMsg($LANG_UA['msg_err_occurred']);
        }
    }
    echo COM_refresh(Config::get('admin_url') . '/index.php');
    break;

case 'uninstall':
    $Tracker = Tracker::create($actionval);
    if ($Tracker !== NULL && $Tracker->isInstalled()) {
        if ($Tracker->uninstall()) {
            COM_setMsg($LANG_UA['tracker_removed']);
        } else {
            COM_setMsg($LANG_UA['msg_err_occurred']);
        }
    }
    echo COM_refresh(Config::get('admin_url') . '/index.php');
    break;

case 'saveconfig':
    $tracker_id = isset($_POST['tracker_id']) ? $_POST['tracker_id'] : '';
    if (!empty($tracker_id)) {
        $Tracker = Analytics\Tracker::create($tracker_id);
        $status = $Tracker->saveConfig($_POST);
        if ($status > -1) {
            COM_setMsg($status == 0 ? $LANG_UA['msg_nochange'] : $LANG_UA['msg_updated']);
            echo COM_refresh(Config::get('admin_url') . '/index.php?list');
        } else {
            COM_setMsg($LANG_UA['msg_err_occurred'], 'error');
            echo COM_refresh(Config::get('admin_url') . '/index.php?config=' . $tracker_id);
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
$display .= Menu::Admin($view);
$display .= $content;
$display .= COM_siteFooter();
echo $display;
exit;

