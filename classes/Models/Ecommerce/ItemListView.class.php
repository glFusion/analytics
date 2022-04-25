<?php
/**
 * Model for the item list view in web analytics.
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
 * Class to model data for an item view.
 * @package analytics
 */
class ItemListView
{
    /** Array of ItemView objects in the list.
     * @var array */
    public $items = array();

    /** List position, incremented when ItemViews are added.
     * @var array */
    private static $list_position = 1;


    /**
     * Get the static instance of the list view
     *
     * @return  object      ItemView object
     */
    public static function getInstance() : self
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }


    /**
     * Add a single item to the item list.
     *
     * @param   object  $IV     ItemView object to add
     * @return  object  $this
     */
    public function addItem(ItemView $IV) : self
    {
        $item->list_position = self::$list_position++;
        $this->items[] = $IV;
        return $this;
    }

}

