<?php
/**
 * Global configuration items for the Analytics plugin.
 * These are either static items, such as the plugin name and table
 * definitions, or are items that don't lend themselves well to the
 * glFusion configuration system, such as allowed file types.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     analytics
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_table_prefix, $_TABLES;

Analytics\Config::set('pi_version', '0.1.0.2');
Analytics\Config::set('gl_version', '2.0.0');

$_UA_table_prefix = $_DB_table_prefix . 'ua_';

$_TABLES['ua_trackers'] = $_UA_table_prefix . 'trackers';
$_TABLES['ua_sess_info'] = $_UA_table_prefix . 'sess_info';

