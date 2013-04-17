<?php
class bdPaygate_XenResource_Model_Resource extends XFCP_bdPaygate_XenResource_Model_Resource
{
	protected $_purchases = array();

	public function prepareResource(array $resource, array $category = null, array $viewingUser = null)
	{
		$resource = parent::prepareResource($resource, $category, $viewingUser);

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

	public function canDownloadResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$canDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser);

		if ($canDownload)
		{
			// check for purchases
			$canPurchase = $this->bdPaygate_canPurchaseResource($resource, $category, $null, $viewingUser);

			if ($canPurchase)
			{
				$errorPhraseKey = 'bdpaygate_you_must_purchase_resource_to_download';
				return false;
			}
		}

		return $canDownload;
	}

	public function bdPaygate_canPurchaseResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$parentCanDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser);

		if ($parentCanDownload)
		{
			if ($resource['user_id'] == $viewingUser['user_id'])
			{
				$errorPhraseKey = 'bdpaygate_cannot_purchase_self_resource';
				return false;
			}

			if (empty($resource['is_fileless']) AND empty($resource['download_url']))
			{
				if (!empty($resource['price']) AND !empty($resource['currency']))
				{
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
			}
		}

		return false;
	}

	protected function _bdPaygate_getPurchase($resourceId, $userId)
	{
		if (!isset($this->_purchases[$resourceId]))
		{
			$this->_purchases[$resourceId] = $this->_bdPaygate_getPurchaseModel()->getPurchaseByContentAndUser(
					'resource', $resourceId,
					$userId
			);
		}

		return $this->_purchases[$resourceId];
	}

	/**
	 * @return bdPaygate_Model_Purchase
	 */
	protected function _bdPaygate_getPurchaseModel()
	{
		return $this->getModelFromCache('bdPaygate_Model_Purchase');
	}
}