<?php
/**
 * Base tracker class for Analytics.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner
 * @package     analytics
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Analytics;
use Analytics\Models\Ecommerce\ItemListView;
use Analytics\Models\Ecommerce\ItemView;
use Analytics\Models\Ecommerce\OrderView;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Base tracker class, extend for specific trackers.
 * @package analytics
 */
class Tracker
{
    /** Tracker module ID, e.g. the tracker name.
     * @var string */
    protected $tracker_id = '';

    /** Flag indicating the tracker is enabled.
     * @var boolean */
    protected $enabled = 0;

    /** Base code used by the tracker.
     * @var string */
    protected $base_code = '';

    /** Configuration data for the tracker.
     * @var array */
    protected $config = array();

    /** Description or short name of the tracker.
     * @var string */
    protected $dscp = '';

    /** Flag indicating that the tracker has been installed.
     * Any tracker in the DB gets this set to 1.
     * @var boolean */
    protected $installed = 0;

    /** Codes to be added to the tracker.
     * Two sets are available in case the tracker needs to split tye code.
     * @var array */
    protected $codes = array('pre' => [], 'post' => []);

    /** Configuration fields, unique to each tracker.
     * @var array */
    protected $cfgFields = array();

    /** Session variable key to save tracker info in the PHP session.
     * The tracker_id is appended to this key.
     * @var string */
    private $sess_var = 'analytics_';

    /** Session information that is saved in the PHP session for later use.
     * @var array */
    protected $session_info = array(
        'sess_id' => '',
        'uniq_id' => '',
        'info' => array(),
    );

    /**
     * Load the tracker from a DB record, if provided.
     *
     * @param   array   $A      Array from the database
     */
    public function __construct(?array $A=NULL)
    {
        if (is_array($A)) {
            $this->setVars($A);
        }
        $this->sess_var .= $this->tracker_id;
        if (SESS_isSet($this->sess_var)) {
            $this->codes = SESS_getVar($this->sess_var);
        }
    }


    /**
     * Create an instance of a tracker.
     * This function ignores the `installed` state and simply creates an object
     * from the class file, if present.
     *
     * @param   string  $gw_name    Gateway name
     * @return  object      Gateway object
     */
    public static function create($tracker_id)
    {
        global $_TABLES;

        $tracker_id = ucfirst($tracker_id);     // to make sure
        $cls = __NAMESPACE__ . "\\Trackers\\{$tracker_id}";
        try {
            $Tracker = new $cls;
         } catch (\Exception $e) {
            $Tracker = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT *, 1 as installed FROM {$_TABLES['ua_trackers']}
                WHERE tracker_id = ?",
                [$tracker_id],
                [Database::STRING]
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            $data = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        if (is_array($data)) {
            $Tracker->setVars($data);
        }
        return $Tracker;
    }


    /**
     * Create an instance of a tracker from a data array.
     * This function ignores the `installed` state and simply creates an object
     * from the class file, if present.
     *
     * @param   string  $gw_name    Gateway name
     * @return  object      Gateway object
     */
    public static function createFromArray(array $A) : self
    {
        if (isset($A['tracker_id'])) {
            $cls = __NAMESPACE__ . "\\Trackers\\" . ucfirst($A['tracker_id']);
            if (class_exists($cls)) {
                $Tracker = new $cls;
                $Tracker->setVars($A);
            } else {
                $Tracker = NULL;
            }
        }
        return $Tracker;
    }


    /**
     * Get the single instance of a tracker, or create if not yet instantiated.
     *
     * @param   string  $tracker_id     Tracker ID
     * @return  object      Tracker object
     */
    public static function getInstance(string $tracker_id) : self
    {
        static $Trackers = array();
        $tracker_id = ucfirst($tracker_id);
        if (!array_key_exists($tracker_id, $Trackers)) {
            $Trackers[$tracker_id] = self::create($tracker_id);
        }
        return $Trackers[$tracker_id];
    }


    /**
     * Helper function to set all the tracker properties from the DB record.
     *
     * @param   array   $A      Array of data
     * @return  object  $this
     */
    public function setVars(array $A) : self
    {
        $this->tracker_id = $A['tracker_id'];
        $this->enabled = isset($A['enabled']) && $A['enabled'] ? 1 : 0;
        $this->base_code = $A['base_code'];
        $this->installed = isset($A['installed']) && $A['installed'] ? 1 : 0;
        $config = @json_decode($A['config'], true);
        if (is_array($config)) {
            // Merge into config property, preserving any defaults not yet in the DB.
            foreach ($config as $key=>$val) {
                if (array_key_exists($key, $this->cfgFields)) {
                    if ($this->cfgFields[$key] == 'password') {
                        $decrypted = COM_decrypt($val);
                        if ($decrypted !== false) {
                            $val = $decrypted;
                        }
                    }
                    $this->config[$key] = $val;
                }
            }
        } else {
            $this->config = array();
        }
        return $this;
    }


    /**
     * Set a configuration value.
     *
     * @param   string  $key    Name of configuration item
     * @param   mixed   $value  Value to set
     * @return  object  $this
     */
    public function setConfig($key, $value) : self
    {
        $this->config[$key] = $value;
        return $this;
    }


    /**
     * Check if this tracker is installed or not.
     *
     * @return  boolean     True if installed, False if not.
     */
    public function isInstalled() : bool
    {
        return $this->installed;
    }


    /**
     * Get the name of the tracker module.
     *
     * @return  string      Name of tracker
     */
    public function getName() : string
    {
        return ucfirst($this->tracker_id);
    }


    /**
     * Get the final code to be placed into the template.
     *
     * @return  string      Complete tracking code
     */
    public function getCode() : string
    {
        return '';
    }


    /**
     * Clear out both code fields.
     * Called after getCode() when the final code is set in the web page.
     */
    public function clearCodes() : void
    {
        $this->codes = array('pre' => [], 'post' => []);
        SESS_unset($this->sess_var);
    }


    /**
     * Track a product list view, e.g. product catalog.
     *
     * @param  object  $ILV    ItemListView object
     * @return  object  $this
     */
    public function addProductListView(ItemListView $ILV) : self
    {
        return $this;
    }


    /**
     * Track a single product view.
     *
     * @param   object  $IV     ItemView object
     * @return  object  $this
     */
    public function addProductView(ItemView $IV) : self
    {
        return $this;
    }


    /**
     * Add an item to the shopping cart.
     *
     * @param   object  $IV     ItemView object for the item being added
     * @param   object  $OLV    OrderView for the entire order, including the item
     * @return  object  $this
     */
    public function addCartItem(ItemView $IV, OrderView $OLV) : self
    {
        return $this;
    }


    public function addCartView(array $data) : self
    {
        return $this;
    }


    public function delCart(array $data) : self
    {
        return $this;
    }


    public function clearCart() : self
    {
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
        return $this;
    }


    /**
     * Add a transaction view such as "view cart".
     *
     * @param   object  $OV     OrderView object
     * @param   string  $event  Standard event type
     */
    public function addTransactionView(OrderView $OV, ?string $event=NULL) : self
    {
        return $this;
    }


    /**
     * Get an array of uninstalled Trackers for the admin list.
     *
     * @param   array   $data_arr   Reference to data array
     */
    private static function getUninstalled(array &$data_arr) : void
    {
        global $LANG32;

        $installed = self::getAll(false);
        $base_path = __DIR__ . '/Trackers';
        $files = glob($base_path. '/*.class.php');
        if (is_array($files)) {
            foreach ($files as $fname) {
                $parts = pathinfo($fname);
                $base_name = basename($parts['basename'], '.class.php');
                if (array_key_exists($base_name, $data_arr)) {
                    continue;
                }
                $clsname = 'Analytics\\Trackers\\' . $base_name;
                try {
                    $Tracker = new $clsname;
                } catch (\Exception $e) {
                    $Tracker = NULL;
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                }
                if (is_object($Tracker)) {
                    $data_arr[$base_name] = array(
                        'tracker_id'    => $Tracker->getName(),
                        //'description' => $Tracker->getDscp(),
                        'enabled' => 0,
                        'installed' => 0,
                    );
                }
            }
        }
    }


    /**
     * Get all the installed trackers for the admin list.
     *
     * @param   array   $data_arr   Reference to data array
     */
    private static function getInstalled(&$data_arr)
    {
        global $_TABLES;

        $sql = "SELECT * FROM {$_TABLES['ua_trackers']}";
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery($sql)->fetchAll(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            $data = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        if (is_array($data)) {
            foreach ($data as $A) {
                $data_arr[$A['tracker_id']] = array(
                    'tracker_id' => $A['tracker_id'],
                    'enabled' => $A['enabled'],
                    'installed' => 1,
                );
            }
        }
    }


    /**
     * Get all installed into a static array.
     *
     * @param   boolean $enabled    True to get only enabled Trackers
     * @return  array       Array of Trackers, enabled or all
     */
    public static function getAll($enabled = false)
    {
        global $_TABLES;

        $Trackers = array();
        $key = $enabled ? 1 : 0;
        $Trackers[$key] = array();
        $cache_key = 'Trackers_' . $key;
        //        $tmp = Cache::get($cache_key);
        $tmp = NULL;
        if ($tmp === NULL) {
            $tmp = array();
            // Load the Trackers
            $db = Database::getInstance();
            $sql = "SELECT *, 1 as installed FROM {$_TABLES['ua_trackers']}";
            // If not loading all Trackers, get just then enabled ones
            if ($enabled) {
                $sql .= ' WHERE enabled=1';
            }
            try {
                $data = $db->conn->executeQuery($sql)->fetchAll(Database::ASSOCIATIVE);
            } catch (\Exception $e) {
                $data = NULL;
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            }
            if (is_array($data)) {
                foreach ($data as $A) {
                    $cls = __NAMESPACE__ . '\\Trackers\\' . $A['tracker_id'];
                    if (class_exists($cls)) {
                        $Tracker = new $cls($A);
                    } else {
                        $Tracker = NULL;
                    }
                    if (is_object($Tracker)) {
                        $Trackers[$key][$A['tracker_id']] = $Tracker;
                    } else {
                        continue;       // Tracker enabled but not installed
                    }
                }
            }
        }
        return $Trackers[$key];
    }



    /**
     * Helper function to get all the enabled trackers.
     *
     * @return  array       Array of enabled Tracker objects
     */
    public static function getEnabled() : array
    {
        return self::getAll(true);
    }


    /**
     * Tracker admin list view
     *
     * @return  string      HTML for the gateway listing
     */
    public static function adminList() : string
    {
        global $_CONF, $_TABLES, $LANG_UA, $_USER, $LANG_ADMIN,
            $LANG32;

        $data_arr = array();
        self::getInstalled($data_arr);
        self::getUninstalled($data_arr);

        $header_arr = array(
            array(
                'text'  => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => 'ID',
                'field' => 'tracker_id',
                'sort'  => true,
            ),
            array(
                'text'  => $LANG_UA['control'],
                'field' => 'enabled',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => 'false',
                'align' => 'center',
            ),
        );

        $extra = array();

        $defsort_arr = array(
            'field' => 'tracker_id',
            'direction' => 'ASC',
        );

        $display = COM_startBlock(
            '', '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $text_arr = array(
            'has_extras' => false,
            'form_url' => Config::get('admin_url') . '/index.php',
        );
        $display .= ADMIN_listArray(
            Config::PI_NAME . '_trackerlist',
            array(__CLASS__,  'getAdminField'),
            $header_arr, $text_arr, $data_arr, $defsort_arr,
            '', $extra, '', ''
        );
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $display;
    }


    /**
     * Get an individual field for the options admin list.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @param   array   $extra      Extra information passed in verbatim
     * @return  string              HTML for field display in the table
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr, $extra='')
    {
        global $_CONF, $LANG_UA, $LANG_ADMIN;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            if ($A['installed']) {
                $retval .= FieldList::edit(array(
                    'url' => Config::get('admin_url') . '/index.php?config=' . $A['tracker_id'],
                ) );
            }
            break;

        case 'enabled':
            if (!$A['installed']) {
                return FieldList::add(array(
                    'url' => Config::get('admin_url') . '/index.php?installtracker=' . urlencode($A['tracker_id']),
                    array(
                        'title' => $LANG_UA['ck_to_install'],
                    )
                ) );
            } elseif ($fieldvalue == '1') {
                $enabled = 1;
                $tip = $LANG_UA['ck_to_disable'];
            } else {
                $enabled = 0;
                $tip = $LANG_UA['ck_to_enable'];
            }
            $retval .= FieldList::checkbox(array(
                'name' => 'ena_check',
                'id' => "togenabled{$A['tracker_id']}",
                'checked' => $fieldvalue == 1,
                'title' => $tip,
                'onclick' => "Analytics.toggle(this,'{$A['tracker_id']}','{$fieldname}','tracker');",
            ) );
            break;

        case 'delete':
            if ($A['installed']) {
                $retval = FieldList::delete(array(
                    'delete_url' => Config::get('admin_url') . '/index.php?uninstall=' . urlencode($A['tracker_id']),
                    'attr' => array(
                        'onclick' => 'return confirm(\'' . $LANG_UA['q_del_item'] . '\');',
                        'title' => $LANG_UA['del_item'],
                        'class' => 'tooltip',
                    ),
                ) );
            }
            break;

        default:
            $retval = htmlspecialchars($fieldvalue, ENT_QUOTES, COM_getEncodingt());
            break;
        }

        return $retval;
    }


    /**
     * Install a new tracker into the table.
     *
     * @return  boolean     True on success, False on failure
     */
    public function install()
    {
        global $_TABLES;

        // Only install the gateway if it isn't already installed
        $installed = self::getAll(false);
        if (!array_key_exists($this->tracker_id, $installed)) {
            $db = Database::getInstance();
            try {
                $status = $db->conn->executeUpdate(
                    "INSERT INTO {$_TABLES['ua_trackers']} SET tracker_id = ?",
                    [$this->tracker_id],
                    [Database::STRING]
                );
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $status = 0;
            }
        }
        return $status ? true : false;
    }


    /**
     * Sets the "enabled" field to the opposite of the specified value.
     *
     * @uses    self::_toggle()
     * @param   integer $oldvalue   Original value
     * @param   string  $id         Gateway ID
     * @return  integer             New value, or old value upon failure
     */
    public static function toggleEnabled(int $oldvalue, string $id) : int
    {
        global $_TABLES;

        // Determing the new value (opposite the old)
        $oldvalue = $oldvalue == 1 ? 1 : 0;
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $db = Database::getInstance();
        try {
            $status = $db->conn->executeUpdate(
                "UPDATE {$_TABLES['ua_trackers']}
                SET enabled = ?
                WHERE tracker_id = ?",
                [$newvalue, $id],
                [Database::INTEGER, Database::STRING]
            );
        } catch (\Exception $e) {
            $newvale = $oldvalue;
            Log::write('system', 'error', __METHOD__ . ': ' . $e->getMessage());
        }
        return $newvalue;
    }


    /**
     * Default function to add a snippet of tracking code.
     * Uses the "pre" code area.
     *
     * @param   string  $code_txt   Code to add
     * @return  object  $this
     */
    public function addCode(string $code_txt) : self
    {
        return $this->addPreCode($code_txt);
    }


    /**
     * Add a snippet of code to the Pre value.
     *
     * @param   string  $code_txt   Code to add
     * @return  object  $this
     */
    public function addPreCode(string $code_txt) : self
    {
        $this->codes['pre'][] = $code_txt;
        SESS_setVar($this->sess_var, $this->codes);
        return $this;
    }


    /**
     * Get the code to be placed in the Pre area.
     *
     * @return  string      Code strings separated by newlines
     */
    protected function getPreCode() : string
    {
        if (!empty($this->codes['pre'])) {
            return implode("\n", $this->codes['pre']) . "\n";
        } else {
            return '';
        }
    }


    /**
     * Add a snippet of code to the Post value.
     * Some trackes may split the "special" code into before and after.
     *
     * @param   string  $code_txt   Code to add
     * @return  object  $this
     */
    public function addPostCode(string $code_txt) : self
    {
        $this->codes['post'][] = $code_txt;
        SESS_setVar($this->sess_var, $this->codes);
        return $this;
    }


    /**
     * Get the value of a single configuration item.
     *
     * @param   string  $name       Name of config item.
     * @return  string      Value of item
     */
    public function getConfigItem(string $name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        } else {
            return '';
        }
    }


    /**
     * Present the configuration form for a tracker.
     *
     * @return  string      HTML for the configuration form.
     */
    public function Configure()
    {
        global $_CONF, $LANG_UA, $_TABLES;

        $T = new \Template(Config::get('path') . 'templates');
        $T->set_file(array(
            'tpl' => 'tracker_edit.thtml',
            'tips' => 'tooltipster.thtml',
        ) );

        // Load the language for this gateway and get all the config fields
        $T->set_var(array(
            'tracker_id' => $this->tracker_id,
            'enabled_chk'   => $this->enabled == 1 ? ' checked="checked"' : '',
            'pi_admin_url'  => Config::get('admin_url'),
            'doc_url' => Config::get('url') . '/docs/english/hlp_' . strtolower($this->tracker_id) . '.html',
        ), false, false);

        $fields = $this->getConfigFields();
        $T->set_block('tpl', 'fieldRow', 'Rowfield');
        foreach ($fields as $name=>$field) {
            $parts = array_map('ucfirst', explode('_', $name));
            $prompt = implode(' ', $parts);
            $T->set_var(array(
                'param_name'    => $prompt,
                'field_name'    => $name,
                'param_field'   => $field['param_field'],
                'other_label'   => isset($field['other_label']) ? $field['other_label'] : '',
                //'hlp_text'      => $this->getLang('hlp_' . $name, ''),
            ) );
            $T->parse('Rowfield', 'fieldRow', true);
        }
        $T->parse('tooltipster_js', 'tips');
        $T->parse('output', 'tpl');
        $form = $T->finish($T->get_var('output'));
        return $form;
    }


    /**
     * Create the fields for the configuration form.
     *
     * @return  array   Array of fields (name=>field_info)
     */
    protected function getConfigFields()
    {
        $fields = array();
        foreach ($this->cfgFields as $name=>$type) {
            if (strpos($type, ',') > 0) {
                list($type,$num) = explode(',', $type);
            }
            switch ($type) {
            case 'checkbox':
                $field = FieldList::checkbox(array(
                    'name' => $name,
                    'checked' => (isset($this->config[$name]) && $this->config[$name] == 1),
                ) );
                break;
            case 'array':
                $field = '';
                if (!isset($this->config[$name])) {
                    $this->config[$name] = array();
                }
                for ($i = 1; $i < $num+1; $i++) {
                    if (isset($this->config[$name][$i])) {
                        $val = $this->config[$name][$i];
                    } else {
                        $val = '';
                    }
                    $field .= $i . ': ' . FieldList::text(array(
                        'name' => $name . '[' . $i . ']',
                        'value' => $val,
                    ) ) . '<br />';
                }
                break;
            default:
                if (isset($this->config[$name])) {
                    $val = $this->config[$name];
                } else {
                    $val = '';
                }
                $field = FieldList::text(array(
                    'name' => $name,
                    'value' => $val,
                ) );
                break;
            }
            $fields[$name] = array(
                'param_field' => $field,
                'doc_url'       => '',
            );
        }
        return $fields;
    }


    /**
     * Save the config variables.
     *
     * @param   array   $A      Array of config items, e.g. $_POST
     * @return  boolean         True if saved successfully, False if not
     */
    public function saveConfig(?array $A = NULL) : bool
    {
        global $_TABLES;

        if (is_array($A)) {
            $this->enabled = isset($A['enabled']) ? 1 : 0;

            // Only update config if provided from form
            foreach ($this->cfgFields as $name=>$type) {
                switch ($type) {
                case 'checkbox':
                    $value = isset($A[$name]) ? 1 : 0;
                    break;
                case 'password':
                    $value = COM_encrypt($A[$name]);
                    break;
                default:
                    $value = $A[$name];
                    break;
                }
                $this->setConfig($name, $value);
            }
        }

        $config = @json_encode($this->config);
        if (!$config) return false;

        $db = Database::getInstance();
        $sql = "UPDATE {$_TABLES['ua_trackers']} SET
            config = ?,
            enabled = ?
            WHERE tracker_id = ?";
        try {
            $status = $db->conn->executeUpdate(
                $sql,
                [$config, $this->enabled, $this->tracker_id],
                [Database::STRING, Database::INTEGER, Database::STRING]
            );
        } catch (\Exception $e) {
            $status = 0;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        return $status;
    }


    protected function _curlExec(string $url) : bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code != 200) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ": Error sending tracking code: code $code");
            return false;
        }
        return true;
    }


    /**
     * Get session information by record ID.
     * Added to allow for shorter strings passed to Ecommerce gateways.
     *
     * @param   integer $s_id       Session record ID
     * @return  array       Database record, empty array if not found
     */
    public function getSessionById(int $s_id) : array
    {
        global $_TABLES;

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['ua_sess_info']}
                WHERE s_id = ? AND tracker_id = ?",
                [$s_id, $this->tracker_id],
                [Database::INTEGER, Database::STRING]
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            $data = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        if (is_array($data)) {
            return json_decode($data['trk_info'], true);
        } else {
            return array();
        }
    }


    /**
     * Get the session data record by the unique ID value.
     *
     * @param   string  $uniq_id    Unique ID string
     * @return  array       Database record, empty array if not found
     */
    public function getSessionByUniqId(string $uniq_id) : array
    {
        global $_TABLES;

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['ua_sess_info']}
                WHERE uniq_id = ? AND tracker_id = ?",
                [$uniq_id, $this->tracker_id],
                [Database::STRING, Database::STRING]
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            $data = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        if (is_array($data)) {
            return json_decode($data['trk_info'], true);
        } else {
            return array();
        }
    }


    /**
     * Gets the session information for the current visitor.
     * Creates a new session record if none found.
     *
     * @return  array   Array of session data
     */
    public function getSessionInfo() : array
    {
        global $_TABLES;

        $sess_id = session_id();
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['ua_sess_info']}
                WHERE sess_id = ? AND tracker_id = ?",
                [$sess_id, $this->tracker_id],
                [Database::STRING, Database::STRING]
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            $data = NULL;
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        if (is_array($data)) {
            $this->session_info = array(
                's_id' => $data['s_id'],
                'sess_id' => $sess_id,
                'uniq_id' => $data['uniq_id'],
                'info' => json_decode($data['trk_info']),
            );
        } elseif ($data === false) {    // not found
            $uniqid = uniqid();
            $this->session_info = array(
                'sess_id' => $sess_id,
                'uniq_id' => $uniqid,
                'info' => $this->makeSessionInfo(),
            );
            try {
                $status = $db->conn->executeUpdate(
                    "INSERT INTO {$_TABLES['ua_sess_info']} SET
                    sess_id = ?, tracker_id = ?, uniq_id = ?, trk_info = ?",
                    [$sess_id, $this->tracker_id, $uniqid, json_encode($this->session_info['info'])],
                    [Database::STRING, Database::STRING, Database::STRING, Database::STRING]
                );
                if ($status) {
                    $this->session_info['s_id'] = $db->conn->lastInsertId();
                }
            } catch (\Exception $e) {
                $status = false;
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            }
        }
        return $this->session_info;
    }


    /**
     * Default function to create a unique user ID.
     *
     * @return  array   Array containing a 16-character customer ID
     */
    protected function makeSessionInfo() : array
    {
        $arr = \array_values(\unpack('N1a/n4b/N1c', \openssl_random_pseudo_bytes(16)));
        $arr[2] = ($arr[2] & 0x0fff) | 0x4000;
        return array(
            $this->tracker_id . '_cid' => \vsprintf('%08x%04x%04x', $arr),
        );
    }


    /**
     * Returns a v4 UUID.
     *
     * @return  string
     */
    public static function uuid() : string
    {
        $arr = \array_values(\unpack('N1a/n4b/N1c', \openssl_random_pseudo_bytes(16)));
        $arr[2] = ($arr[2] & 0x0fff) | 0x4000;
        $arr[3] = ($arr[3] & 0x3fff) | 0x8000;
        return \vsprintf('%08x-%04x-%04x-%04x-%04x%08x', $arr);
    }

}

