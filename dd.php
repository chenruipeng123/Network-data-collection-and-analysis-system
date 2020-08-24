<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Urls;
use app\index\model\Rules;
use app\index\model\Category;
use app\index\model\Data;
use app\index\model\Logs;
use app\index\model\StatisticsDay;
use app\index\model\StatisticsMonth;
use app\index\model\Test;

/* 
	url地址页面
*/

class Url extends Supers

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

	public function data()
	{

		$data = Urls::order('show_order asc')->select();
		foreach ($data as $key => $value) {

			$r_data = Rules::where('id', $value["rules_id"])->find();
			$data[$key]['rules_name'] = $r_data['name'];
			$data[$key]['rules_level'] = $r_data['level'];
			$data[$key]['rules_l1'] = $r_data['l1_rule'];
			$data[$key]['rules_l2_title'] = $r_data['l2_title'];
			$data[$key]['rules_l2_time'] = $r_data['l2_time'];
			$data[$key]['rules_l2_cont'] = $r_data['l2_cont'];
			// dump($r_data);
		}

		echo json_encode($data);
	}
	/**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
	public function add()
	{
		if (input('post.add') == 'add') {
			$data = request()->except('add');
			$data = nd($data);
			$res = Urls::insert($data);
			if ($res) {
				$this->assign([
					'message' => '添加成功',
					'waitSecond' => 2,
				]);
			} else {
				$this->assign([
					'error' => '添加失败',
				]);
			}
		}
		$list = Rules::where('show_tag', '1')->order('show_order asc')->select();
		$res_num = Urls::max("show_order");
		$this->assign([
			"list" => $list,
			"res_num" => $res_num,
		]);
		return view();
	}


	public function edit($id)
	{
		$data = Urls::where('id', $id)->find();
		$list = Rules::where('show_tag', '1')->order('show_order asc')->select();
		if (input("post.add") == 'add') {
			$res = request()->except('id,add');
			$res['updated_at'] = date("Y-m-d,H:i:s");
			$rst = Urls::where('id', $id)->update($res);
			if ($rst) {
				$this->assign([
					"message" => "修改成功！",
					"waitSecond" => 2,
				]);
			} else {
				$this->assign("errors", "修改失败！");
			}
		}
		$this->assign([
			"data" => $data,
			"list" => $list,
			"id" => $id,
		]);
		return view();
	}

	/**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
	public function delete($id)
	{
		$res = Urls::where('id', $id)->delete();
		if ($res) {
			$this->assign([
				"message" => "删除成功！",
				"waitSecond" => 2,
			]);
		} else {
			$this->assign([
				"errors" => "删除失败！",
			]);
		}
		return view('index');
	}

	/* 手动数据抓取 */
	public function grab($id)
	{
		// 初始dom类
		$dom = new \simple_html_dom();
		$keywords = Category::field('keywords')->where('show_tag', '1')->order('show_order asc')->select();
		$data = Urls::where('id', $id)->find();
		$rules = Rules::where('id', $data['rules_id'])->find();
		$url_addr = $data['addr'];
		$url_name = $data['name'];
		$page = $data['page'];
		$p_count = $data['p_count'];
		$rules_name = $rules['name'];
		$rules_level = $rules->getData('level');
		$l1_rule = $rules['l1_rule'];
		$l2_main = $rules['l2_main'];
		$l2_title = $rules['l2_title'];
		$l2_time = $rules['l2_time'];
		$l2_cont = $rules['l2_cont'];
		$url_s = explode('/', $url_addr);
		$n_url = $url_s[0] . "//" . $url_s[2] . "/";
		//常用：二级抓取
		switch ($rules_level) {
			case '2':
				//循环多页抓取
				for ($i = 1; $i < $p_count; $i++) {
					//替换字符串为变量，实现递增
					$page_1 = str_replace('$i', "$i", $page);
					// 拼接成新url
					$f_url = $url_addr . $page_1;
					$html1 = file_get_html($f_url);
					echo $html1;
					exit;
					foreach ($html1->find($l2_main) as $k => $main) {
						//抓取发布时间
						foreach ($main->find($l2_time) as $key => $time) {
							// 特殊字符过滤以及各式统一转换
							$time = $time->plaintext;
							$time = replaceSpecialChar($time);
							if (strlen($time) != 8) {
								break;
							}
							$y = substr($time, 0, 4);
							$m = substr($time, 4, 2);
							$d = substr($time, 6, 2);
							$date[$key] = $y . "-" . $m . "-" . $d;
						}
						//抓取a标签，再抓取内容
						foreach ($main->find('a') as $kk => $a) {
							$href = $a->href;
							if (!strstr($href, 'http')) {
								$href = $n_url . $href;
							}
							$array1 = @get_headers($href, 1);
							if (preg_match('/200/', $array1[0])) {
								$container = file_get_html($href);
								//抓取标题
								foreach ($container->find($l2_title) as $title) {
									$title = $title->plaintext;
								}
								//抓取内容
								foreach ($container->find($l2_cont) as $content) {
									$content = $content->innertext;
									foreach ($keywords as $preg) {
										$key = explode(',', $preg['keywords']);
										foreach ($key as $val) {
											if (strstr($content, $val)) {
												$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
												$ins['c_id'] = $info['id'];
												$ins['url_id'] = $id;
												$ins['url_addr'] = $href;
												$ins['rules_id'] = $data['rules_id'];
												$ins['title'] = $title;
												$ins['release_time'] = $date[$kk];
												$ins['cont'] = $content;
												$ins = nd($ins);
												// dump($ins);
												// 重复数据不执行插入
												$rows = Data::where('title', $title)->count();
												if ($rows < 1) {
													//插入数据表
													$res = Data::insert($ins);
													if ($res) {
														$this->assign([
															"message" => "抓取成功！",
															"waitSecond" => 2,
														]);
													} else {
														$this->assign("error", "抓取失败！");
													}
													// 统计分析
													$s_counts = Data::where([
														'url_id' => $id,
														'c_id' => $info['id'],
														'release_time' => $date[$kk],
													])->count();

													// 插入统计表																						
													$s_ins['s_day'] = $date[$kk];
													$s_ins['url_id'] = $id;
													$s_ins['c_id'] = $info['id'];
													$s_ins['counts'] = $s_counts;
													$s_upd['counts'] = $s_counts;

													$data2 = StatisticsDay::where([
														'c_id' => $s_ins['c_id'],
														'url_id' => $s_ins['url_id'],
														's_day' => $s_ins['s_day'],
													])->find();
													if ($s_counts != 0) {
														if ($s_ins['c_id'] == $data2['c_id'] and $s_ins['s_day'] == $data2['s_day'] and $s_ins['url_id'] == $data2['url_id']) {
															StatisticsDay::where(
																[
																	'id' => $data2['id'],
																]
															)->update($s_upd);
														} else if ($s_ins['c_id'] != $data2['c_id'] or $s_ins['url_id'] != $data2['url_id']) {
															StatisticsDay::insert($s_ins);
														} else if (empty($data2)) {
															StatisticsDay::insert($s_ins);
														}
													} else {
														continue;
													}
												} else {
													break;
												}
											}
										}
									}
								}
							} else {
								break;
							}
						}
					}
				}


				break;
		}
		return view('index');
	}
}
// 开始统计
$d_counts = Data::where(
	[
		'url_id' => $id,
		'c_id' => $info['id'],
		'day' => date('Y-m-d'),
	]
)->count();

// 日统计
$s_day['s_day'] = date('Y-m-d');
$s_day['url_id'] = $id;
$s_day['c_id'] = $info['id'];
$s_day['counts'] = $d_counts;
$s_day['created_at'] = date('Y-m-d,H:i:s');
$s_day_upd['counts'] = $d_counts;
// $s_day_upd['id'] = 
$data2 = StatisticsDay::where([
	'c_id' => $s_day['c_id'],
	'url_id' => $s_day['url_id'],
	's_day' => date("Y-m-d"),

])->find();
if ($d_counts != 0) {
	if ($s_day['c_id'] == $data2['c_id'] and $s_day['s_day'] == $data2['s_day'] and $s_day['url_id'] == $data2['url_id']) {
		StatisticsDay::where(
			[
				'id' => $data2['id'],
			]
		)->update($s_day_upd);
	} else if ($s_day['c_id'] != $data2['c_id'] or $s_day['url_id'] != $data2['url_id']) {
		// echo 1;exit;
		StatisticsDay::insert($s_day);
	} else if (empty($data2)) {
		// echo 2;exit;
		StatisticsDay::insert($s_day);
	}
} else {
	continue;
}

$data = StatisticsMonth::where('s_month', $sel_month)->order('created_at desc')->select();
foreach ($data as $key => $value) {
	$url_name = Urls::where('id', $value['url_id'])->find();
	$c_name = Category::where('id', $value['c_id'])->find();
	$data[$key]['url_name'] = $url_name['name'];
	$data[$key]['c_name'] = $c_name['name'];
	$time = strtotime($value['created_at']);
	$date = date('Y-m-d', $time);
	$data[$key]['s_day'] = $date;
}
