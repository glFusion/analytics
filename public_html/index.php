<?php
/**
 * Index page for the analytics plugin.
 * No guest-facing functions are available, just return 404.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2023 Lee Garner
 * @package     analytics
 * @version     v0.1.1
 * @since       v0.1.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import Required glFusion libraries */
require_once('../lib-common.php');
echo COM_404();
exit
