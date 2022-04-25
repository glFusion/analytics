<?php
/**
 * Common admistrative AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     analytics
 * @version     v0.0.1
 * @since       v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!plugin_ismoderator_analytics()) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the analytics admin ajax function.");
    $retval = array(
        'status' => false,
        'statusMessage' => $LANG_UA['access_denied'],
    );
    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    echo json_encode($retval);
    exit;
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = '';
}
COM_errorLog(var_export($_POST,true));

$title = NULL;      // title attribute to be set
switch ($action) {
case 'toggle':
    switch ($_POST['component']) {
    case 'tracker':
        switch ($_POST['type']) {
        case 'enabled':
            $newval = \Analytics\Tracker::toggleEnabled($_POST['oldval'], $_POST['id']);
            break;
         default:
            exit;
        }
        break;

    default:
        exit;
    }

    // Common output for all toggle functions.
    $retval = array(
        'id'    => $_POST['id'],
        'type'  => $_POST['type'],
        'component' => $_POST['component'],
        'newval'    => $newval,
        'statusMessage' => $newval != $_POST['oldval'] ?
            $LANG_UA['msg_updated'] : $LANG_UA['msg_nochange'],
        'title' => $title,
    );
    break;
}

// Return the $retval array as a JSON string
header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate");
//A date in the past
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
echo json_encode($retval);
exit;

