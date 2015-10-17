<?php

class bdPaygate_XenForo_Model_Thread extends XFCP_bdPaygate_XenForo_Model_Thread
{
    public function canReplyToThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
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
                } elseif (!$this->_bdPaygate_getResourceModel()->canDownloadResource($resource, $resource, $errorPhraseKey, $viewingUser)) {
                    return false;
                }
            }
        }

        return $canReplyToThread;
    }

    protected function _bdPaygate_getResourceByThreadId($threadId)
    {
        if (bdPaygate_Listener::getXfrmVersionId() == 0) {
            return array();
        }

        static $resources = array();

        if (!isset($resources[$threadId])) {
            $resource = $this->_bdPaygate_getResourceModel()->getResourceByDiscussionId($threadId, array(
                'join' => XenResource_Model_Resource::FETCH_CATEGORY));

            $resources[$threadId] = $resource;
        }

        return $resources[$threadId];
    }

    /**
     * @return bdPaygate_XenResource_Model_Resource
     */
    protected function _bdPaygate_getResourceModel()
    {
        return $this->getModelFromCache('XenResource_Model_Resource');
    }

}
