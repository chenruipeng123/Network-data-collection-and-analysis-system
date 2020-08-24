<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\GrabRules;
use app\index\model\Category;
use app\index\model\Data;
use app\index\model\GrabLog;

header("Content-type:text/html;charset=utf-8");
ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');

class Grab extends Supers
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index()

	{
		$dom = new \simple_html_dom();
		$keywords = Category::field('keywords')->where('show_tag', '1')->order('show_order asc')->select();
		if (input("post.add") == "add") {
			$data = request()->except('add');
			$url = $data['url_addr'];
			$url_name = $data['url_name'];
			$rule = $data['rule_level'];
			$url_s = explode('/', $url);
			$n_url = $url_s[0] . "//" . $url_s[2] . "/";
			$nb_url = $url_s[0] . "//" . $url_s[2];
			$html = file_get_html($url);
			// 不常用，一级抓取
			if ($data['rule_level'] == 1) {

				foreach ($html->find('p') as $content) {
					// $text = $content;
					// echo gettype($conttent);exit;
					$text1 = $content->parent();
					$text = $text1->innertext;
					// exit;
					foreach ($keywords as $preg) {
						$key = explode(',', $preg['keywords']);
						foreach ($key as $val) {
							if (strstr($text, $val, true)) {
								$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
								$ins['c_id'] = $info['id'];
								$ins['url_name'] = $url_name;
								$ins['url_addr'] = $url;
								$ins['rule_level'] = $rule;
								$ins['category'] = $info['name'];
								$ins['cont'] = $text;
								$ins['show_tag'] = 1;
								$ins = nd($ins);
								// dump($info);exit;
								// dump($ins);exit;
								$res = Data::insert($ins);
								$query = Data::execute("DELETE data FROM data, (SELECT min(id) id, cont FROM data GROUP BY cont HAVING count(*) > 1) t2 WHERE data.cont = t2.cont and data.id>t2.id");
								if (!$query) {
									$this->assign([
										"error" => "该条数据唯一！"
									]);
								}
								$log_i = grz($ins['url_name'], $ins['url_addr'], "抓取成功", session('super_name'));
								GrabLog::insert($log_i);
								if ($res) {
									$this->assign([
										"message" => "抓取成功！",
										"waitSecond" => 2,
									]);
								} else {
									$this->assign("error", "有未成功抓取数据！");
								}
							}
						}
					}
				}
			}
			//常用：二级抓取
			if ($data['rule_level'] == 2) {
				foreach ($html->find('a') as $a) {
					if (strstr($a, 'http')) {
						if (url_exists($a->href)) {
								// echo $a->href . "<br>";
								// exit;
							$arr = array($a->href);
							foreach ($arr as $url_all) {
								$cont = file_get_html($url_all);
								if (!empty($cont)) {
									foreach ($cont->find('p') as $content) {
										$text1 = $content->parent();
										$text = $text1->innertext;
										foreach ($keywords as $preg) {
											$key = explode(',', $preg['keywords']);
											foreach ($key as $val) {
												if (strstr($text, $val, true)) {
													$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
													$ins['c_id'] = $info['id'];
													$ins['url_name'] = $url_name;
													$ins['url_addr'] = $url_all;
													$ins['rule_level'] = $rule;
													$ins['category'] = $info['name'];
													$ins['cont'] = $text;
													$ins['show_tag'] = 1;
													$ins = nd($ins);
													// dump($info);exit;
													// dump($ins);exit;
													$res = Data::insert($ins);
													$query = Data::execute("DELETE data FROM data, (SELECT min(id) id, cont FROM data GROUP BY cont HAVING count(*) > 1) t2
													WHERE data.cont = t2.cont and data.id>t2.id");
													if (!$query) {
														$this->assign([
															"error" => "该条数据唯一！"
														]);
													}
													$log_i = grz($ins['url_name'], $ins['url_addr'], "抓取成功", session('super_name'));
													GrabLog::insert($log_i);
													if ($res) {
														$this->assign([
															"message" => "抓取成功！",
															"waitSecond" => 2,
														]);
													} else {
														$this->assign("error", "有未成功抓取数据！");
													}
												}
											}
										}
									}
								}
							}
						} else {
							continue;
						}
					} else {
						if (!strstr($a, 'javascript')) {
							if (url_exists($n_url . $a->href)) {
										// echo $a->href."<br>";
								$arr = array($n_url . $a->href);
								foreach ($arr as $url_all) {
									$cont = file_get_html($url_all);
									if (!empty($cont)) {
										foreach ($cont->find('p') as $content) {
											$text1 = $content->parent();
											$text = $text1->innertext;
											foreach ($keywords as $preg) {
												$key = explode(',', $preg['keywords']);
												foreach ($key as $val) {
													if (strstr($text, $val, true)) {
														$info = Category::where('keywords', 'like', '%' . $val . '%')->find();
														$ins['c_id'] = $info['id'];
														$ins['url_name'] = $url_name;
														$ins['url_addr'] = $url_all;
														$ins['rule_level'] = $rule;
														$ins['category'] = $info['name'];
														$ins['cont'] = $text;
														$ins['show_tag'] = 1;
														$ins = nd($ins);
															// dump($info);exit;
															// dump($ins);exit;
														$res = Data::insert($ins);
														$query = Data::execute("DELETE data FROM data, (SELECT min(id) id, cont FROM data GROUP BY cont HAVING count(*) > 1) t2
														WHERE data.cont = t2.cont and data.id>t2.id");
														if (!$query) {
															$this->assign([
																"error" => "该条数据唯一！"
															]);
														}
														$log_i = grz($ins['url_name'], $ins['url_addr'], "抓取成功", session('super_name'));
														GrabLog::insert($log_i);
														if ($res) {
															$this->assign([
																"message" => "抓取成功！",
																"waitSecond" => 2,
															]);
														} else {
															$this->assign("error", "有未成功抓取数据！");
														}
													}
												}
											}
										}
									} else {
										continue;
									}

								}
							}
						}
					}
				}
			}
			$html->clear();
			unset($html);
		}

		$list = GrabRules::where('show_tag', '1')->order('show_order asc')->select();
		$this->assign("list", $list);
		return view();
	}

}
