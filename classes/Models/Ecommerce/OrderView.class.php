<?php
/**
 * Model for the order view in web analytics.
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
namespace Analytics\Models\Ecommerce;


/**
 * Class to model data for an order.
 * @package analytics
 */
class OrderView extends ItemListView
{
    /** Order ID.
     * @var string */
    public $transaction_id = '';

    /** Shop name.
     * @var string */
    public $affiliation = '';

    /** Total order value, including tax and shipping.
     * @var float */
    public $value = 0;

    /** Currency code.
     * @var string */
    public $currency = 'USD';

    /** Sales tax amount.
     * @var float */
    public $tax = 0;

    /** Shipping fees.
     * @var float */
    public $shipping = 0;

    /** Discount amount.
     * @var float */
    public $discount = 0;

    /** Payment gateway name.
     * @var float */
    public $gateway = '';


    /**
     * Get the instance of the object, or create a new one if needed.
     *
     * @return  object  Current OrderView object
     */
    public static function getInstance() : self
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }

}

