<?php

class bdPaygate_XenForo_Model_Thread extends XFCP_bdPaygate_XenForo_Model_Thread
{
	public function canViewThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$canView = parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);

		if ($canView AND !empty($thread['discussion_type']) AND $thread['discussion_type'] === 'resource')
		{
			// check for resource permission
			$resource = $this->_bdPaygate_getResourceByThreadId($thread['thread_id']);

			if (!empty($resource) AND $this->_bdPaygate_getResourceModel()->bdPaygate_mustPurchaseToDownload($resource, $viewingUser))
			{
				// this is a paid resource, check for discussion permission
				$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

				if (XenForo_Permission::hasContentPermission($nodePermissions, 'bdPaygate_allThreads'))
				{
					return true;
				}
				elseif (!$this->_bdPaygate_getResourceModel()->canDownloadResource($resource, $resource, $null, $viewingUser))
				{
					return false;
				}
			}
		}

		return $canView;
	}

	protected function _bdPaygate_getResourceByThreadId($threadId)
	{
		static $resources = array();

		if (!isset($resources[$threadId]))
		{
			$resource = $this->_bdPaygate_getResourceModel()->getResourceByDiscussionId($threadId, array('join' => XenResource_Model_Resource::FETCH_CATEGORY));

			$resources[$threadId] = $resource;
		}

		return $resources[$threadId];
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _bdPaygate_getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}

}
