<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\SuperLogs;
use app\index\model\Logs;

class Truncate
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */

	public function tgrab()
	{
		Logs::execute("truncate table logs");
	}

	public function tsuper()
	{
		Superlogs::execute("truncate table super_logs");
	}

}
