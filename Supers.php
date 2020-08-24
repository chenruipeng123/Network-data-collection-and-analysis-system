<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Supers extends Controller
{
	public function __construct()
	{
		parent::__construct();
		if (session('?super_name') != 1 || session('?super_id') != 1) {
			// echo 11;exit;
			$this->redirect('index/login/index');
		}
	}

}
