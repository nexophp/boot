<?php

namespace core;

class SellerController extends AdminController
{
	protected $user_tag = 'seller';
	protected $seller_id;
	public function  before()
	{
		global $admin_type;
		$admin_type = 'seller';
		$this->seller_id = db_get_one("seller","id",['user_id'=>$this->user_id]);
		if (!$this->seller_id) {
			json_error(['msg' => lang('商家不存在')]);
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
