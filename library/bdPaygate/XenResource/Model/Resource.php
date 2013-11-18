<?php

class bdPaygate_XenResource_Model_Resource_Base extends XFCP_bdPaygate_XenResource_Model_Resource
{
	protected $_purchases = array();

	public function prepareResource(array $resource, array $category = null, array $viewingUser = null)
	{
		$resource = parent::prepareResource($resource, $category, $viewingUser);

		$resource['mustPurchaseToDownload'] = $this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser);

		if ($resource['mustPurchaseToDownload'] AND $resource['currency'])
		{
			$currencies = $this->getModelFromCache('bdPaygate_Model_Processor')->getCurrencies();
			if (!empty($currencies[$resource['currency']]))
			{
				$resource['cost'] = XenForo_Locale::numberFormat($resource['price'], 2) . ' ' . $currencies[$resource['currency']];
			}
		}

		if ($category)
		{
			$resource['canPurchase'] = $this->bdPaygate_canPurchaseResource($resource, $category, $null, $viewingUser);
		}
		else
		{
			$resource['canPurchase'] = false;
		}

		return $resource;
	}

	public function bdPaygate_mustPurchaseToDownload(array $resource, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($resource['is_fileless']) AND empty($resource['download_url']))
		{
			if ($resource['user_id'] != $viewingUser['user_id'])
			{
				if (!empty($resource['price']) AND !empty($resource['currency']))
				{
					return true;
				}
			}
		}

		return false;
	}

	public function bdPaygate_canPurchaseResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']))
		{
			return false;
		}

		$parentCanDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser);

		if ($parentCanDownload AND $this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser))
		{
			if ($resource['user_id'] == $viewingUser['user_id'])
			{
				// this may never happen
				$errorPhraseKey = 'bdpaygate_cannot_purchase_self_resource';
				return false;
			}

			$purchase = $this->_bdPaygate_getPurchase($resource['resource_id'], $viewingUser['user_id']);

			if (!empty($purchase))
			{
				$errorPhraseKey = 'bdpaygate_cannot_repurchase_resource';
				return false;
			}
			else
			{
				return true;
			}
		}

		return false;
	}

	protected function _bdPaygate_canDownloadResource($canDownload, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		if ($canDownload)
		{
			if ($this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser))
			{
				$purchase = $this->_bdPaygate_getPurchase($resource['resource_id'], $viewingUser['user_id']);

				if (empty($purchase))
				{
					$errorPhraseKey = 'bdpaygate_you_must_purchase_resource_to_download';
					return false;
				}
			}
		}

		return $canDownload;
	}

	protected function _bdPaygate_getPurchase($resourceId, $userId)
	{
		$hash = sprintf('%d_%d', $resourceId, $userId);

		if (!isset($this->_purchases[$hash]))
		{
			$this->_purchases[$hash] = $this->_bdPaygate_getPurchaseModel()->getPurchaseByContentAndUser('resource', $resourceId, $userId);
		}

		return $this->_purchases[$hash];
	}

	/**
	 * @return bdPaygate_Model_Purchase
	 */
	protected function _bdPaygate_getPurchaseModel()
	{
		return $this->getModelFromCache('bdPaygate_Model_Purchase');
	}

}

if (bdPaygate_Listener::getXfrmVersionId() > 1010000)
{
	// XFRM 1.1
	class bdPaygate_XenResource_Model_Resource extends bdPaygate_XenResource_Model_Resource_Base
	{
		public function canDownloadResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
		{
			$canDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser, $categoryPermissions);

			return $this->_bdPaygate_canDownloadResource($canDownload, $resource, $category, $errorPhraseKey, $viewingUser);
		}

	}

}
else
{
	// XFRM 1.0
	class bdPaygate_XenResource_Model_Resource extends bdPaygate_XenResource_Model_Resource_Base
	{
		public function canDownloadResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null)
		{
			$canDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser);

			return $this->_bdPaygate_canDownloadResource($canDownload, $resource, $category, $errorPhraseKey, $viewingUser);
		}

	}

}
