<?php

class bdPaygate_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerAdmin_Log',
			'XenForo_ControllerAdmin_UserUpgrade',
			'XenForo_ControllerPublic_Account',
			'XenForo_DataWriter_User',
			'XenForo_DataWriter_UserUpgrade',
			'XenForo_Model_Option',
			'XenForo_Model_UserUpgrade',
			'XenForo_ViewPublic_Account_Upgrades',

			'XenResource_ControllerPublic_Resource',
			'XenResource_DataWriter_Resource',
			'XenResource_Model_Resource',
		);

		if (in_array($class, $classes))
		{
			$extend[] = 'bdPaygate_' . $class;
		}
	}

	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'resource_add':
				$template->preloadTemplate('bdpaygate_resource_add');
				$template->preloadTemplate('bdpaygate_resource_edit');
				break;
			case 'resources_tab_links':
				$template->preloadTemplate('bdpaygate_resources_tab_links');
				break;
			case 'resource_view':
				$template->preloadTemplate('bdpaygate_resource_view_header');
				$template->preloadTemplate('bdpaygate_resource_view_tabs');
				break;
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'resource_view_header_after_resource_buttons':
				if (XenForo_Application::$versionId < 1020000)
				{
					$ourTemplate = $template->create('bdpaygate_resource_view_header', $template->getParams());
					$contents .= $ourTemplate;
				}
				break;
			case 'resource_view_tabs':
				$ourTemplate = $template->create('bdpaygate_resource_view_tabs', $template->getParams());
				$contents .= $ourTemplate;
				break;
		}
	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'resource_add':
				$params = $template->getParams();

				if (empty($params['resource']['resource_id']))
				{
					// adding a new resource
					$ourTemplate = $template->create('bdpaygate_resource_add', $params);
					$search = '<ul id="ctrl_resource_file_type_file_Disabler">';
					$search2 = '</ul>';
					$strPos = strpos($content, $search);
					if ($strPos !== false)
					{
						$strPos2 = strpos($content, $search2, $strPos);
						if ($strPos2 !== false)
						{
							$content = substr_replace($content, $ourTemplate, $strPos2, 0);
						}
					}
				}
				else
				{
					// editing an existing resource
					$ourTemplate = $template->create('bdpaygate_resource_edit', $params);
					$search = '</fieldset>';
					$strPos = strpos($content, $search);
					if ($strPos !== false)
					{
						$content = substr_replace($content, $ourTemplate, $strPos + strlen($search), 0);
					}
				}
				break;
			case 'resources_tab_links':
				$ourTemplate = $template->create('bdpaygate_resources_tab_links', $template->getParams());
				$search = '</ul>';
				$strPos = strpos($content, $search);
				if ($search !== false)
				{
					$content = substr_replace($content, $ourTemplate, $strPos, 0);
				}
				break;
		}
	}

	public static function bdshop_stock_pricing_get_systems(array &$systems)
	{
		$systems[] = 'bdPaygate_bdShop_StockPricing';
	}

	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdPaygate_FileSums::getHashes();
	}

	public static function getXfrmVersionId()
	{
		if (XenForo_Application::$versionId > 1020000)
		{
			$addOns = XenForo_Application::get('addOns');
			if (isset($addOns['XenResource']))
			{
				return $addOns['XenResource'];
			}
		}
		else
		{
			// XenForo is 1.1 or earlier, XFRM cannot reach 1.1
			return 1009900;
		}

		return 0;
	}

}
