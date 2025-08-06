<?php

namespace core;

class StoreController extends SellerController
{
	protected $user_tag = 'store';
	protected $store_id;
	public function  before()
	{
		parent::before();
		$this->store_id = db_get_one("store", "id", ['user_id' => $this->user_id]);
		if (!$this->store_id) {
			json_error(['msg' => lang('商家不存在')]);
		}
	}
}
