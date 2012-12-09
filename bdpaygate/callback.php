<?php

$startTime = microtime(true);

// we have to figure out XenForo path
// dirname(dirname(__FILE__)) should work most of the time
// as it was the way XenForo's index.php does
// however, sometimes it may not work...
// so we have to be creative
$parentOfDirOfFile = dirname(dirname(__FILE__));
$scriptFilename = (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
$pathToCheck = '/library/XenForo/Autoloader.php';
$fileDir = false;
if (file_exists($parentOfDirOfFile . $pathToCheck))
{
	$fileDir = $parentOfDirOfFile;
}
if ($fileDir === false AND !empty($scriptFilename))
{
	$parentOfDirOfScriptFilename = dirname(dirname($scriptFilename));
	if (file_exists($parentOfDirOfScriptFilename . $pathToCheck))
	{
		$fileDir = $parentOfDirOfScriptFilename;
	} 
}
if ($fileDir === false)
{
	die('XenForo path could not be figured out...');
}
// finished figuring out $fileDir

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

if (!isset($_GET['p']))
{
	die('Invalid callback request');
}

$processorId = $_GET['p'];
$processorModel = XenForo_Model::create('bdPaygate_Model_Processor');
$names = $processorModel->getProcessorNames();

if (!isset($names[$processorId]))
{
	die('Invalid processor specified');
}

$processor = bdPaygate_Processor_Abstract::create($names[$processorId]);
$request = new Zend_Controller_Request_Http();
$response = new Zend_Controller_Response_Http();

$logType = '';
$logMessage = '';
$logDetails = array();
$transactionId = false;
$paymentStatus = false;
$itemId = false;

try
{
	if (!$processor->validateCallback($request, $transactionId, $paymentStatus, $logDetails, $itemId))
	{
		$logType = 'error';
		$logMessage = $processor->getLastError();

		$response->setHttpResponseCode(500);
	}
	else
	{
		$logType = $paymentStatus;
		$logMessage = $processor->processTransaction($paymentStatus, $itemId);
	}
}
catch (Exception $e)
{
	$response->setHttpResponseCode(500);
	XenForo_Error::logException($e);

	$logType = 'error';
	$logMessage = 'Exception: ' . $e->getMessage();
	$logDetails['_e'] = $e;
}

$processorModel->log($processorId, $transactionId, $logType, $logMessage, $logDetails);

$response->setBody(htmlspecialchars($logMessage));
$response->sendResponse();