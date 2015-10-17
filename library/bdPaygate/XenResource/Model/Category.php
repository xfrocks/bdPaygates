<?php

class bdPaygate_XenResource_Model_Category extends XFCP_bdPaygate_XenResource_Model_Category
{

    public function canAddResource(array $category = null, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
    {
        $canAddResource = parent::canAddResource($category, $errorPhraseKey, $viewingUser, $categoryPermissions);

        if ($category AND !$canAddResource) {
            if (!empty($category['bdpaygate_allow_commercial_local'])) {
                $this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

                if (XenForo_Permission::hasContentPermission($categoryPermissions, 'add')) {
                    $canAddResource = true;
                }
            }
        }

        return $canAddResource;
    }

    public function prepareCategory(array $category)
    {
        $category = parent::prepareCategory($category);

        if (!empty($category['bdpaygate_allow_commercial_local'])) {
            $category['allowResource'] = true;
        }

        return $category;
    }

}
