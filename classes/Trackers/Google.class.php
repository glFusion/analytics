<?php
/**
 * Tracker module for Google analytics (https://analytics.google.com).
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


/**
 * Module for Google Analytics.
 * @package analytics
 */
class Google extends \Analytics\Tracker
{
    /** The tracker module ID.
     * @var string */
    protected $tracker_id = 'Google';

    /** Translation of standard ecommerce events to tracker-specific names.
     * @var array */
    private static $ecom_events = array(
        'product_list' => 'view_item_list',
        'product_detail' => 'view_item',
        'viewcart' => 'begin_checkout',
        'address' => 'checkout_progress',
        'shipping' => 'checkout_progress',
    );


    /**
     * Create a tracker object and set the config fields that are used.
     *
     * @param   array   $A      Database record
     */
    public function __construct(?array $A=NULL)
    {
        $this->cfgFields= array(
            'tracking_id'    => 'string',
            'user_id' => 'string',
            'debug' => 'checkbox',
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
        $T->set_file('tracker', 'google.thtml');
        $T->set_var(array(
            'tracking_id'   => $this->getConfigItem('tracking_id'),
            'code_txt'      => $this->getPreCode(),
            'user_id'       => $this->getConfigItem('user_id'),
            'debug'         => $this->getConfigItem('debug'),
        ) );
        $T->parse('output', 'tracker');
        $retval = $T->finish ($T->get_var('output'));
        $retval = PLG_replaceTags($retval);
        $this->clearCodes();
        return $retval;
    }


    public function addProductListView($Items, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'product_list';
        }
        if (isset(self::$ecom_events[$event])) {
            $event = self::$ecom_events[$event];
        }

        $viewItems = array();
        foreach ($Items->items as $Item) {
            $viewItems[] = self::convertItemView($Item);
        }
        if (!empty($viewItems)) {
            $viewItems = json_encode(array('items' => $viewItems));
            $this->addCode('gtag("event", "' . $event . '", ' . $viewItems . ');');
        }
        return $this;
    }


    /**
     * Convert an ItemView object to Google's format.
     *
     * @param   object  $IV ItemView object
     * @return  array       Array of data to include in Google's tag
     */
    private function convertItemView(ItemView $IV) : array
    {
        $retval = array(
            'id' => $IV->sku,
            'name' => $IV->short_dscp,
            'brand' => $IV->brand,
            'category' => $IV->categories,
            'variant' => $IV->variant,
            'list_position' => $IV->list_position,
            'quantity' => $IV->quantity,
            'price' => number_format($IV->price, 2),
        );
        if (!empty($IV->list_name)) {
            $retval['list_name'] = $IV->list_name;
        }
        return $retval;
    }


    /**
     * Track the view of a product view page.
     *
     * @param   object  $IV     ItemView object
     * @return  object  $this
     */
    public function addProductView(ItemView $IV) : self
    {
        $items = array(
            'items' => array(
                self::convertItemView($IV)
            ),
        );
        $items = json_encode($items);
        $this->addCode("gtag('event', 'view_item', $items)");
        return $this;
    }


    /**
     * Add a cart item to the code.
     *
     * @param   object  $IV     ItemView object (not used)
     * @param   object  $OLV    OrderView object for the whole order
     * @return  object  $this
     */
    public function addCartItem(ItemView $IV, OrderView $OLV) : self
    {
        return $this;
        $sku = $Item->getProductId();
        $dscp = $Item->getDscp();
        $price = $Item->getPrice();
        $qty = $Item->getQuantity();
        $cats = array();
        foreach ($Item->getProduct()->getCategories() as $Cat) {
            $cats[] = $Cat->getName();
        }
        $cats = !empty($cats) ? json_encode($cats) : '';
        $this->addCode("gtag('event', 'add_to_cart', {
            'value: " . ($qty * $price) . ",
            'items: [
                'id': '{$sku}',
                'name': '{$dscp}',
                'brand': '" . $Item->getProduct()->getBrandName() . "',
                'category': 'shirts',
                'variant': 'black',
                'quantity': {$Item->getQuantity()},
                'price': {$price}
        ]);");
        
        /// Records the cart for this visit
        //$this->addCode("_paq.push(['trackEcommerceCartUpdate', $price]);");
        return $this;
    }


    /**
     * Tracks a transaction view, such as "View Cart".
     *
     * @param   object  $OV     OrderView object
     * @param   string  $event  Event type, default = 'purchase'
     * @return  object  $this
     */
    public function addTransactionView(\Analytics\Models\Ecommerce\OrderView $OV, ?string $event=NULL) : self
    {
        if ($event === NULL) {
            $event = 'purchase';
        }

        $data = array(
            'transaction_id' => $OV->transaction_id,
            'value' => $OV->value,
            'affiliation' => $OV->affiliation,
            'currency' => $OV->currency,
            'tax' => $OV->tax,
            'shipping' => $OV->shipping,
            'items' => array(),
        );
        foreach ($OV->items as $IV) {
            $data['items'][] = self::convertItemView($IV);
        }
        $code = json_encode($data);
        $this->addCode("gtag('event', '$event', $code)");
    }


    /**
     * Track an order asynchronously, such as from IPN or webhook.
     *
     * @param   object  $OV     OrderView object
     * @param   array   $trk_info   Array of url and tracker unique ID
     * @param   array   $IPN    Array of IPN or webhook data
     * @return  object  $this
     */
    public function addTransactionViewAsync(OrderView $OV, array $trk_info, array $IPN) : self
    {
        $sess_info = $this->getSessionById($trk_info['s_id']);
        if (!isset($sess_info['Google_cid'])) {
            return $this;
        }
        $cid = $sess_info['Google_cid'];

        require_once Config::get('path') . 'vendor/autoload.php';
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.google-analytics.com/collect'
        ]);

        $params = array(
            'v' => 1,
            't' => 'transaction',
            'tid' => $this->getConfigItem('tracking_id'),
            'cid' => $cid,
            'ti' => $OV->transaction_id,
            'ta' => $OV->affiliation,
            'tr' => $OV->value,
            'ts' => $OV->shipping,
            'tt' => $OV->tax,
            'cu' => $OV->currency,
        );
        $response = $client->request('POST', 'https://www.google-analytics.com/collect',
            ['form_params' => $params]
        );

        foreach ($OV->items as $IV) {
            $params = array(
                'v' => 1,
                't' => 'item',
                'tid' => $this->getConfigItem('tracking_id'),
                'cid' => $cid,
                'ti' => $OV->transaction_id,
                'in' => $IV->short_dscp,
                'ic' => $IV->sku,
                'ip' => $IV->price,
                'iq' => $IV->quantity,
                'iv' => $IV->categories,
                'cu' => $OV->currency,
            );
            $response = $client->request('POST', 'https://www.google-analytics.com/collect',
                ['form_params' => $params]
            );
        }
        return $this;
    }


    /**
     * Create a new session ID for the user to store in the sess_info table.
     *
     * @return  array       Array, including new `cid`
     */
    protected function makeSessionInfo() : array
    {
        return array(
            $this->tracker_id . '_cid' => $this->uuid(),
        );
    }

}

