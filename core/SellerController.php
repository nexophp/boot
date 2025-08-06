<?php

namespace core;

class SellerController extends AdminController
{
	protected $user_tag = 'seller';

	public function  before()
	{
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
			return true;
		}
	}
}
