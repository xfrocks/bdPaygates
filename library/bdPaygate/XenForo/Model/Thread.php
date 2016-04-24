<?php

class bdPaygate_XenForo_Model_Thread extends XFCP_bdPaygate_XenForo_Model_Thread
{
    public function canReplyToThread(
        array $thread,
        array $forum,
        &$errorPhraseKey = '',
        array $nodePermissions = null,
        array $viewingUser = null
    ) {
        $canReplyToThread = parent::canReplyToThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);

        if ($canReplyToThread
            && !empty($thread['discussion_type'])
            && $thread['discussion_type'] === 'resource'
        ) {
            // check for resource permission
            $resource = $this->_bdPaygate_getResourceByThreadId($thread['thread_id']);

            if (!empty($resource)
                && $this->_bdPaygate_getResourceModel()->bdPaygate_mustPurchaseToDownload($resource, $viewingUser)
            ) {
                // this is a paid resource, check for discussion permission
                $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

                if (XenForo_Permission::hasContentPermission($nodePermissions, 'bdPaygate_allThreads')) {
                    return true;
                } elseif (!$this->_bdPaygate_getResourceModel()->canDownloadResource($resource, $resource,
                    $errorPhraseKey, $viewingUser)
                ) {
                    return false;
                }
            }
        }

        return $canReplyToThread;
    }

    protected function _bdPaygate_getResourceByThreadId($threadId)
    {
        static $resources = array();

        if (!isset($resources[$threadId])) {
            $resource = null;

            $resourceModel = $this->_bdPaygate_getResourceModel();
            if ($resourceModel !== null) {
                $resource = $resourceModel->getResourceByDiscussionId($threadId, array(
                    'join' => XenResource_Model_Resource::FETCH_CATEGORY
                ));
            }

            $resources[$threadId] = $resource;
        }

        return $resources[$threadId];
    }

    /**
     * @return bdPaygate_XenResource_Model_Resource
     */
    protected function _bdPaygate_getResourceModel()
    {
        static $resourceModel = false;

        if ($resourceModel === false) {
            $resourceModel = null;

            $addOns = XenForo_Application::get('addOns');
            if (!empty($addOns['XenResource'])) {
                $resourceModel = $this->getModelFromCache('XenResource_Model_Resource');
            }
        }

        return $resourceModel;
    }

}
