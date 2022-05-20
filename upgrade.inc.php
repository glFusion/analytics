<?php
/**
 * Upgrade routines for the Analytics plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     analytics
 * @version     v0.0.2
 * @since       v0.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_DB_dbms;
require_once __DIR__ . "/sql/{$_DB_dbms}_install.php";
use Analytics\Config;
use glFusion\Log\Log;
use glFusion\Database\Database;


/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True to ignore erorrs and continue
 * @return  boolean     True on success, False on failure
 */
function analytics_do_upgrade($dvlp=false)
{
    global $_TABLES, $_PLUGIN_INFO, $UA_UPGRADE;

    $pi_name = Config::PI_NAME;

    if (isset($_PLUGIN_INFO[$pi_name])) {
        $code_ver = plugin_chkVersion_analytics();
        if (is_array($_PLUGIN_INFO[$pi_name])) {
            // glFusion 1.6.6+
            $current_ver = $_PLUGIN_INFO[$pi_name]['pi_version'];
        } else {
            $current_ver = $_PLUGIN_INFO[$pi_name];
        }
        if (COM_checkVersion($current_ver, $code_ver)) {
            // Already updated to the code version, nothing to do
            return true;
        }
    } else {
        // Error determining the installed version
        return false;
    }
    $installed_ver = plugin_chkVersion_analytics();

    // Update with any configuration changes
    USES_lib_install();
    global $analyticsConfigData;
    require_once __DIR__ . '/install_defaults.php';
    _update_config($pi_name, $analyticsConfigData);

    // Remove deprecated files from old versions
    UA_remove_old_files();

    // Final extra check to catch code-only patch versions
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!analytics_do_update_version($installed_ver)) return false;
    }
    return true;
}


/**
 * Update the plugin version at each step to keep the version up to date.
 *
 * @param   string  $version    Version to set
 * @return  boolean     True on success, False on failure
 */
function analytics_do_update_version($version)
{
    global $_TABLES, $_CONF_UA;

    $db = Database::getInstance();
    try {
        $db->conn->executeStatement(
            "UPDATE {$_TABLES['plugins']} SET
            pi_version = ?,
            pi_gl_version = ?,
            pi_homepage = ?
            WHERE pi_name = ?",
            array(
                $version,
                Config::get('gl_version'),
                Config::get('pi_url'),
                Config::PI_NAME,
            ),
            array(
                Database::STRING,
                Database::STRING,
                Database::STRING,
                Database::STRING,
            )
        );
        Log::write('system', Log::INFO, "Succesfully updated the " . Config::PI_NAME . " Plugin version to $version!");
        return true;
    } catch (\Exception $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return false;
    }
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $dvlp       True to ignore errors during dvlpupdate
 * @return  boolean     True on success, False on failure
 */
function analytics_do_upgrade_sql($version, $dvlp=false)
{
    global $_TABLES, $_CONF_UA, $UA_UPGRADE;

    // If no sql statements needed, return success
    if (
        !isset($UA_UPGRADE[$version]) ||
        !is_array($UA_UPGRADE[$version])
    ) {
        return true;
    }

    $db = Database::getInstance();

    // Execute SQL now to perform the upgrade
    Log::write('system', Log::INFO, "--Updating Analytics to version $version");
    foreach($UA_UPGRADE[$version] as $sql) {
        Log::write('system', Log::INFO, "Analytics Plugin $version update: Executing SQL => $sql");
        try {
            $db->conn->executeStatement($sql);
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ": $sql");
        }
    }
    return true;
}


/**
 * Remove deprecated files.
 * Errors in unlink() are ignored.
 */
function UA_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/analytics
        __DIR__ => array(
        ),
        // public_html/analytics
        $_CONF['path_html'] . Config::PI_NAME => array(
        ),
        // admin/plugins/analytics
        $_CONF['path_html'] . 'admin/plugins/' . Config::PI_NAME => array(
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            if (is_file("$path/$file")) {
                Log::write('system', Log::INFO, __FUNCTION__ . ": removing $path/$file");
                @unlink("$path/$file");
            }
        }
    }
}


/**
 * Check if a column exists in a table
 *
 * @param   string  $table      Table Key, defined in analytics.php
 * @param   string  $col_name   Column name to check
 * @return  boolean     True if the column exists, False if not
 */
function UA_tableHasColumn(string $table, string $col_name) : bool
{
    global $_TABLES;

    $col_name = DB_escapeString($col_name);
    $db = Database::getInstance();
    try {
        $data = $db->conn->executeQuery(
            "SHOW COLUMNS FROM {$_TABLES[$table]} LIKE '$col_name'"
        )->fetchAssociative();
    } catch (\Exception $e) {
        $data = NULL;
    }
    return !empty($data);
}

