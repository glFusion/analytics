<?php
/**
 * Class to provide admin and user-facing menus.
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
namespace Analytics;
use Analytics\Template;


/**
 * Class to provide menus for the Analytics plugin.
 * @package analytics
 */
class Menu
{

    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_ADMIN, $LANG_UA;

        USES_lib_admin();
        if (isset($LANG_UA['admin_hdr_' . $view]) &&
            !empty($LANG_UA['admin_hdr_' . $view])) {
            $hdr_txt = $LANG_UA['admin_hdr_' . $view];
        } else {
            $hdr_txt = '';
        }

        $admin_url = Config::get('admin_url');
        $menu_arr = array(
            array(
                'url' => $admin_url . '/index.php?list',
                'text' => $LANG_UA['trackers'],
                'active' => $view == 'trackers' ? true : false,
            ),
            array(
            'url'  => $_CONF['site_admin_url'],
            'text' => $LANG_ADMIN['admin_home'],
            ),
        );

        $T = new \Template(Config::get('path') . '/templates');
        $T->set_file('title', 'admin_title.thtml');
        $T->set_var(array(
            'title' => $LANG_UA['admin_title'] . ' (' . Config::get('pi_version') . ')',
            'icon'  => plugin_geticon_analytics(),
        ) );
        $retval = $T->parse('', 'title');
        $retval .= \ADMIN_createMenu(
            $menu_arr,
            $hdr_txt,
        );
        return $retval;
    }

}
