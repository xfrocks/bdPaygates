<?php
class bdPaygate_DevHelper_Config extends DevHelper_Config_Base {
	protected $_dataClasses = array(
		'log' => array(
			'name' => 'log',
			'camelCase' => 'Log',
			'camelCasePlural' => false,
			'camelCaseWSpace' => 'Log',
			'fields' => array(
				'log_id' => array('name' => 'log_id', 'type' => 'uint', 'autoIncrement' => true),
				'processor' => array('name' => 'processor', 'type' => 'string', 'length' => 20, 'required' => true),
				'transaction_id' => array('name' => 'transaction_id', 'type' => 'string', 'length' => 50, 'required' => true),
				'log_type' => array('name' => 'log_type', 'type' => 'string', 'length' => 20, 'required' => true),
				'log_message' => array('name' => 'log_message', 'type' => 'string', 'length' => 255, 'required' => true),
				'log_details' => array('name' => 'log_details', 'type' => 'serialized'),
				'log_date' => array('name' => 'log_date', 'type' => 'uint', 'required' => true)
			),
			'phrases' => array(),
			'id_field' => 'log_id',
			'title_field' => 'processor',
			'primaryKey' => array('log_id'),
			'indeces' => array(
				'transaction_id' => array('name' => 'transaction_id', 'fields' => array('transaction_id'), 'type' => 'NORMAL')
			),
			'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false)
		)
	);
	protected $_dataPatches = array();
	protected $_exportPath = '/Users/sondh/Dropbox/XenForo/bdPaygate';
	protected $_exportIncludes = array('bdpaygate/callback.php');
	
	/**
	 * Return false to trigger the upgrade!
	 * common use methods:
	 * 	public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array())
	 *	public function addDataPatch($table, array $field)
	 *	public function setExportPath($path)
	**/
	protected function _upgrade() {
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
			'primary_key_field_here',
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