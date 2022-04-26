<?php
/**
 * Class to create fields for adminlists and other uses.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.0.1
 * @since       v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Analytics;

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
 * Class to create special list fields.
 * @package analytics
 */
class FieldList extends \glFusion\FieldList
{
    /**
     * Return a cached template object to avoid repetitive path lookups.
     *
     * @return  object      Template object
     */
    protected static function init()
    {
        static $t = NULL;

        if ($t === NULL) {
            $t = new \Template(Config::get('path'). 'templates');
            $t->set_file('field', 'fieldlist.thtml');
        } else {
            $t->unset_var('output');
            $t->unset_var('attributes');
        }
        return $t;
    }


    public static function text($args)
    {
        $t = self::init();
        $t->set_block('field','field-text');

        // Go through the required or special options
        $t->set_block('field', 'attr', 'attributes');
        foreach ($args as $name=>$value) {
            $t->set_var(array(
                'name' => $name,
                'value' => $value,
            ) );
            $t->parse('attributes', 'attr', true);
        }
        $t->parse('output', 'field-text');
        return $t->finish($t->get_var('output'));
    }


    public static function add($args)
    {
        $t = self::init();
        $t->set_block('field','field-add');

        if (isset($args['url'])) {
            $t->set_var('url',$args['url']);
        } else {
            $t->set_var('url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field-add','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-add',true);
        return $t->finish($t->get_var('output'));
    }


}
