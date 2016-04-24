<?php

class bdPaygate_XenResource_DataWriter_Resource extends XFCP_bdPaygate_XenResource_DataWriter_Resource
{
    protected function _preSave()
    {
        if (isset($GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE])) {
            /** @var bdPaygate_XenResource_ControllerPublic_Resource $controller */
            $controller = $GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE];
            $controller->bdPaygate_actionSave($this);
        }

        parent::_preSave();

        $errors = $this->getErrors();
        if (!empty($errors['currency'])
            && $errors['currency'] instanceof XenForo_Phrase
        ) {
            /** @var XenForo_Phrase $errorCurrency */
            $errorCurrency = $errors['currency'];
            if ($errorCurrency->getPhraseName() === 'please_complete_all_commercial_resource_related_fields') {
                // this error happened because we set `price` and `currency` but didn't set
                // `external_purchase_url`, unset it
                unset($this->_errors['currency']);
            }
        }

        // make sure price and currency are always together
        $commercialParts = (floatval($this->get('price')) ? 1 : 0) + ($this->get('currency') ? 1 : 0);
        if ($commercialParts == 1) {
            $this->error(new XenForo_Phrase('please_complete_all_commercial_resource_related_fields'), 'currency');
        }

        if (!empty($errors['resource_category_id'])
            && $errors['resource_category_id'] instanceof XenForo_Phrase
        ) {
            /** @var XenForo_Phrase $errorCategory */
            $errorCategory = $errors['resource_category_id'];
            if ($errorCategory->getPhraseName() === 'category_not_allow_new_resources') {
                $category = $this->_getCategoryModel()->getCategoryById($this->get('resource_category_id'));

                if (!$this->get('is_fileless')
                    && !empty($category['bdpaygate_allow_commercial_local'])
                ) {
                    // the error checker found out that the category doesn't accept local file
                    // but we set a local file somewhere, here we verified that the category allows
                    // commercial local file so we unset the error
                    unset($this->_errors['resource_category_id']);
                }
            }
        }

        if (!$this->getErrors()
            && (
                $this->isChanged('resource_category_id')
                || $this->isChanged('price')
                || $this->isChanged('currency')
            )
        ) {
            $category = $this->_getCategoryModel()->getCategoryById($this->get('resource_category_id'));

            if (!$this->get('is_fileless')
                && !empty($category['bdpaygate_allow_commercial_local'])
                && empty($category['allow_local'])
            ) {
                if ($commercialParts < 2) {
                    // normal local is not allowed, only commercial local
                    // that means price and currency are required
                    $this->error(new XenForo_Phrase('please_complete_all_commercial_resource_related_fields'),
                        'currency');
                }
            }
        }
    }

}
