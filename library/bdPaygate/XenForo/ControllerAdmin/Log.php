<?php

class bdPaygate_XenForo_ControllerAdmin_Log extends XFCP_bdPaygate_XenForo_ControllerAdmin_Log
{
	public function actionBdpaygate()
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);
		$logModel = $this->getModelFromCache('bdPaygate_Model_Log');
		
		if ($id)
		{
			$entry = $logModel->getLogById($id);
			if (!$entry)
			{
				return $this->responseError(new XenForo_Phrase('bdpaygate_requested_log_entry_not_found'), 404);
			}

			$viewParams = array(
				'entry' => $entry
			);
			return $this->responseView('bdPaygate_ViewAdmin_Log_View', 'bdpaygate_log_view', $viewParams);
		}
		else
		{
			$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
			$perPage = 20;
			
			$entries = $logModel->getLogs(array(), array(
				'page' => $page,
				'perPage' => $perPage,
		
				'order' => 'log_id',
				'direction' => 'desc',
			));
			$total = $logModel->countLogs();

			$viewParams = array(
				'entries' => $entries,

				'page' => $page,
				'perPage' => $perPage,
				'total' => $total,
			);
			return $this->responseView('bdPaygate_ViewAdmin_Log_List', 'bdpaygate_log_list', $viewParams);
		}
	}

	public function actionBdpaygateClear()
	{
		$logModel = $this->getModelFromCache('bdPaygate_Model_Log');
		
		if ($this->isConfirmedPost())
		{
			$logModel->clearLog();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('logs/bdpaygate')
			);
		}
		else
		{
			$viewParams = array();
			return $this->responseView('bdPaygate_ViewAdmin_Log_Clear', 'bdpaygate_log_clear', $viewParams);
		}
	}
}