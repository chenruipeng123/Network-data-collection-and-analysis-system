<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\SuperLogs;
use app\index\model\Logs;
use app\index\model\Urls;
use app\index\model\Rules;
use app\index\model\Data;

class Log extends Supers
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index()
	{
		return view();
	}

	public function indexData()
	{
		$username = session('super_name');
		$data = SuperLogs::where("username='{$username}'")->order("created_at desc")->select();
		echo json_encode($data);
	}

	public function grab()
	{
		return view();
	}

	public function grabData()
	{
		$data = Logs::order("grab_at desc")->select();
		foreach ($data as $key => $value) {
			$data2 = Urls::where('id',$value['url_id'])->find();
			$data[$key]['url_name'] = $data2['name'];
		}

        foreach ($data as $key => $value) {
			$data3 = Rules::where('id',$value['rules_id'])->find();
			// dump($data3);
            $data[$key]['rules_name'] = $data3['name'];
		}

        foreach ($data as $key => $value) {
            $data4 = Data::where('id',$value['data_id'])->find();
            $data[$key]['data_title'] = $data4['title'];
		}
		echo json_encode($data);
	}
}
