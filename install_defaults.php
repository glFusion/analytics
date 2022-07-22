<?php
/**
 * Installation Defaults used when loading the online configuration.
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

/** Block execution if not loaded through glFusion */
if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}
use glFusion\Log\Log;

/** @var global config data */
global $analyticsConfigData;
$analyticsConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'analytics',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'analytics',
    ),
    array(
        'name' => 'trk_admins',     // Track admin actions?
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'analytics',
    ),
    array(
        'name' => 'trk_adm_pages',  // Track admin page hits?
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'analytics',
    ),
    array(
        'name' => 'parse_autotags', // Parse autotags in custom fields?
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'analytics',
    ),
    array(
        'name' => 'block_ips',      // IP addresses to ignore
        'default_value' => array(),
        'type' => '%text',
        'subgroup' => 0,
        'fieldset' => 00,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'analytics',
    ),
);


/**
 * Initialize Analytics plugin configuration.
 *
 * @return  boolean     True: success; False: an error occurred
 */
function plugin_initconfig_analytics()
{
    global $analyticsConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('analytics')) {
        USES_lib_install();
        foreach ($analyticsConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': Analytics config group already exists');
    }
    return true;
}

