<?php
/**
 * glFusion API functions for the Analytics plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Analytics\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
use Analytics\Config;
Config::getInstance();

/** Include plugin config file  */
require_once __DIR__ . '/analytics.php';

// Load the site's language file, falling back to English.
$langpath = __DIR__ . '/language';
$langfile = "{$_CONF['language']}.php";
if (!is_file("$langpath/$langfile")) {
    $langfile = 'english_utf-8.php';
}
/** Include the proper language file */
require_once "$langpath/$langfile";

if (is_file("$langpath/custom/$langfile")) {
    /** Include the custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

if (file_exists(__DIR__ . '/config.php')) {
    /** Include local configuration overrides */
    include_once __DIR__ . '/config.php';
}


/**
 * Upgrade the plugin to the currently-installed version.
 *
 * @param   boolean $dvlp   True if this is called from dvlpupdate.php
 * @return  boolean     True on success, False on failure
 */
function plugin_upgrade_analytics($dvlp = false)
{
    require_once __DIR__ . '/upgrade.inc.php';
    return analytics_do_upgrade();
}


/**
 * Run scheduled tasks.
 * - Delete old sessions.
 */
function plugin_runScheduledTask_shop()
{
    $ts = time() - (86400 * 14);
    $db = Database::getInstance();
    try {
        $db->conn->executeUpdate(
            "DELETE FROM {$_TABLES['ua_sess_info']}
            WHERE UNIX_TIMESTAMP(last_update) < ?",
            [$ts],
            [Database::INTEGER]
        );
    } catch (\Exception $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_analytics()
{
    return Config::get('url') . '/images/analytics.png';
}


/**
 * Check if this is a plugin administrator.
 * There is no "moderator" for this plugin.
 * Saves a static value to conserve calls to SEC_hasRights()
 *
 * @return  boolean     True for admins, False for regular users
 */
function plugin_ismoderator_analytics() : bool
{
    static $isadmin = NULL;
    if ($isadmin === NULL) {
        $isadmin = SEC_hasRights('analytics.admin');
    }
    return $isadmin;
}


/**
 * Add an option for the plugin in the command and control area.
 *
 * @return  array   Array containing (pi_name, admin_url, icon_url)
 */
function plugin_cclabel_analytics() : ?array
{
    if (plugin_ismoderator_analytics()) {
        return array(
            Config::get('pi_display_name'),
            Config::get('admin_url') . '/index.php',
            plugin_geticon_analytics()
        );
    } else {
        return NULL;
    }
}


/**
 * Adds the plugin to the Admin menu.
 *
 * @return  array   Array containing (pi_name, pi_admin_url, # of items or '')
 */
function plugin_getadminoption_analytics() : ?array
{
    if (plugin_ismoderator_analytics()) {
        return array(
            Config::get('pi_display_name'),
            Config::get('admin_url') . '/index.php',
            ''
        );
    } else {
        return NULL;
    }
}


/**
 * Returns the current version of the plugin.
 *
 * @return  string  Plugin version
 */
function plugin_chkVersion_analytics()
{
    return Config::get('pi_version');
}


/**
 * Execute additional functions when COM_siteHeader() is called.
 * Adds tracker codes to the header, depending on the url and whether
 * tracking admin page views is allowed.
 */
function plugin_getheadercode_analytics()
{
    global $_CONF;


    $retval = '';
    if (
        Config::get('trk_adm_pages') == 1 ||
        @strpos(COM_getCurrentURL(), $_CONF['site_admin_url']) !== 0
    ) {
        $Trackers = Analytics\Tracker::getEnabled();
        foreach ($Trackers as $Tracker) {
            $Instance = Analytics\Tracker::getInstance($Tracker->getName());
            $retval .= $Instance->getCode();
            $Instance->clearCodes();
        }
    }
    return $retval;
}


/**
 * Get the URL to the localized configuration help file.
 *
 * @uses    ANALYTICS_getDocURL()
 * @param   string  $option     Configuration option to display
 * @param   string  $doclang    Documentation language requested
 * @return  array   Array of (url, window_type)
 */
function plugin_getconfigelementhelp_analytics($option, $doclang='english')
{
    $doc_url = ANALYTICS_getDocURL('config', $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }

    return array($doc_url, 0);
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @param  string  $file       HTML file
 * @param  string  $doclang    Documentation language
 * @return string              URL to document directory or file
 */
function ANALYTICS_getDocURL($file='', $doclang='')
{
    global $_CONF;

    if ($doclang == '') {
        $doclang = $_CONF['language'];
    }

    $doc_path = __DIR__ . "/docs/$doclang/";
    $doc_url = '';

    $file .= '.html';

    if ($file != '') {
        if (!file_exists($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = Config::get('url') . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = Config::get('url') . "/docs/$doclang/";
    }
    return $doc_url;
}


/**
 * Return the items that should be removed with the plugin.
 *
 * @return  array   Array of items to be removed.
 */
function plugin_autouninstall_analytics()
{
    $out = array (
        // Give the name of the tables, without $_TABLES[]
        'tables' => array(
            'ua_trackers',
            'ua_sess_info',
        ),
    );
}
            
