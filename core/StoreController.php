<?php

namespace core;

class StoreController extends AdminController
{
	protected $user_tag = 'store';
	protected $store_id;
	public function  before()
	{
		$this->store_id = db_get_one("store", "id", ['user_id' => $this->user_id]);
		if (!$this->store_id) { 
			$sys_tag = $this->user_info['sys_tag'];
			$store_id = substr($sys_tag, strpos($sys_tag, '.') + 1);
			$this->store_id = $store_id;
			if(!$this->store_id){
				json_error(['msg' => lang('店铺不存在')]);
			} 
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
