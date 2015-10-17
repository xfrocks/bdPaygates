<?php

class bdPaygate_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataClasses = array(
        'log' => array(
            'name' => 'log',
            'camelCase' => 'Log',
            'camelCasePlural' => 'Logs',
            'camelCaseWSpace' => 'Log',
            'fields' => array(
                'log_id' => array('name' => 'log_id', 'type' => 'uint', 'autoIncrement' => true),
                'processor' => array('name' => 'processor', 'type' => 'string', 'length' => 20, 'required' => true),
                'transaction_id' => array('name' => 'transaction_id', 'type' => 'string', 'length' => 50, 'required' => true),
                'log_type' => array('name' => 'log_type', 'type' => 'string', 'length' => 20, 'required' => true),
                'log_message' => array('name' => 'log_message', 'type' => 'string', 'length' => 255, 'required' => true),
                'log_details' => array('name' => 'log_details', 'type' => 'serialized'),
                'log_date' => array('name' => 'log_date', 'type' => 'uint', 'required' => true),
            ),
            'phrases' => array(),
            'id_field' => 'log_id',
            'title_field' => 'processor',
            'primaryKey' => array('log_id'),
            'indeces' => array(
                'transaction_id' => array('name' => 'transaction_id', 'fields' => array('transaction_id'), 'type' => 'NORMAL'),
            ),
            'files' => array(
                'data_writer' => false,
                'model' => array('className' => 'bdPaygate_Model_Log', 'hash' => '5c6481c3da2edd72f397c6cb027c2fb4'),
                'route_prefix_admin' => false,
                'controller_admin' => false,
            ),
        ),
        'purchase' => array(
            'name' => 'purchase',
            'camelCase' => 'Purchase',
            'camelCasePlural' => 'Purchases',
            'camelCaseWSpace' => 'Purchase',
            'fields' => array(
                'purchase_id' => array('name' => 'purchase_id', 'type' => 'uint', 'autoIncrement' => true),
                'user_id' => array('name' => 'user_id', 'type' => 'uint', 'required' => true),
                'content_type' => array('name' => 'content_type', 'type' => 'string', 'length' => 25, 'required' => true),
                'content_id' => array('name' => 'content_id', 'type' => 'uint', 'required' => true),
                'purchase_date' => array('name' => 'purchase_date', 'type' => 'uint', 'required' => true),
                'purchased_amount' => array('name' => 'purchased_amount', 'type' => 'string', 'length' => 10, 'required' => true),
                'purchased_currency' => array('name' => 'purchased_currency', 'type' => 'string', 'length' => 3, 'required' => true),
            ),
            'phrases' => array(),
            'id_field' => 'purchase_id',
            'title_field' => 'content_type',
            'primaryKey' => array('purchase_id'),
            'indeces' => array(
                'content_type_content_id' => array(
                    'name' => 'content_type_content_id',
                    'fields' => array('content_type', 'content_id'),
                    'type' => 'NORMAL',
                ),
                'user_id' => array('name' => 'user_id', 'fields' => array('user_id'), 'type' => 'NORMAL'),
            ),
            'files' => array(
                'data_writer' => array('className' => 'bdPaygate_DataWriter_Purchase', 'hash' => '5a647e591a893776f62e5d920a291cb9'),
                'model' => array('className' => 'bdPaygate_Model_Purchase', 'hash' => '8fc24f3e66bd6fad338d9d9cfe4c5670'),
                'route_prefix_admin' => false,
                'controller_admin' => false,
            ),
        ),
    );
    protected $_dataPatches = array(
        'xf_resource_category' => array(
            'bdpaygate_allow_commercial_local' => array('name' => 'bdpaygate_allow_commercial_local', 'type' => 'uint', 'required' => true, 'default' => 0),
        ),
    );
    protected $_exportPath = '/Users/sondh/XenForo/bdPaygate';
    protected $_exportIncludes = array('bdpaygate/callback.php');
    protected $_exportExcludes = array();
    protected $_exportAddOns = array();
    protected $_exportStyles = array();
    protected $_options = array();

    /**
     * Return false to trigger the upgrade!
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        $this->addDataClass(
            'name_here',
            array( // fields
                'field_here' => array(
                    'type' => 'type_here',
                    // 'length' => 'length_here',
                    // 'required' => true,
                    // 'allowedValues' => array('value_1', 'value_2'),
                    // 'default' => 0,
                    // 'autoIncrement' => true,
                ),
                // other fields go here
            ),
            array('primary_key_1', 'primary_key_2'), // or 'primary_key', both are okie
            array( // indeces
                array(
                    'fields' => array('field_1', 'field_2'),
                    'type' => 'NORMAL', // UNIQUE or FULLTEXT
                ),
            ),
        );
        */
    }
}