<?php

class bdPaygate_XenResource_Model_Resource extends XFCP_bdPaygate_XenResource_Model_Resource
{
    protected $_purchases = array();

    public function prepareResource(array $resource, array $category = null, array $viewingUser = null)
    {
        $resource = parent::prepareResource($resource, $category, $viewingUser);

        $resource['mustPurchaseToDownload'] = $this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser);

        if ($resource['mustPurchaseToDownload']
            && $resource['currency']
        ) {
            /** @var bdPaygate_Model_Processor $processorModel */
            $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
            $cost = $processorModel->formatCost($resource['price'], $resource['currency']);
            if ($cost !== '') {
                $resource['cost'] = $cost;
            }
        }

        if ($category) {
            $resource['canPurchase'] = $this->bdPaygate_canPurchaseResource($resource, $category, $null, $viewingUser);
        } else {
            $resource['canPurchase'] = false;
        }

        return $resource;
    }

    public function canDownloadResource(
        array $resource,
        array $category,
        &$errorPhraseKey = '',
        array $viewingUser = null,
        array $categoryPermissions = null
    ) {
        $canDownload = parent::canDownloadResource($resource, $category,
            $errorPhraseKey, $viewingUser, $categoryPermissions);

        if ($canDownload) {
            if ($this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser)) {
                $purchase = $this->_bdPaygate_getPurchase($resource['resource_id'], $viewingUser['user_id']);

                if (empty($purchase)) {
                    $errorPhraseKey = 'bdpaygate_you_must_purchase_resource_to_download';
                    return false;
                }
            }
        }

        return $canDownload;
    }

    public function bdPaygate_mustPurchaseToDownload(array $resource, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!empty($viewingUser['permissions'])
            && XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdPaygate_allPurchases')
        ) {
            // user has permission to access all purchases
            return false;
        }

        if (bdPaygate_Helper_Resource::isPaid($resource)
            && $resource['user_id'] != $viewingUser['user_id']
        ) {
            return true;
        }

        return false;
    }

    public function bdPaygate_canPurchaseResource(
        array $resource,
        array $category,
        &$errorPhraseKey = '',
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id'])) {
            return false;
        }

        $parentCanDownload = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser);

        if ($parentCanDownload
            && $this->bdPaygate_mustPurchaseToDownload($resource, $viewingUser)
        ) {
            if ($resource['user_id'] == $viewingUser['user_id']) {
                // this may never happen
                $errorPhraseKey = 'bdpaygate_cannot_purchase_self_resource';
                return false;
            }

            $purchase = $this->_bdPaygate_getPurchase($resource['resource_id'], $viewingUser['user_id']);

            if (!empty($purchase)) {
                $errorPhraseKey = 'bdpaygate_cannot_repurchase_resource';
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    protected function _bdPaygate_getPurchase($resourceId, $userId)
    {
        if (empty($resourceId) OR empty($userId)) {
            return false;
        }

        $hash = sprintf('%d_%d', $resourceId, $userId);

        if (!isset($this->_purchases[$hash])) {
            $this->_purchases[$hash]
                = $this->_bdPaygate_getPurchaseModel()->getPurchaseByContentAndUser('resource', $resourceId, $userId);
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
