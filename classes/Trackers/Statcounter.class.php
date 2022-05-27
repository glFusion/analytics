<?php
/**
 * Tracker module for Statcounter (https://statcounter.com)
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.1.1
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
class Statcounter extends \Analytics\Tracker
{
    /** The tracker module ID.
     * @var string */
    protected $tracker_id = 'Statcounter';


    /**
     * Create a tracker object and set the config fields that are used.
     *
     * @param   array   $A      Database record
     */
    public function __construct(?array $A=NULL)
    {
        $this->cfgFields= array(
            'project_id'   => 'string',
            'security_code'   => 'string',
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
        $T->set_file('tracker', 'statcounter.thtml');
        $T->set_var(array(
            'project_id'    => $this->getConfigItem('project_id'),
            'security_code' => $this->getConfigItem('security_code'),
            'code_txt'      => $this->getPreCode(),
        ) );
        $T->parse('output', 'tracker');
        $retval = $T->finish ($T->get_var('output'));
        $this->clearCodes();
        return $retval;
    }

}

