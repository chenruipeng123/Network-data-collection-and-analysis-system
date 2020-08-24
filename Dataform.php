<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Data;
use app\index\model\Urls;
use app\index\model\StatisticsDay;
use app\index\model\StatisticsMonth;
use app\index\model\Category;


class Dataform extends Supers
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index($id="",$s_day="")
	{
		$day = StatisticsDay::query("SELECT DISTINCT  `s_day` 
			FROM  `statistics_day` 
			WHERE DATE_FORMAT(  `s_day` ,  '%Y%m' ) = DATE_FORMAT( CURDATE( ) ,  '%Y%m' ) order by `s_day` desc");
		$category = Category::where('show_tag',1)->select();
		$url = Urls::where('show_tag',1)->select();
		$this->assign([
			
			'day' => $day,
			'url' => $url,
			'category' => $category,
		]);
		return view();
	}

	public function data($sel_day="",$sel_url="",$sel_category="")
	{
		if (empty($sel_day)) {
			if (empty($sel_url)) {
				if (empty($sel_category)) {
					$data = StatisticsDay::order('s_day desc')->select();				
				}else{
					$data = StatisticsDay::where('c_id',$sel_category)->order( 's_day desc')->select();					
				}
			}else{
				if (empty($sel_category)) {
					$data = StatisticsDay::where('url_id',$sel_url)->order( 's_day desc')->select(); 
				}else{
					$data = StatisticsDay::where(['url_id'=>$sel_url,'c_id'=>$sel_category])->order( 's_day desc')->select(); 
				}			
			}
		}else{
			if (empty($sel_category)) {
				if (empty($sel_url)) {
					$data = StatisticsDay::where('s_day',$sel_day)->order('s_day desc')->select();
				}else{
					$data = StatisticsDay::where(['url_id'=>$sel_url,'s_day'=>$sel_day])->order( 's_day desc')->select();					
				}
			}else {
				if (empty($sel_url)) {
					$data = StatisticsDay::where(['c_id'=>$sel_category,'s_day'=>$sel_day])->order( 's_day desc')->select();					
				}else{
					$data = StatisticsDay::where(['url_id'=>$sel_url,'s_day'=>$sel_day,'c_id'=>$sel_category])->order( 's_day desc')->select();					
				}
			}	
		}
		foreach ($data as $key => $value) {
			$url_name = Urls::where('id',$value['url_id'])->find();
			$c_name = Category::where('id',$value['c_id'])->find();
			$data[$key]['url_name'] = $url_name['name'];
			$data[$key]['c_name'] = $c_name['name'];
		}
		echo json_encode($data);
	}

	public function m_index()
	{
		$month = StatisticsMonth::distinct(true)->field('s_month')->order('s_month desc')->limit(12)->select();
		$category = Category::where('show_tag',1)->select();
		$url = Urls::where('show_tag',1)->select();
		$this->assign([
			'month' => $month,
			'url' => $url,
			'category' => $category,
		]);
		return view();
	}

	public function m_data($sel_month="",$sel_url="",$sel_category="")
	{
		if (empty($sel_month)) {
			if (empty($sel_url)) {
				if (empty($sel_category)) {
					$data = StatisticsMonth::order('s_month desc')->select();				
				}else{
					$data = StatisticsMonth::where('c_id',$sel_category)->order( 's_month desc')->select();					
				}
			}else{
				if (empty($sel_category)) {
					$data = StatisticsMonth::where('url_id',$sel_url)->order( 's_month desc')->select(); 
				}else{
					$data = StatisticsMonth::where(['url_id'=>$sel_url,'c_id'=>$sel_category])->order( 's_month desc')->select(); 
				}			
			}
		}else{
			if (empty($sel_category)) {
				if (empty($sel_url)) {
					$data = StatisticsMonth::where('s_month',$sel_month)->order('s_month desc')->select();
				}else{
					$data = StatisticsMonth::where(['url_id'=>$sel_url,'s_month'=>$sel_month])->order( 's_month desc')->select();					
				}
			}else {
				if (empty($sel_url)) {
					$data = StatisticsMonth::where(['c_id'=>$sel_category,'s_month'=>$sel_month])->order( 's_month desc')->select();					
				}else{
					$data = StatisticsMonth::where(['url_id'=>$sel_url,'s_month'=>$sel_month,'c_id'=>$sel_category])->order( 's_month desc')->select();					
				}
			}	
		}
		foreach ($data as $key => $value) {
			$url_name = Urls::where('id',$value['url_id'])->find();
			$c_name = Category::where('id',$value['c_id'])->find();
			$data[$key]['url_name'] = $url_name['name'];
			$data[$key]['c_name'] = $c_name['name'];
		}
		echo json_encode($data);
	}

}
