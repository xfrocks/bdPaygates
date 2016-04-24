<?php

class bdPaygate_XenResource_ControllerPublic_Resource extends XFCP_bdPaygate_XenResource_ControllerPublic_Resource
{
    public function actionBuyers()
    {
        /** @var bdPaygate_XenResource_Model_Resource $resourceModel */
        $resourceModel = $this->_getResourceModel();
        /** @var bdPaygate_Model_Purchase $purchaseModel */
        $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');

        list($resource, $category) = $this->_getResourceViewInfo();

        if (!bdPaygate_Helper_Resource::isPaid($resource)
            && !$resourceModel->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('resources/buyers', $resource));

        $conditions = array(
            'content_type' => 'resource',
            'content_id' => $resource['resource_id'],
        );
        $purchaseId = $this->_input->filterSingle('purchase_id', XenForo_Input::UINT);
        if ($purchaseId > 0) {
            $conditions['purchase_id'] = $purchaseId;
        }

        $fetchOptions = array(
            'join' => bdPaygate_Model_Purchase::FETCH_USER,
            'order' => 'purchase_date',
            'direction' => 'desc',
        );
        $fetchOptions['page'] = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $fetchOptions['limit'] = 50;

        $buyers = $purchaseModel->getPurchases($conditions, $fetchOptions);
        $total = $purchaseModel->countPurchases($conditions, $fetchOptions);

        $viewParams = array(
            'resource' => $this->_getResourceModel()->prepareResource($resource),
            'category' => $category,

            'buyers' => $buyers,
            'total' => $total,
            'page' => $fetchOptions['page'],
            'limit' => $fetchOptions['limit'],
        );

        return $this->_getResourceViewWrapper('buyers', $resource, $category,
            $this->responseView(
                'bdPaygate_ViewPublic_Resource_Buyers',
                'bdpaygate_resource_buyers',
                $viewParams
            )
        );
    }

    public function actionBuyersAdd()
    {
        list($resource, $category) = $this->_getResourceViewInfo();

        /** @var bdPaygate_XenResource_Model_Resource $resourceModel */
        $resourceModel = $this->_getResourceModel();
        if (!bdPaygate_Helper_Resource::isPaid($resource)
            && !$resourceModel->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('resources/buyers/add', $resource));

        if ($this->isConfirmedPost()) {
            $usernames = $this->_input->filterSingle('usernames', XenForo_Input::STRING);
            $usernames = explode(',', $usernames);

            /** @var bdPaygate_Model_Purchase $purchaseModel */
            $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');
            /** @var XenForo_Model_User $userModel */
            $userModel = $this->getModelFromCache('XenForo_Model_User');

            $users = $userModel->getUsersByNames($usernames);

            if (empty($users)) {
                throw new XenForo_Exception(new XenForo_Phrase('requested_user_not_found'), true);
            }

            foreach ($users as $user) {
                $purchased = $purchaseModel->getPurchaseByContentAndUser('resource', $resource['resource_id'], $user['user_id']);

                if (empty($purchased)) {
                    $purchaseModel->createRecord('resource', $resource['resource_id'], $user['user_id']);
                }
            }

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildPublicLink('resources/buyers', $resource)
            );
        } else {
            $viewParams = array(
                'resource' => $this->_getResourceModel()->prepareResource($resource),
                'category' => $category,
            );

            return $this->_getResourceViewWrapper('buyers', $resource, $category,
                $this->responseView(
                    'bdPaygate_ViewPublic_Resource_AddBuyer',
                    'bdpaygate_resource_add_buyer',
                    $viewParams
                )
            );
        }
    }

    public function actionBuyersDelete()
    {
        list($resource, $category) = $this->_getResourceViewInfo();

        /** @var bdPaygate_XenResource_Model_Resource $resourceModel */
        $resourceModel = $this->_getResourceModel();
        if (!bdPaygate_Helper_Resource::isPaid($resource)
            && !$resourceModel->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('resources/buyers/delete', $resource));

        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        /** @var XenForo_Model_User $userModel */
        $userModel = $this->getModelFromCache('XenForo_Model_User');
        $user = $userModel->getUserById($userId);
        if (empty($user)) {
            return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
        }

        if ($this->isConfirmedPost()) {
            /** @var bdPaygate_Model_Purchase $purchaseModel */
            $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');
            $purchaseModel->deleteRecords('resource', $resource['resource_id'], $user['user_id']);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildPublicLink('resources/buyers', $resource)
            );
        } else {
            $viewParams = array(
                'resource' => $this->_getResourceModel()->prepareResource($resource),
                'category' => $category,
                'user' => $user,
            );

            return $this->_getResourceViewWrapper('buyers', $resource, $category,
                $this->responseView('bdPaygate_ViewPublic_Resource_DeleteBuyer',
                    'bdpaygate_resource_delete_buyer', $viewParams));
        }
    }

    public function actionBuyersFind()
    {
        list($resource, $category) = $this->_getResourceViewInfo();

        /** @var bdPaygate_XenResource_Model_Resource $resourceModel */
        $resourceModel = $this->_getResourceModel();
        if (!bdPaygate_Helper_Resource::isPaid($resource)
            && !$resourceModel->canEditResource($resource, $category)) {
            return $this->responseNoPermission();
        }

        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('resources/buyers/find', $resource));

        if ($this->isConfirmedPost()) {
            $username = $this->_input->filterSingle('username', XenForo_Input::STRING);

            /** @var bdPaygate_Model_Purchase $purchaseModel */
            $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');
            /** @var XenForo_Model_User $userModel */
            $userModel = $this->getModelFromCache('XenForo_Model_User');

            $user = $userModel->getUserByName($username);
            if (empty($user)) {
                return $this->responseError(new XenForo_Phrase('requested_user_not_found'), 404);
            }

            $purchase = $purchaseModel->getPurchaseByContentAndUser(
                'resource', $resource['resource_id'], $user['user_id']);
            if (empty($purchase)) {
                return $this->responseMessage(new XenForo_Phrase('bdpaygate_buyer_not_found'));
            }

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('resources/buyers', $resource,
                    array('purchase_id' => $purchase['purchase_id'])),
                new XenForo_Phrase('bdpaygate_buyer_found')
            );
        } else {
            $viewParams = array(
                'resource' => $this->_getResourceModel()->prepareResource($resource),
                'category' => $category,
            );

            return $this->_getResourceViewWrapper('buyers', $resource, $category,
                $this->responseView(
                    'bdPaygate_ViewPublic_Resource_FindBuyer',
                    'bdpaygate_resource_find_buyer',
                    $viewParams
                )
            );
        }
    }

    public function actionPurchase()
    {
        list($resource, $category) = $this->_getResourceViewInfo();

        /** @var bdPaygate_XenResource_Model_Resource $resourceModel */
        $resourceModel = $this->_getResourceModel();
        if (!$resourceModel->bdPaygate_mustPurchaseToDownload($resource)) {
            return $this->responseNoPermission();
        }
        if (!$resourceModel->bdPaygate_canPurchaseResource($resource, $category, $errorPhraseKey)) {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        /* @var $processorModel bdPaygate_Model_Processor */
        $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');

        $processorNames = $processorModel->getProcessorNames();
        $processors = array();
        foreach ($processorNames as $processorId => $processorClass) {
            $processors[$processorId] = bdPaygate_Processor_Abstract::create($processorClass);
        }

        $viewParams = array(
            'resource' => $this->_getResourceModel()->prepareResource($resource),
            'category' => $category,

            'processors' => $processors,
        );

        return $this->responseView('bdPaygate_ViewPublic_Resource_Purchase', 'bdpaygate_resource_purchase', $viewParams);
    }

    public function actionPurchaseComplete()
    {
        return $this->responseMessage(new XenForo_Phrase('bdpaygate_purchase_resource_complete', array(
            'purchased_link' => XenForo_Link::buildPublicLink('resources/purchased'))));
    }

    public function actionPurchased()
    {
        /* @var $purchaseModel bdPaygate_Model_Purchase */
        $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');

        $visitor = XenForo_Visitor::getInstance();

        $purchases = $purchaseModel->getPurchases(array(
            'content_type' => 'resource',
            'user_id' => $visitor['user_id'],
        ));

        if (empty($purchases)) {
            return $this->responseMessage(new XenForo_Phrase('bdpaygate_you_have_not_purchased_resources'));
        }

        $resourceIds = array();
        foreach ($purchases as $purchase) {
            $resourceIds[] = $purchase['content_id'];
        }
        $resources = $this->_getResourceModel()->getResourcesByIds($resourceIds);

        $viewParams = array('resources' => $this->_getResourceModel()->prepareResources($resources),);

        return $this->responseView(
            'bdPaygate_ViewPublic_Resource_Purchased',
            'bdpaygate_resource_purchased',
            $viewParams
        );
    }

    public function actionSave()
    {
        $GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE] = $this;

        return parent::actionSave();
    }

    public function bdPaygate_actionSave(XenResource_DataWriter_Resource $dw)
    {
        $input = $this->_input->filter(array(
            'bdpaygate_price' => XenForo_Input::UNUM,
            'bdpaygate_currency' => XenForo_Input::STRING,
            'file_hash' => XenForo_Input::STRING,
        ));

        if (!empty($input['file_hash'])) {
            $dw->getVersionDw()->setExtraData(XenResource_DataWriter_Version::DATA_ATTACHMENT_HASH, $input['file_hash']);
        }

        if (!empty($input['bdpaygate_price']) OR !empty($input['bdpaygate_currency'])) {
            $dw->set('price', $input['bdpaygate_price']);
            $dw->set('currency', $input['bdpaygate_currency']);
        }

        unset($GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE]);
    }

    protected function _checkCsrf($action)
    {
        if (strtolower($action) == 'purchasecomplete') {
            // may be coming from external payment gateway
            return;
        }

        parent::_checkCsrf($action);
    }

    protected function _getResourceAddOrEditResponse(array $resource, array $category, array $attachments = array())
    {
        $response = parent::_getResourceAddOrEditResponse($resource, $category, $attachments);

        if ($response instanceof XenForo_ControllerResponse_View) {
            $params = &$response->params;

            if (!empty($params['category']['bdpaygate_allow_commercial_local'])) {
                $params['allowLocal'] = true;

                if (empty($params['resource']['resource_id'])) {
                    $params['resourceType'] = 'local';
                }
            }

            /** @var bdPaygate_Model_Processor $processorModel */
            $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
            $params['bdPaygate_currencies'] = $processorModel->getEnabledCurrencies();
        }

        return $response;
    }

}
