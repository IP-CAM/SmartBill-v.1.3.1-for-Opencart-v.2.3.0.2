<?php
/**
 * @copyright  Intelligent IT SRL 2019
 */

class SmartBillDataLogger{
    private $order_id;
    private $existing_data;
    /**
     * Getter for OpenCart ID
     *
     * @return void
     */
    public function get_order_id() {
        return $this->order_id;
    }

    /**
     * Constructor - if data does not exist - we do not set the order_id because we need to retrieve it
     * in a separate call if data exists (in order to append JSON exchanged data) and we do not want false negatives
     *
     * @return void
     */
    public function __construct($order_id) {

        // get DB instance
		$this->db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

        $existing_data = $this->get_db_log($order_id);

        //if data exists, fill in the details
        if (! empty($existing_data)){
            $this->existing_data = $existing_data;
            $this->order_id = $order_id;
        } else {
            $this->existing_data = null;
        }
    }

    /**
     * This function retrieves existing data by OpenCart order ID
     *
     * @return void
     */
    public function get_data($order_id, $key = null) {
        if (!is_numeric($order_id) || !$key) return false;

        $existing_data = $this->get_db_log($order_id);
        if ( $key && array_key_exists($key, $existing_data)) return $existing_data[$key];
        return $existing_data;
    }

    /**
     * This function saves exchanged data between OpenCart and SmartBill Cloud
     *
     * @return void
     */
    public function set_data($order_id, $key, $value) {
        if (!is_numeric($order_id) || !$key) return false;
        $this->existing_data[$key] = $value;
        return $this;
    }

    /**
     * Save information into database
     *
     * @return void
     */
    public function save($order_id = null) {
        if (! $this->existing_data) return false;
        if (! $this->get_order_id()){
            $this->order_id = $order_id;
        }
        $existing_data = $this->db->escape(serialize($this->existing_data));

        $status = $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `smartbill_invoice_log` = '$existing_data' WHERE order_id = {$order_id}");

        return $status;
    }

    private function get_db_log($order_id) {
        $order_sql = $this->db->query("SELECT smartbill_invoice_log FROM `" . DB_PREFIX . "order` WHERE order_id = {$order_id}");
        if (isset($order_sql->row['smartbill_invoice_log'])) {
            return unserialize($order_sql->row['smartbill_invoice_log']);
        } else {
            return unserialize('');
        }
    }

}
