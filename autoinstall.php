<?php
/**
 * Automatic installation functions for the Analytics plugin.
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

/** Include plugin configuration */
require_once __DIR__  . '/functions.inc';
/** Include database queries */
require_once __DIR__ . '/sql/mysql_install.php';
/** Include default values */
require_once __DIR__ . '/install_defaults.php';
use glFusion\Log\Log;
use Analytics\Config;

global $_CONF;
$language = $_CONF['language'];
if (!is_file(__DIR__  . '/language/' . $language . '.php')) {
    $language = 'english';
}
require_once __DIR__ . '/language/' . $language . '.php';
global $_SQL, $_TABLES;

/** Plugin installation options */
$INSTALL_plugin['analytics'] = array(
    'installer' => array(
        'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),
    'plugin' => array(
        'type' => 'plugin',
        'name' => Config::PI_NAME,
        'ver' => Config::get('pi_version'),
        'gl_ver' => Config::get('gl_version'),
        'url' => Config::get('pi_url'),
        'display' => Config::get('pi_display_name'),
    ),
    array(
        'type' => 'feature',
        'feature' => 'analytics.admin',
        'desc' => 'Ability to administer the Analytics plugin',
        'variable' => 'admin_feature_id',
    ),
    array(
        'type' => 'mapping',
        'findgroup' => 'Root',
        'feature' => 'admin_feature_id',
        'log' => 'Adding Admin feature to the Root group',
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['ua_trackers'],
        'sql' => $_SQL['ua_trackers'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['ua_sess_info'],
        'sql' => $_SQL['ua_sess_info'],
    ),
);


/**
*   Puts the datastructures for this plugin into the glFusion database
*   Note: Corresponding uninstall routine is in functions.inc
*
*   @return boolean     True if successful False otherwise
*/
function plugin_install_analytics()
{
    global $INSTALL_plugin, $_PLUGIN_INFO;

    $pi_name            = Config::PI_NAME;
    $pi_display_name    = Config::get('pi_display_name');

    Log::write('system', Log::INFO, "Attempting to install the $pi_display_name plugin");

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
 * Loads the configuration records for the Online Config Manager.
 *
 * @return  boolean     true = proceed with install, false = an error occured
 */
function plugin_load_configuration_analytics()
{
    return plugin_initconfig_analytics();
}


/**
 * Plugin-specific post-installation function.
 * - Creates the file download path and working area.
 * - Migrates configurations from the Paypal plugin, if installed and up to date.
 * - No longer automatically migrates Paypal data since the currency may not be configured.
 */
function plugin_postinstall_analytics($upgrade=false)
{
}

