<?php

namespace core;

class StoreController extends AdminController
{
	protected $user_tag = 'store';
	protected $store_id;
	public function  before()
	{ 
		if (!$this->getStoreInfo()) {  
			json_error(['msg' => lang('店铺不存在')]); 
		} 
		if ($this->isSupper()) {
			return true;
		}
		parent::before();
	}

	public function  checkPermissions()
	{
		if ($this->isSupper()) {
			return true;
		}
		parent::checkPermissions();
	}

	protected function isSupper()
	{
		if ($this->user_info['is_supper']) {
			add_action("has_access", function (&$data) {
				$data['flag'] = true;
			});
			return true;
		}
	}
}
