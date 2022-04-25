<?php
/**
 * Tracker module for Open Web Analytics (https://www.openwebanalytics.com/).
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Analytics\Trackers;
use Analytics\Config;
use Analytics\Models\Ecommerce\ItemListView;
use Analytics\Models\Ecommerce\ItemView;
use Analytics\Models\Ecommerce\OrderView;
use glFusion\Log\Log;


/**
 * Open Web Analytics tracker module.
 * @package analytics
 */
class Owa extends \Analytics\Tracker
{
    /** Tracker module ID.
     * @var string */
    protected $tracker_id = 'Owa';

    /** Translation of standard event types to OWA tags.
     * @var array */
    private static $ecom_events = array(
        'product_list' => 'noop',
        'product_detail' => 'noop',
        'viewcart' => 'noop',
        'address' => 'noop',
        'shipping' => 'noop',
        'add_to_cart' => 'noop',
        'purchase' => 'addTransaction',
    );


    /**
     * Set up the tracker config fields and load from DB.
     *
     * @param   array   $A      Database record
     */
    public function __construct(?array $A=NULL)
    {
        $this->cfgFields= array(
            'site_id'    => 'string',
            'tracking_url' => 'string',
            'api_key' => 'string',
            'secret_auth_key' => 'password',
            'custom_variables' => 'array,5',
        );
        parent::__construct($A);

    }


    /**
     * Make the tracker session data.
     * Actually retrieves the data from OWA.
     *
     * @return  array       Array with the Owa_cid key set
     */
    protected function makeSessionInfo() : array
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $tracker = new \OwaSdk\Tracker\TrackerClient(array());
        return array('Owa_cid' => $tracker->getSessionId());
    }


    /**
     * Get the final tracking code to include in the site header.
     *
     * @return  string      Tracking code
     */
    public function getCode() : string
    {
        global $_CONF;

        $this->getSessionInfo();
        $T = new \Template(Config::get('path') . 'templates/trackers');
        $T->set_file('tracker', 'owa.thtml');
        $T->set_var(array(
            'tracking_url'  => $this->getConfigItem('tracking_url'),
            'site_id'       => $this->getConfigItem('site_id'),
            'code_txt'      => $this->getPreCode(),
        ) );

        $custom_vars = $this->getConfigItem('custom_variables');
        $customs = array();
        foreach ($custom_vars as $i=>$val) {
            if (!empty($val)) {
                $customs[$i] = $val;
            }
        }
        if (!empty($customs)) {
            $T->set_block('tracker', 'customVars', 'customVar');
            foreach ($customs as $key=>$val) {
                $T->set_var(array(
                    'custom_id' => $key,
                    'custom_val' => $val,
                ) );
                $T->parse('customVar', 'customVars', true);
            }
        }

        $T->parse('output', 'tracker');
        $retval = $T->finish($T->get_var('output'));
        if (!empty($customs)) {
            $retval = PLG_replaceTags($retval);
        }
        $this->clearCodes();
        return $retval;
    }


    /**
     * Add a transaction view such as "view cart".
     *
     * @param   object  $OV     OrderView object
     * @param   string  $event  Standard event type
     */
    public function addTransactionView(OrderView $OV, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'addTransaction';
        }

        $this->addCode("owa_cmds.push(['addTransaction',
            '{$OV->transaction_id}',
            '{$OV->affiliation}',
            '{$OV->value}',
            '{$OV->tax}',
            '{$OV->shipping}'
            ]);"
        );
        foreach ($OV->items as $IV) {
            $this->addCode(
                "owa_cmds.push(['addTransactionLineItem',
                '{$OV->transaction_id}',
                '{$IV->sku}',
                '{$IV->short_dscp}',
                '{$IV->categories}',
                '{$IV->price}',
                '{$IV->quantity}'
                ]);"
            );
        }
        $this->addCode("owa_cmds.push(['trackTransaction']);");
        return $this;
    }


    /**
     * Track an Ecommerce order asynchronously, such as from IPN or webhook.
     *
     * @param   object  $OV     Order View object
     * @param   array   $trk_info   Tracking info, including uniq_id value
     * @param   array   $IPN    Array of IPN or Webhook data
     * @return  object  $this
     */
    public function addTransactionViewAsync(OrderView $OV, array$trk_info, array $IPN) : self
    {
        $sess_info = $this->getSessionById($trk_info['s_id']);
        if (!isset($sess_info['Owa_cid'])) {
            return $this;
        }
        $owa_sess_id = $sess_info['Owa_cid'];

        require_once Config::get('path') . 'vendor/autoload.php';
        $config = array(
            'instance_url' => $this->getConfigItem('tracking_url'),
            'credentials' => $this->getConfigItem('api_key'),
            'auth_key' => $this->getConfigItem('secret_auth_key'),
        );

        $tracker = new \OwaSdk\Tracker\TrackerClient($config);
        $tracker->setSiteId($this->getConfigItem('site_id'));
        $tracker->trackPageView(); // track the page view
        $tracker->addTransaction(
            $OV->transaction_id,
            $OV->affiliation,
            $OV->value,
            $OV->tax,
            $OV->shipping,
            $OV->gateway,
            '',
            '',
            '',
            $trk_info['url'],
            $owa_sess_id
        );

        foreach ($OV->items as $IV) {
            $tracker->addTransactionLineItem(
                $OV->transaction_id,
                $IV->sku,
                $IV->short_dscp,
                $IV->categories,
                $IV->price,
                $IV->quantity,
            );
        }
        $tracker->trackTransaction();
        return $this;
    }

}

