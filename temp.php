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
		$rules_name = $rules['name'];
		$rules_level = $rules->getData('level');
		$l1_rule = $rules['l1_rule'];
		$l2_main = $rules['l2_main'];
		$l2_title = $rules['l2_title'];
		$l2_time = $rules['l2_time'];
		$l2_cont = $rules['l2_cont'];
		$url_s = explode('/', $url_addr);
		$n_url = $url_s[0] . "//" . $url_s[2] . "/";
		$nb_url = $url_s[0] . "//" . $url_s[2];
		$array = @get_headers($url_addr, 1);
		if (preg_match('/200/', $array[0])) {
			$html = file_get_html($url_addr);
			//常用：二级抓取
			switch ($rules_level) {
				case '2':
					foreach ($html->find($l2_main) as $main) {
						// echo $main;exit;
						foreach ($main->find('a') as $a) {
							if (strstr($a, 'http')) {
								// echo $a->href."<br>";
								$array1 = @get_headers($a->href, 1);
								if (preg_match('/200/', $array1[0])) {
									// echo "1";
									// echo $n_url.$a->href;exit;
									$arr = array($a->href);
									foreach ($arr as $url_all) {
										$cont = file_get_html($url_all);
										if (!empty($cont)) {
											if (empty($l2_title)) {
												$title = "暂无文章标题";
											} else {
												foreach ($cont->find($l2_title) as $title) {
													$title = $title->innertext;
												}
											}
											// echo $title;exit;
											if (empty($l2_time)) {
												$info1 = "暂无文章发布信息";
											} else {
												foreach ($cont->find($l2_time) as $info1) {
													// echo $info;exit;
													$info1 = $info1->plaintext;
													// dump($info1);exit;
												}
											}
											foreach ($cont->find($l2_cont) as $content) {
												$content = $content->innertext;
												foreach ($keywords as $preg) {
													$key = explode(',', $preg['keywords']);
													foreach ($key as $val) {
														if (strstr($content, $val, true)) {
															$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
															$ins['c_id'] = $info['id'];
															$ins['url_id'] = $id;
															$ins['url_addr'] = $url_all;
															$ins['rules_id'] = $data['rules_id'];
															$ins['title'] = $title;
															$ins['release_info'] = $info1;
															$ins['cont'] = $content;
															$ins['day'] = date("Y-m-d");
															$ins['month'] = date("Y-m");
															$ins = nd($ins);
															// dump($ins);exit;
															$res = Data::insert($ins);
															if ($res) {
																$this->assign([
																	"message" => "抓取成功！",
																	"waitSecond" => 2,
																]);
															} else {
																$this->assign("error", "抓取失败！");
															}
															$query = Data::execute("DELETE data FROM data, (SELECT min(id) id, cont FROM data GROUP BY cont HAVING count(*) > 1) t2
																	WHERE data.cont = t2.cont and data.id>t2.id");

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

															// 开始统计
															$m_counts = Data::where(
																[
																	'url_id' => $id,
																	'c_id' => $info['id'],
																	'month' => date('Y-m'),
																]
															)->count();

															// 月统计
															$s_month['s_month'] = date('Y-m');
															$s_month['url_id'] = $id;
															$s_month['c_id'] = $info['id'];
															$s_month['counts'] = $m_counts;
															$s_month['created_at'] = date('Y-m-d,H:i:s');
															$s_month_upd['counts'] = $m_counts;
															// $s_month_upd['id'] = 
															$data2 = StatisticsMonth::where([
																'c_id' => $s_month['c_id'],
																'url_id' => $s_month['url_id'],
																's_month' => date("Y-m"),

															])->find();
															if ($m_counts != 0) {
																if ($s_month['c_id'] == $data2['c_id'] and $s_month['s_month'] == $data2['s_month'] and $s_month['url_id'] == $data2['url_id']) {
																	StatisticsMonth::where(
																		[
																			'id' => $data2['id'],
																		]
																	)->update($s_month_upd);
																} else if ($s_month['c_id'] != $data2['c_id'] or $s_month['url_id'] != $data2['url_id']) {
																	// echo 1;exit;
																	StatisticsMonth::insert($s_month);
																} else if (empty($data2)) {
																	// echo 2;exit;
																	StatisticsMonth::insert($s_month);
																}
															} else {
																continue;
															}


															$rst = Data::where('title', $title)->find();
															if ($rst['id'] == "") {
																$log_i = grz($id, $url_all, 0, $data['rules_id'], session('super_name'));
															} else {
																$log_i = grz($id, $url_all, $rst['id'], $data['rules_id'], session('super_name'));
															}
															// dump($log_i);exit;
															Logs::insert($log_i);
															// echo Logs::getLastSql();exit;
														}
													}
												}
											}
										} else {
											continue;
										}
									}
								}
							} else {
								if (!strstr($a, 'javascript')) {
									// echo $a->href . "<br>"; exit;    
									$array1 = @get_headers($n_url . $a->href, 1);
									if (preg_match('/200/', $array1[0])) {
										// echo "1";exit;
										// echo $n_url.$a->href;exit;
										$arr = array($n_url . $a->href);
										foreach ($arr as $url_all) {
											$cont = file_get_html($url_all);
											if (!empty($cont)) {
												if (empty($l2_title)) {
													$title = "暂无文章标题";
												} else {
													if (!$cont->find($l2_title)) {
														continue;
													}
													foreach ($cont->find($l2_title) as $title) {
														$title = $title->innertext;
													}
												}
												if (empty($l2_time)) {
													$info1 = "暂无文章发布信息";
												} else {
													if (!$cont->find($l2_time)) {
														continue;
													}
													foreach ($cont->find($l2_time) as $info1) {
														$info1 = $info1->plaintext;
													}
													// $info1 = $info1;
												}
												// echo $title;exit;
												foreach ($cont->find($l2_cont) as $content) {
													$content = $content->innertext;
													// echo $content;exit;
													foreach ($keywords as $preg) {
														$key = explode(',', $preg['keywords']);
														// dump($key);exit;
														foreach ($key as $val) {
															// echo $val."<br>";
															if (strstr($content, $val)) {
																// echo 1;exit;
																$ee['url_name'] = $url_name;
																$ee['time'] = date("Y-m-d H:i:s");
																Test::insert($ee);

																$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
																// dump($info);exit;
																$ins['c_id'] = $info['id'];
																$ins['url_id'] = $id;
																$ins['url_addr'] = $url_all;
																$ins['rules_id'] = $data['rules_id'];
																$ins['title'] = $title;
																// echo $title;exit;
																$ins['release_info'] = $info1;
																$ins['cont'] = $content;
																$ins['day'] = date("Y-m-d");
																$ins['month'] = date("Y-m");
																$ins = nd($ins);
																// dump($ins);exit;
																$res = Data::insert($ins);
																if ($res) {
																	$this->assign([
																		"message" => "抓取成功！",
																		"waitSecond" => 2,
																	]);
																} else {
																	$this->assign("error", "抓取失败！");
																}
																$query = Data::execute("DELETE data FROM data, (SELECT min(id) id, cont FROM data GROUP BY cont HAVING count(*) > 1) t2
																		WHERE data.cont = t2.cont and data.id>t2.id");
																// 开始统计
																$d_counts = Data::where(
																	[
																		'url_id' => $id,
																		'c_id' => $info['id'],
																		'day' => date("Y-m-d"),
																	]
																)->count();
																// echo Data::getLastSql();exit;
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
																	's_day' => $s_day['s_day'],
																])->find();
																// echo StatisticsDay::getLastSql();exit;
																// dump($data2);exit;
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

																// 开始统计
																$m_counts = Data::where(
																	[
																		'url_id' => $id,
																		'c_id' => $info['id'],
																		'month' => date('Y-m'),
																	]
																)->count();

																// 月统计
																$s_month['s_month'] = date('Y-m');
																$s_month['url_id'] = $id;
																$s_month['c_id'] = $info['id'];
																$s_month['counts'] = $m_counts;
																$s_month['created_at'] = date('Y-m-d,H:i:s');
																$s_month_upd['counts'] = $m_counts;
																// $s_month_upd['id'] = 
																$data2 = StatisticsMonth::where([
																	'c_id' => $s_month['c_id'],
																	'url_id' => $s_month['url_id'],
																	's_month' => date("Y-m"),

																])->find();
																if ($m_counts != 0) {
																	if ($s_month['c_id'] == $data2['c_id'] and $s_month['s_month'] == $data2['s_month'] and $s_month['url_id'] == $data2['url_id']) {
																		StatisticsMonth::where(
																			[
																				'id' => $data2['id'],
																			]
																		)->update($s_month_upd);
																	} else if ($s_month['c_id'] != $data2['c_id'] or $s_month['url_id'] != $data2['url_id']) {
																		// echo 1;exit;
																		StatisticsMonth::insert($s_month);
																	} else if (empty($data2)) {
																		// echo 2;exit;
																		StatisticsMonth::insert($s_month);
																	}
																} else {
																	continue;
																}
																//抓取日志
																$rst = Data::where('title', $title)->find();
																if ($rst['id'] == "") {
																	$log_i = grz($id, $url_all, 0, $data['rules_id'], session('super_name'));
																} else {
																	$log_i = grz($id, $url_all, $rst['id'], $data['rules_id'], session('super_name'));
																}
																// dump($log_i);exit;
																Logs::insert($log_i);
																// echo Logs::getLastSql();exit;															
															}
														}
													}
												}
											} else {
												continue;
											}
										}
									} else {
										continue;
									}
								}
							}
						}
					}
					break;
				case '1':
					# code...
					break;
			}
		} else {
			$this->assign('error', '无效的url远程地址！');
		}
		// $html->clear();
		// unset($html);
		return view('index');
		$year = substr($date[$kk],0,4);
													$month = substr($date[$kk],5,2);
													$day = substr($date[$kk],8,2);
	}
}

if (empty($start)&&empty($end)) {
				if (empty($u_id)) {
					if (empty($c_id)) {
						
					}
				}
			}

		if (input('post.submit')=='submit') {
			$info = request()->except('id,submit');
			$start = $info['start'];
			$end = $info['end'];
			$u_id = $info['sel_url'];
			$c_id = $info['sel_category'];
			if (empty($start)&&empty($end)) {
				if (empty($u_id)) {
					if (empty($c_id)) {
						$this->assign('error', '开始时间、结束时间、url地址、分类均不能为空！');
					}else{
						$data = Data::where('c_id',$c_id)->limit(0,10)->select();
						foreach ($data as $key => $value) {
							$url = Urls::where('id',$value['url_id'])->find();
							$data[$key]['url_name'] = $url['name'];
						}
						foreach ($data as $key => $value) {
							$category = Category::where('id',$value['c_id'])->find();
							$data[$key]['c_name'] = $category['name'];
						}
					}
				}
			}
		}else{
			$last = date('Y-m-d');
			$data = Data::where('created_at','like',$last.'%')->limit(0,10)->select();
			foreach ($data as $key => $value) {
				$url = Urls::where('id',$value['url_id'])->find();
				$data[$key]['url_name'] = $url['name'];
			}
			foreach ($data as $key => $value) {
				$category = Category::where('id',$value['c_id'])->find();
				$data[$key]['c_name'] = $category['name'];
			}
		}