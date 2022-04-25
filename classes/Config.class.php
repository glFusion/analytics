<?php
/**
 * Class to read and manipulate Analytics configuration values.
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


/**
 * Class to get plugin configuration data.
 * @package analytics
 */
final class Config
{
    /** Plugin Name.
     */
    public const PI_NAME = 'analytics';

    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;

    /** Config class singleton instance.
     * @var object */
    static private $instance = NULL;


    /**
     * Get the configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Create an instance of the configuration object.
     */
    private function __construct()
    {
        global $_CONF;

        $cfg = \config::get_instance();
        $this->properties = $cfg->get_config(self::PI_NAME);

        $this->properties['pi_name'] = self::PI_NAME;
        $this->properties['pi_display_name'] = 'Analytics';
        $this->properties['pi_url'] = 'http://www.glfusion.org';
        $this->properties['url'] = $_CONF['site_url'] . '/' . self::PI_NAME;
        $this->properties['admin_url'] = $_CONF['site_admin_url'] . '/plugins/' . self::PI_NAME;
        $this->properties['path'] = $_CONF['path'] . 'plugins/' . self::PI_NAME . '/';
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    private function _get($key=NULL, $default=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } elseif (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
           return $default;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     */
    private function _set($key, $val)
    {
        $this->properties[$key] = $val;
        return $this;
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set, NULL to unset
     */
    public static function set($key, $val=NULL, ?bool $save=NULL)
    {
        if ($save) {
            $cfg = \config::get_instance();
            $cfg->set($key, $val, self::PI_NAME);
        }
        return self::getInstance()->_set($key, $val);

    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    public static function get($key=NULL, $default=NULL)
    {
        return self::getInstance()->_get($key, $default);
    }


    /**
     * Convenience function to get the base plugin path.
     *
     * @return  string      Path to main plugin directory.
     */
    public static function path()
    {
        return self::_get('path');
    }


    /**
     * Convenience function to get the path to plugin templates.
     *
     * @return  string      Template path
     */
    public static function path_template()
    {
        return self::get('path') . 'templates/';
    }

}
