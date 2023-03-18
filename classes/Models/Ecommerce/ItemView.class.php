<?php
/**
 * Model for the item view in web analytics.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022-2023 Lee Garner <lee@leegarner.com>
 * @package     analytics
 * @version     v0.0.1
 * @since       v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Analytics\Models\Ecommerce;


/**
 * Class to model data for an item view.
 * @package analytics
 */
class ItemView
{
    /** Item ID, e.g. record ID.
     * @var string */
    public $item_id = '';

    /** Item SKU, aka Item Name.
     * @var string */
    public $sku = '';

    /** Item short description.
     * @var string */
    public $short_dscp = '';

    /** Item long description.
     * @var string */
    public $long_dscp = '';

    /** Item unit price.
     * @var float */
    public $price = 0;

    /** Item quantity.
     * @var float */
    public $quantity = 1;

    /** Item variant name.
     * @var string */
    public $variant = '';

    /** Item categories as a comma-separated string.
     * @var string */
    public $categories = '';

    /** Item brand name.
     * @var string */
    public $brand = '';

    /** Item list name, e.g. "Main Catalog".
     * @var string */
    public $list_name = '';

    /** Item list position, increment as items are added to the ItemListView.
     * @var integer */
    public $list_position = 1;

    /** Array of field names to facilitate loading properties.
     * @var array */
    static $_fields = array(
        'item_id',
        'sku',
        'short_dscp',
        'long_dscp',
        'price',
        'quantity',
        'variant',
        'categories',
        'brand',
        'list_name',
        'list_position',
    );


    /**
     * Create an ItemView object from an array of data.
     *
     * @param   array   $A      Array of data
     */
    public function __construct(?array $A=NULL)
    {
        if (is_array($A)) {
            foreach (self::$_fields as $key) {
                if (array_key_exists($key, $A)) {
                    $this->$key = $A[$key];
                }
            }
        }
    }


    /**
     * Get the object properties as an array.
     *
     * @return  array   Array of properties
     */
    public function toArray() : array
    {
        $retval = array(
            'item_id' => $this->item_id,
            'sku' => $this->sku,
            'short_dscp' => $this->short_dscp,
            'long_dscp' => $this->long_dscp,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'variant' => $this->variant,
            'categories' => $this->categories,
            'brand' => $this->brand,
            'list_position' => $this->list_position,
        );
        if (!empty($this->list_name)) {
            $retval['list_name'] = $this->list_name;
        }
        return $retval;
    }

}
