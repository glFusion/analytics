<?php
/**
 * Tracker module for Matomo analytics (https://matomo.org).
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
 * Matomo tracker module.
 * @package analytics
 */
class Matomo extends \Analytics\Tracker
{
    /** The tracker module ID.
     * @var string */
    protected $tracker_id = 'Matomo';

    /** Translation of Ecommerce event tags to Matomo names.
     * @var array */
    private static $ecom_events = array(
        'product_list' => 'noop',
        'product_detail' => 'setEcommerceView',
        'viewcart' => 'addEcommerceItem',
        'address' => 'noop',
        'shipping' => 'noop',
        'add_to_cart' => 'addEcommerceItem',
        'purchase' => 'trackEcommerceOrder',
    );


    /**
     * Create the tracker object and set the config fields used by Matomo.
     *
     * @param   array   $A      Database record
     */
    public function __construct(?array $A=NULL)
    {
        $this->cfgFields= array(
            'site_id'    => 'string',
            'matomo_url' => 'string',
            'api_auth_token' => 'password',
            'num_customs' => 'string',
            'custom_dimensions' => 'array,5',
        );
        // Set default configuration defaults
        $this->config = array(
            'site_id' => '1',
            'num_customs' => '5',
        );
        parent::__construct($A);
        $num_customs = (int)$this->getConfigItem('num_customs');
        if ($num_customs > 0) {
            $this->cfgFields['custom_dimensions'] = 'array,' . $num_customs;
        }
    }


    /**
     * Add a single snippet to be included with the tracking code.
     *
     * @param   string  $code_txt   Code snippet
     * @return  object  $this
     */
    private function _addCode($code_txt)
    {
        $this->codes[] = $code_txt;
        return $this;
    }


    /**
     * Get the final tracking code to include in the site header.
     *
     * @return  string      Tracking code
     */
    public function getCode() : string
    {
        global $_CONF;

        $T = new \Template(Config::get('path') . 'templates/trackers');
        $T->set_file('tracker', 'matomo.thtml');
        $T->set_var(array(
            'matomo_url'    => $this->getConfigItem('matomo_url'),
            'matomo_site_id' => $this->getConfigItem('site_id'),
            'code_txt'      => $this->getPreCode(),
        ) );

        // Get the custom dimensions.
        // Collect into the $customs array only dimensions with a value
        // to check if there are any to deal with.
        $custom_dims = $this->getConfigItem('custom_dimensions');
        $customs = array();
        foreach ($custom_dims as $i=>$val) {
            if (!empty($val)) {
                $customs[$i] = $val;
            }
        }
        if (!empty($customs)) {
            $T->set_block('tracker', 'customDims', 'cdItem');
            foreach ($customs as $key=>$val) {
                $T->set_var(array(
                    'custom_id' => $key,
                    'custom_val' => $val,
                ) );
                $T->parse('cdItem', 'customDims', true);
            }
        }
        $T->parse('output', 'tracker');
        $retval = $T->finish($T->get_var('output'));
        if (!empty($customs) && Config::get('parse_autotags')) {
            $retval = PLG_replaceTags($retval);
        }
        $this->clearCodes();
        return $retval;
    }


    /**
     * Add tracking for a product view page.
     *
     * @param   array   $data   Data array
     * @return  object  $this
     */
    public function addProductView(ItemView $IV, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'product_detail';
        }
        if (isset(self::$ecom_events[$event])) {
            $event = self::$ecom_events[$event];
            if ($event == 'noop') {
                return $this;
            }
        }

        $sku = $IV->sku;
        $dscp = $IV->short_dscp;
        $price = $IV->price;
        $cats = explode(',', $IV->categories);
        $quantity = (float)$IV->quantity;
        if (!empty($cats)) {
            $cats = json_encode($cats);
        } else {
            $cats = 'false';
        }
        $this->addPreCode("_paq.push(['$event',
            '$sku',
            '$dscp',
            $cats,
            $price,
            $quantity
        ]);");
        return $this;
    }


    /**
     * Add the tracking code for a product list (catalog) view.
     *
     * @param   object  $ILV        ItemListView object
     * @param   string  $event      Event type name
     * @return  object  $this
     */
    public function addProductListView(ItemListView $ILV, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'product_list';
        }
        if (isset(self::$ecom_events[$event])) {
            $event = self::$ecom_events[$event];
            if ($event == 'noop') {
                return $this;
            }
        }

        $total_value = 0;
        try {
            foreach ($ILV->items as $IV) {
                $this->addProductView($IV, $event);
                $total_value += $IV->price;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            Log::write('system', Log::ERROR, __METHOD__ . ': Items are ' . var_export($ILV,true));
            Log::write('system', Log::ERROR, __METHOD__ . ': backtrace: ' . var_dump(debug_backtrace(0), true));
        }
        if ($event == 'addEcommerceItem') {
            // Add the total cart value
            $this->addCode("_paq.push(['trackEcommerceCartUpdate', $total_value]);");
        }

        return $this;
    }


    /**
     * Add a cart item to the code.
     * Matomo requires all cart items to be shown with the updated total
     * after adding the new item, so just call addCheckoutView()
     * which does the same thing.
     *
     * @param   object  $IV     ItemView object (not used)
     * @param   object  $OLV    OrderView object for the whole order
     * @return  object  $this
     */
    public function addCartItem(ItemView $IV, OrderView $OV) : self
    {
        return $this->addCheckoutView($OV, 'add_to_cart');
    }


    /**
     * Add tracking for a cart view page.
     *
     * @param   object  $OV     OrderView object
     * @param   string  $event  Standard event tag
     * @return  object  $this
     */
    public function addCheckoutView(OrderView $OV, ?string $event=NULL) : self
    {
        if (isset(self::$ecom_events[$event])) {
            $event = self::$ecom_events[$event];
            if ($event == 'noop') {
                return $this;
            }
        }

        $net_items = 0;
        $ILV = new ItemListView;
        foreach ($OV->items as $IV) {
            $ILV->addItem($IV);
            $net_items += $IV->price * $IV->quantity;
        }
        $this->addProductListView($ILV, $event);
        $this->_addCode("_paq.push(['trackEcommerceCartUpdate', {$net_items}]);");
        return $this;
    }


    /**
     * Record that a cart item was removed.
     * // TODO
     *
     * @param   string  $sku    Item sku
     * @return  object  $this
     */
    public function delCartItem(array $data) : self
    {
        $this->_addCode("_paq.push(['removeEcommerceItem', '$sku']");
        return $this;
    }


    /**
     * Record that the cart was emptied
     *
     * @return  object  $this
     */
    public function clearCart() : self
    {
        $this->_addCode("_paq.push(['clearEcommerceCart']");
        return $this;
    }


    /**
     * Add tracking for a category view page.
     *
     * @param   string  $cat_name   Category name
     * @return  object  $this
     */
    public function addCategoryView($cat_name)
    {
        $this->_addCode("_paq.push(['setEcommerceView',
            productSku = false,
            productName = false,
            category = '{$cat_name}'
            ]);"
        );
        return $this;
    }


    /**
     * Add a transaction view tracker, such as "view cart".
     *
     * @param   object  $OV     OrderView object
     * @param   string  $event  Standard event tag
     */
    public function addTransactionView(OrderView $OV, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'trackEcommerceOrder';
        }

        $ILV = new ItemListView;
        foreach ($OV->items as $IV) {
            $ILV->addItem($IV);
        }
        $this->addProductListView($ILV, 'purchase');
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
    public function addTransactionViewAsync(OrderView $OV, array $trk_info, array $IPN) : self
    {
        $sess_info = $this->getSessionById($trk_info['s_id']);
        if (!isset($sess_info['Matomo_cid'])) {
            return $this;
        }
        $cid = $sess_info['Matomo_cid'];

        require_once Config::get('path') . 'vendor/autoload.php';
        $tracker = new \MatomoTracker(
            $this->getConfigItem('site_id'),
            $this->getConfigItem('matomo_url')
        );
        $auth_token = $this->getConfigItem('api_auth_token'); 
        $tracker->setTokenAuth($auth_token);
        $tracker->setVisitorId($cid);
        foreach ($OV->items as $IV) {
            try {
                $tracker->addEcommerceItem(
                    $IV->sku,
                    $IV->price,
                    $IV->short_dscp,
                    explode(',', $IV->categories),
                    $IV->quantity
                );
            } catch (\Exception $e) {
                Log::write('system', LOG::ERROR, __METHOD__ . ': ' . $e->getMessage);
            }
        }
        $subtotal = $OV->value - $OV->shipping - $OV->tax;
        try {
            $tracker->doTrackEcommerceOrder(
                $OV->transaction_id,
                $OV->value,
                $subtotal,
                $OV->tax,
                $OV->shipping,
                $OV->discount
            );
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }

        $debug_TrackingUrlWithoutToken = str_replace('token_auth=' . $auth_token, 'token_auth=XYZANONYMIZED', \MatomoTracker::$DEBUG_LAST_REQUESTED_URL);
        Log::write('system', Log::DEBUG, __METHOD__ . sprintf(': Tracked ecommerce order %s: URL was %s', $OV->transaction_id, $debug_TrackingUrlWithoutToken));
        return $this;
    }


    /**
     * Sanitize the configuration, if necessary.
     * Operates directly on the object config array.
     *
     * @return  object  $this
     */
    protected function sanitizeConfig() : self
    {
        $this->config['matomo_url'] = self::_stripTrailingSlashes($this->config['matomo_url']);
        return $this;
    }

}

