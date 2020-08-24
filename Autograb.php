<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Rules;
use app\index\model\Category;
use app\index\model\Data;
use app\index\model\Urls;
use app\index\model\Logs;
use app\index\model\StatisticsDay;
use app\index\model\StatisticsMonth;
use app\index\model\Test;

header("Content-type:text/html;charset=utf-8");

class Autograb extends Controller
{
    public function index()
    {
		/* 
		初始dom类
		*/
		$dom = new \simple_html_dom();
        $keywords = Category::field('keywords')->where('show_tag', '1')->order('show_order asc')->select();
		$url_list = Urls::where('show_tag', '1')->order('show_order asc')->select();
        foreach ($url_list as $key => $value) {
			$url = $value['addr'];
			$url_name = $value['name'];
			$url_rule = Rules::where('id',$value['rules_id'])->find();
			// dump($url_rule);exit;
			$rules_level = $url_rule->getData('level');
			$rules_name = $url_rule['name'];
			$rules_l1 = $url_rule['l1_rule'];
			$l2_title = $url_rule['l2_title'];
			$l2_main = $url_rule['l2_main'];
			$l2_time = $url_rule['l2_time'];
			$l2_cont = $url_rule['l2_cont'];
			$page = $url_rule['page'];
			$p_count = $url_rule['p_count'];
			$datecount = $url_rule['datecount'];		
            $url_s = explode('/', $url);
			$n_url = $url_s[0] . "//" . $url_s[2] . "/";
			$array = @get_headers($url,1); 
			if(preg_match('/200/',$array[0])){ 
				$html = file_get_html($url);
				// echo $url."<br>";s
				// echo $url_rule."<br>";
				if ($rules_level == 2) {
					// echo $url."<br>";
					// echo $url_rule."<br>";
					if (empty($page)) {
						foreach ($html->find($l2_main) as $main) {
							foreach ($main->find('a') as $a) {
							// echo $a->href . "<br>";
							if (strstr($a, 'http')) {
								$href = $a->href;
							}else{
								$href = $n_url.$a->href;
							}
								$array1 = @get_headers($href,1); 
								if(preg_match('/200/',$array1[0])){ 
									$cont = file_get_html($href);
									if (!empty($cont)) {
										if (!empty($l2_title)) {
											foreach ($cont->find($l2_title) as $title) {
												$title = $title->innertext;
												$rows = Data::where('title', $title)->count();
												if ($rows >= 1) {
													continue;
												}
											}
										}else{
											continue;
										}
										

										if (!empty($l2_time)) {
											foreach ($cont->find($l2_time) as $key => $time) {
												$time = $time->plaintext;
												//正则提取日期
												$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
												$string = preg_replace('/[^\d]*/', '', $string);												
											// 	$string = preg_replace('/[^\d]*/', '', $time);
											// 	echo $string;exit;
												$string = substr($string,0,$datecount);
												$date = date("Y-m-d",strtotime($string));
												$date2 = date("Y-m",strtotime($string));
											}									
										}else{
											$date = date("Y-m-d");
											$date2 = date("Y-m");
											// break;
											// foreach ($cont->find($l2_title) as $key => $value2) 
											// {
											// 	// echo $value;exit;
											// 	$time2 = $value2->next_sibling();
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
														// $ee['url_name'] = $url_name;
														// $ee['time'] = date("Y-m-d H:i:s");
														// Test::insert($ee);
														$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
														$ins['c_id'] = $info['id'];
														$ins['url_id'] = $value['id'];
														$ins['url_addr'] = $href;
														$ins['rules_id'] = $value['rules_id'];
														$ins['title'] = $title;
														$ins['release_time'] = $date;
														$ins['month'] = $date2;														
														$ins['cont'] = $content;
														$ins = nd1($ins);
														// dump($ins);exit;
														$rows = Data::where('title', $title)->count();
														if ($rows < 1) {
															Data::insert($ins);
															// 统计分析
															$s_counts = Data::where([
																'url_id' => $value['id'],
																'c_id' => $info['id'],
																'release_time' => $date,
															])->count();

															$m_counts = Data::where([
																'url_id' => $value['id'],
																'c_id' => $info['id'],
																'month' => $date2,
															])->count();

															// 按日统计																						
															$s_ins['s_day'] = $date;
															$s_ins['url_id'] = $value['id'];
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
															$m_ins['url_id'] = $value['id'];
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
															
															//日志
															$rst = Data::where('title',$title)->find();
															if ($rst['id']!="") {
																$log_i = grz($value['id'], $href,$rst['id'],$value['rules_id'], '系统自动抓取');
																Logs::insert($log_i);
															}
															// dump($log_i);exit;
														}else{
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
							 $m_url = $url.$page_n; 
 							$array = @get_headers($m_url, 1);
							if (preg_match('/200/', $array[0])) {
								$html = file_get_html($m_url);
								foreach ($html->find($l2_main) as $main) {
									foreach ($main->find('a') as $a) {
									// echo $a->href . "<br>";
									if (strstr($a, 'http')) {
										$href = $a->href;
									}elseif(strstr($a,'../')){
										$href = $n_url.url_real($url,$a->href);
									}else{
										$href = $n_url.$a->href;
									}
										$array1 = @get_headers($href,1); 
										if(preg_match('/200/',$array1[0])){ 
											$cont = file_get_html($href);
											if (!empty($cont)) {
												if (!empty($l2_title)) {
													foreach ($cont->find($l2_title) as $title) {
														$title = $title->innertext;
														$rows = Data::where('title', $title)->count();
														if ($rows >= 1) {
															break 2;
														}
													}
												}else{
													continue;
												}
											
												if (!empty($l2_time)) {
													foreach ($cont->find($l2_time) as $key => $time) {
														$time = $time->plaintext;
														//正则提取日期
														$string = preg_replace('#^.*(\d{4})\-(\d{1,2})-(\d{1,2}).*$#', '$1$2$3', $time );
														$string = preg_replace('/[^\d]*/', '', $string);												
													// 	$string = preg_replace('/[^\d]*/', '', $time);
													// 	echo $string;exit;
														$string = substr($string,0,$datecount);
														$date = date("Y-m-d",strtotime($string));
														$date2 = date("Y-m",strtotime($string));

													}									
												}else{
													foreach ($cont->find($l2_title) as $key => $value2) 
													{
														// echo $value;exit;
														$time2 = $value2->next_sibling();
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
																// $ee['url_name'] = $url_name;
																// $ee['time'] = date("Y-m-d H:i:s");
																// Test::insert($ee);
																$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
																$ins['c_id'] = $info['id'];
																$ins['url_id'] = $value['id'];
																$ins['url_addr'] = $href;
																$ins['rules_id'] = $value['rules_id'];
																$ins['title'] = $title;
																$ins['release_time'] = $date;
																$ins['month'] = $date2;
																$ins['cont'] = $content;
																$ins = nd1($ins);
																// dump($ins);exit;
																$rows = Data::where('title', $title)->count();
																if ($rows < 1) {
																	Data::insert($ins);
																	// 统计分析
																	$s_counts = Data::where([
																		'url_id' => $value['id'],
																		'c_id' => $info['id'],
																		'release_time' => $date,
																	])->count();

																	$m_counts = Data::where([
																		'url_id' => $value['id'],
																		'c_id' => $info['id'],
																		'month' => $date2,
																	])->count();

																	// 按日统计																						
																	$s_ins['s_day'] = $date;
																	$s_ins['url_id'] = $value['id'];
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
																	$m_ins['url_id'] = $value['id'];
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
																	
																	//日志
																	$rst = Data::where('title',$title)->find();
																	if ($rst['id']!="") {
																		$log_i = grz($value['id'], $href,$rst['id'],$value['rules_id'], '系统自动抓取');
																		Logs::insert($log_i);
																	}
																	// dump($log_i);exit;
																}else{
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
							}
						}
					}
					
				}
			}else{
				continue;
			}
			$html->clear();
        }
    }
}
