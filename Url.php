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

header("Content-type:text/html;charset=utf-8");

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
		$page = $rules['page'];
		$p_count = $rules['p_count'];
		$datecount = $rules['datecount'];
		$url_s = explode('/', $url_addr);
		$n_url = $url_s[0] . "//" . $url_s[2] . "/";
		$nb_url = $url_s[0] . "//" . $url_s[2];
		$array = @get_headers($url_addr, 1);
		if (preg_match('/200/', $array[0])) {
			
			$html = file_get_html($url_addr);
			//常用：二级抓取
			switch ($rules_level) {
				case '2':
					if (empty($page)) {
						foreach ($html->find($l2_main) as $main) {
							// echo 1;exit;
							// echo $main;exit;
							foreach ($main->find('a') as $a) {
								if (strstr($a, 'http')) {
									$href = $a->href;
									// echo $href;exit;
								} else{
									$href = $n_url.$a->href;
									// echo $href;exit;
									// echo 1;exit;

								}
								$array1 = @get_headers($href, 1);
								if (preg_match('/200/', $array1[0])) {
									// echo $href;exit;
									$cont = file_get_html($href);
									if (!empty($cont)) {
										// echo $cont;exit;
										if (!empty($l2_title)) {
											foreach ($cont->find($l2_title) as $title) {
												$title = $title->innertext;
												// echo $title;exit;
											}
										}else{
											continue;
										}
										if (!empty($l2_time)) {
											// echo l2_time;exit;
											foreach ($cont->find($l2_time) as $key => $time) {
												$time = $time->plaintext;
												// echo $time;exit;
												//正则提取日期
												$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
												$string = preg_replace('/[^\d]*/', '', $string);												
											// 	$string = preg_replace('/[^\d]*/', '', $time);
												// echo $string;exit;
												$string = substr($string,0,$datecount);
												$date = date("Y-m-d",strtotime($string));
												$date2 = date("Y-m",strtotime($string));
												// echo $string;exit;
												// echo $date;exit;
											}									
										}else{
											$date = date("Y-m-d");
											$date2 = date("Y-m");
											// echo $date;exit;
											// break;
											// foreach ($cont->find($l2_title) as $key => $value) 
											// {
											// 	// echo $value;exit;
											// 	$time2 = $value->next_sibling();
											// 	if ($time2!="") {
											// 		$time = $time2->plaintext;
											// 		$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
											// 		$string = preg_replace('/[^\d]*/', '', $string);
											// 		$string = substr($string,0,$datecount);
													
											// 		$date = date("Y-m-d",strtotime($string));
											// 		$date2 = date("Y-m",strtotime($string));												
											// 	}else{
											// 		$date=date("Y-m-d");
											// 		$date2=date("Y-m");
											// 	}
												
											// }
											
										}
										foreach ($cont->find($l2_cont) as $content) {
											$content = $content->innertext;
											foreach ($keywords as $preg) {
												$key = explode(',', $preg['keywords']);
												foreach ($key as $val) {
													if (strstr($content, $val, true)) {
														// echo 1;exit;
														$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
														$ins['c_id'] = $info['id'];
														$ins['url_id'] = $id;
														$ins['url_addr'] = $href;
														$ins['rules_id'] = $data['rules_id'];
														$ins['title'] = $title;
														$ins['release_time'] = $date;
														$ins['month'] = $date2;
														$ins['cont'] = $content;
														$ins = nd($ins);
														$rows = Data::where('title', $title)->count();
														if ($rows < 1) {
															// echo 111;exit;
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
																'release_time' => $date,
															])->count();

															$m_counts = Data::where([
																'url_id' => $id,
																'c_id' => $info['id'],
																'month' => $date2,
															])->count();

															// 按日统计																					
															$s_ins['s_day'] = $date;
															$s_ins['url_id'] = $id;
															$s_ins['c_id'] = $info['id'];
															$s_ins['counts'] = $s_counts;
															$s_ins['created_at'] = date("Y-m-d,H:i:s");
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

																//按月统计
																$m_ins['s_month'] = $date2;
																$m_ins['url_id'] = $id;
																$m_ins['c_id'] = $info['id'];
																$m_ins['counts'] = $m_counts;
																$m_ins['created_at'] = date("Y-m-d,H:i:s");
																$m_upd['counts'] = $m_counts;
																$data3 = StatisticsMonth::where([
																	'c_id' => $m_ins['c_id'],
																	'url_id' => $m_ins['url_id'],
																	's_month' => $m_ins['s_month'],
																])->find();
																if ($s_counts != 0) {
																	if ($m_ins['c_id'] == $data3['c_id'] and $m_ins['s_month'] == $data3['s_month'] and $m_ins['url_id'] == $data3['url_id']) {
																		StatisticsMonth::where(
																			[
																				'id' => $data3['id'],
																			]
																		)->update($m_upd);
																	} else if ($m_ins['c_id'] != $data3['c_id'] or $m_ins['url_id'] != $data3['url_id']) {
																		StatisticsMonth::insert($m_ins);
																	} else if (empty($data3)) {
																		StatisticsMonth::insert($m_ins);
																	}
																} else {
																	continue;
																}

															//插入日志表
															$rst = Data::where('title', $title)->find();
															if ($rst['id'] == "") {
																$log_i = grz($id, $href, $rst['id'], $data['rules_id'], session('super_name'));
																Logs::insert($log_i);
															}
															// dump($log_i);exit;
														} else {
															break;
														}
													}
												}
											}
										}
									} else {
										continue;
									}
									
								}else{
									continue;
								}
							}
						}	
					}else{
						for ($i=1; $i < $p_count; $i++) {
							$page_n = str_replace('$i',$i,$page);
							$m_url = $url_addr.$page_n;
							$array = @get_headers($m_url, 1);
							if (preg_match('/200/', $array[0])) {
								$html = file_get_html($m_url);
								foreach ($html->find($l2_main) as $main) {
								// echo $main;exit;
								foreach ($main->find('a') as $a) {
									if (strstr($a, 'http')) {
										$href = $a->href;
									} elseif(strstr($a,'../')){
										$href = $n_url.url_real($url_addr,$a->href);
									}else{
										$href = $n_url.$a->href;
									}
									// echo $href;exit;
									$array1 = @get_headers($href, 1);
									if (preg_match('/200/', $array1[0])) {
										// echo $n_url.$href;exit;
										$cont = file_get_html($href);
										if (!empty($cont)) {
											if (!empty($l2_title)) {
												foreach ($cont->find($l2_title) as $title) {
													$title = $title->innertext;
												}
											}else{
												continue;
											}
											$row = Data::where('title', $title)->count();
											if ($row>=1) {
												break;
											}
											if (!empty($l2_time)) {
												foreach ($cont->find($l2_time) as $key => $time) {
													$time = $time->plaintext;
													//正则提取日期
													$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
													// if ($string = ) {
													// 	# code...
													// }
													// echo $string;exit;
													$string = preg_replace('/[^\d]*/', '', $string);
													// echo $string;exit;
													$string = substr($string,0,$datecount);
													$date = date("Y-m-d",strtotime($string));
													$date2 = date("Y-m",strtotime($string));
													// echo $date;exit;
												}									
											}else{
												// break;
												foreach ($cont->find($l2_title) as $key => $value) 
												{
													// echo $value;exit;
													$time2 = $value->next_sibling();
													if ($time2!="") {
														$time = $time2->plaintext;
														$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
														$string = preg_replace('/[^\d]*/', '', $string);
														$string = substr($string,0,$datecount);
														
														$date = date("Y-m-d",strtotime($string));
														$date2 = date("Y-m",strtotime($string));												
													}else{
														$date=date("Y-m-d");
														$date2=date("Y-m");
													}
													
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
															$ins['url_addr'] = $href;
															$ins['rules_id'] = $data['rules_id'];
															$ins['title'] = $title;
															$ins['release_time'] = $date;
															$ins['month'] = $date2;
															$ins['cont'] = $content;
															$ins = nd($ins);
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
																	'release_time' => $date,
																])->count();

																$m_counts = Data::where([
																	'url_id' => $id,
																	'c_id' => $info['id'],
																	'month' => $date2,
																])->count();

																// 按日统计																						
																$s_ins['s_day'] = $date;
																$s_ins['url_id'] = $id;
																$s_ins['c_id'] = $info['id'];
																$s_ins['counts'] = $s_counts;
																$s_ins['created_at'] = date("Y-m-d,H:i:s");
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

							
																//按月统计
																$m_ins['s_month'] = $date2;
																$m_ins['url_id'] = $id;
																$m_ins['c_id'] = $info['id'];
																$m_ins['counts'] = $m_counts;
																$m_ins['created_at'] = date("Y-m-d,H:i:s");
																$m_upd['counts'] = $m_counts;
																$data3 = StatisticsMonth::where([
																	'c_id' => $m_ins['c_id'],
																	'url_id' => $m_ins['url_id'],
																	's_month' => $m_ins['s_month'],
																])->find();
																if ($s_counts != 0) {
																	if ($m_ins['c_id'] == $data3['c_id'] and $m_ins['s_month'] == $data3['s_month'] and $m_ins['url_id'] == $data3['url_id']) {
																		StatisticsMonth::where(
																			[
																				'id' => $data3['id'],
																			]
																		)->update($m_upd);
																	} else if ($m_ins['c_id'] != $data3['c_id'] or $m_ins['url_id'] != $data3['url_id']) {
																		StatisticsMonth::insert($m_ins);
																	} else if (empty($data2)) {
																		StatisticsMonth::insert($m_ins);
																	}
																} else {
																	continue;
																}

																//插入日志表
																$rst = Data::where('title', $title)->find();
																if ($rst['id'] == "") {
																	$log_i = grz($id, $href, $rst['id'], $data['rules_id'], session('super_name'));	
																	Logs::insert($log_i);
																}
																// dump($log_i);exit;
															} else {
																break;
															}
														}
													}
												}
											}
										} else {
											continue;
										}
										
									}else{
										continue;
									}
								}
							}	
						}else{
							continue;
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
	}
}
