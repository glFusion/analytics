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
        'name' => 'trk_adm_pages',   // Track admin page hits?
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'analytics',
    ),
);


/**
 * Initialize Analytics plugin configuration.
 *
 * @param   integer $admin_group    Admin Group ID created by installation
 * @return  boolean     True: success; False: an error occurred
 */
function plugin_initconfig_analytics($admin_group)
{
    global $analyticsConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('analytics')) {
        USES_lib_install();
        foreach ($analyticsConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Analytics config group already exists');
    }
    return true;
}

