<?php
/**
 * Tracker module for Smartlook (https://app.smartlook.com).
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022-2023 Lee Garner
 * @package     analytics
 * @version     v0.1.1
 * @since       v0.1.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Analytics\Trackers;
use Analytics\Config;


/**
 * Module for Smartlook.
 * @package analytics
 */
class Smartlook extends \Analytics\Tracker
{
    /** The tracker module ID.
     * @var string */
    protected $tracker_id = 'Smartlook';


    /**
     * Create a tracker object and set the config fields that are used.
     *
     * @param   array   $A      Database record
     */
    public function __construct(?array $A=NULL)
    {
        $this->cfgFields= array(
            'tracking_id'   => 'string',
        );
        parent::__construct($A);
    }


    /**
     * Get the complete user tracking code to be placed in the HTML header.
     * Also resets the code values.
     *
     * @return  string      Code to add to the page
     */
    public function getCode() : string
    {
        $this->getSessionInfo();
        $T = new \Template(Config::get('path') . 'templates/trackers');
        $T->set_file('tracker', 'smartlook.thtml');
        $T->set_var(array(
            'tracking_id'   => $this->getConfigItem('tracking_id'),
            'code_txt'      => $this->getPreCode(),
        ) );
        $T->parse('output', 'tracker');
        $retval = $T->finish ($T->get_var('output'));
        $this->clearCodes();
        return $retval;
    }

}
