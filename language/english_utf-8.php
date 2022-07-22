<?php
/**
 * English language file for the Analytics plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     analytics
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Global array to hold all plugin-specific configuration items. */
$LANG_UA = array (
'plugin'            => 'Analytics',
'main_title'        => 'Analytics',
'admin_title'       => 'Analytics Administration',
'trackers'          => 'Trackers',
'control'           => 'Control',
'ck_to_install'     => 'Click to Install',
'ck_to_disable'     => 'Click to Disable',
'ck_to_enable'      => 'Click to Enable',
'tr_inst_success'   => 'Tracker %s Installed Successfully.',
'tr_inst_fail'      => 'Tracker %s could not be installed.',
'q_del_item'        => 'Are you sure you want to delete this item?',
'configuring'       => 'Configuring Tracker',
'msg_updated'       => 'The item was updated.',
'msg_nochange'      => 'The item was not changed.',
'del_item'          => 'Delete this item',
'msg_err_occurred'  => 'An error occurred, check the system log.',
'tracker_installed' => 'The tracker was successfully installed.',
'tracker_removed'   => 'The tracker was successfully removed.',
);


// Localization of the Admin Configuration UI
$LANG_configsections['analytics'] = array(
    'label' => 'Analytics',
    'title' => 'Analytics Configuration',
);

$LANG_configsubgroups['analytics'] = array(
    'sg_main' => 'Main Settings',
);

$LANG_fs['analytics'] = array(
    'fs_main' => 'Main Analytics Settings',
);

$LANG_confignames['analytics'] = array(
    'trk_admins' => 'Track Admin Actions?',
    'trk_adm_pages' => 'Track Hits to Admin Pages?',
    'parse_autotags' => 'Parse Autotags?',
    'block_ips' => 'Ignore IP Addresses',
);

$LANG_configSelect['analytics'] = array(
    0 => array(1 => 'Yes', 0 => 'No'),
);
