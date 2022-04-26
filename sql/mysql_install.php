<?php
/**
 * Database creation and update statements for the Analytics plugin.
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

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_TABLES, $_SQL;

$_SQL = array(
'ua_trackers' => "CREATE TABLE {$_TABLES['ua_trackers']} (
  `tracker_id` varchar(40) NOT NULL DEFAULT '',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `base_code` text NOT NULL DEFAULT '',
  `config` text,
  UNIQUE KEY `tracker_id` (`tracker_id`)
) ENGINE=MyISAM",

'ua_sess_info' => "CREATE TABLE {$_TABLES['ua_sess_info']} (
  `s_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sess_id` varchar(127) NOT NULL,
  `tracker_id` varchar(40) NOT NULL DEFAULT '',
  `uniq_id` varchar(20) NOT NULL DEFAULT '',
  `trk_info` text,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `sess_trk` (`sess_id`,`tracker_id`),
  KEY `trk_uniq` (`tracker_id`,`uniq_id`)
) ENGINE=MyISAM",
);

