<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Urls;
use app\index\model\Data;
use app\index\model\Category;
use app\index\model\StatisticsDay;
use app\index\model\StatisticsMonth;

class Datachart extends Supers
{
	/**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
	public function index($id="",$day="",$category="")
	{
		//
		$url = Urls::where('show_tag',1)->order('show_order asc')->select();
		$day = StatisticsDay::query("SELECT DISTINCT  `s_day` 
			FROM  `statistics_day` 
			WHERE DATE_FORMAT(  `s_day` ,  '%Y%m' ) = DATE_FORMAT( CURDATE( ) ,  '%Y%m' ) order by `s_day` desc");
		$category = Category::where('show_tag',1)-> select();
		$this->assign([
			"url" => $url,
			"id" => $id,
			'day' => $day,
			'category' => $category,
			// "data" => $data,
		]);
		return view();
	}

	public function data($id="",$day=""){
		// $id=$_POST['id'];
		// $day=$_POST['day'];
		// $category=$_POST['category'];
		$info = Category::where('show_tag',1)->select();
		if(empty($id)&&empty($day)){
			foreach ($info as $key => $value) {
				$data = StatisticsDay::where('c_id',$value['id'])->select();
				$sum = 0;
				foreach ($data as $k => $v) {
					// echo $v['c_id'].":".$v['counts'].'<br>';
					$sum += $v['counts'];
				}
				$info[$key]['sum'] = $sum;

			}
		}elseif(!empty($id)&&empty($day)){
			foreach ($info as $key => $value) {
				$data = StatisticsDay::where(['url_id'=>$id,'c_id'=>$value['id']])->select();
				$sum = 0;
				foreach ($data as $k => $v) {
					// echo $v['c_id'].":".$v['counts'].'<br>';
					$sum += $v['counts'];
				}
				$info[$key]['sum'] = $sum;

			}
		}elseif(empty($id)&&!empty($day)){
			foreach ($info as $key => $value) {
				$data = StatisticsDay::where(['s_day'=>$day,'c_id'=>$value['id']])->select();
				$sum = 0;
				foreach ($data as $k => $v) {
					// echo $v['c_id'].":".$v['counts'].'<br>';
					$sum += $v['counts'];
				}
				$info[$key]['sum'] = $sum;

			}
		}elseif(!empty($id)&&!empty($day)){
			foreach ($info as $key => $value) {
				$data = StatisticsDay::where(['s_day'=>$day,'c_id'=>$value['id'],'url_id'=>$id])->select();
				$sum = 0;
				foreach ($data as $k => $v) {
					// echo $v['c_id'].":".$v['counts'].'<br>';
					$sum += $v['counts'];
				}
				$info[$key]['sum'] = $sum;

			}
		}
		echo json_encode($info);
	}

		public function m_index($id="",$month="")
		{
			//
			$url = Urls::where('show_tag',1)->order('show_order asc')->select();
			$month = StatisticsMonth::distinct(true)->field('s_month')->order('s_month desc')->limit(12)->select();		
			// $category = Category::where('show_tag',1)-> select();
			$this->assign([
				"url" => $url,
				"id" => $id,
				'month'=>$month,
				// 'category' => $category,
				// "data" => $data,
			]);
			return view();
		}

		public function m_data($id="",$month=""){
			// $id=$_POST['id'];
			// $day=$_POST['day'];
			// $category=$_POST['category'];
			$info = Category::where('show_tag',1)->select();
			if(empty($id)&&empty($month)){
				foreach ($info as $key => $value) {
					$data = StatisticsMonth::where('c_id',$value['id'])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						// echo $v['c_id'].":".$v['counts'].'<br>';
						$sum += $v['counts'];
					}
					$info[$key]['sum'] = $sum;

				}
			}elseif(!empty($id)&&empty($month)){
				foreach ($info as $key => $value) {
					$data = StatisticsMonth::where(['url_id'=>$id,'c_id'=>$value['id']])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						// echo $v['c_id'].":".$v['counts'].'<br>';
						$sum += $v['counts'];
					}
					$info[$key]['sum'] = $sum;

				}
			}elseif(empty($id)&&!empty($month)){
				foreach ($info as $key => $value) {
					$data = StatisticsMonth::where(['s_month'=>$month,'c_id'=>$value['id']])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						// echo $v['c_id'].":".$v['counts'].'<br>';
						$sum += $v['counts'];
					}
					$info[$key]['sum'] = $sum;

				}
			}elseif(!empty($id)&&!empty($month)){
				foreach ($info as $key => $value) {
					$data = StatisticsMonth::where(['s_month'=>$month,'c_id'=>$value['id'],'url_id'=>$id])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						// echo $v['c_id'].":".$v['counts'].'<br>';
						$sum += $v['counts'];
					}
					$info[$key]['sum'] = $sum;

				}
			}
			echo json_encode($info);
		}

	public function d_index($category="",$url="")
	{
		$category = Category::where('show_tag',1)-> select();
		$url = Urls::where('show_tag',1)->order('show_order asc')->select();
		$this->assign([
			'category' => $category,
			'url'=>$url,
		]);
		return view();
	}

	public function d_data($url="",$category="")
	{
		$date = StatisticsDay::field('s_day')->distinct(true)->order('s_day desc')->limit(0,30 )->select();
		if ($url=="") {
			if ($category=="") {
				foreach ($date as $key => $value) {
					$data = StatisticsDay::where('s_day',$value['s_day'])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}else{
				foreach ($date as $key => $value) {
					$data = StatisticsDay::where(['s_day'=>$value['s_day'],'c_id'=>$category])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}
			
		}else{
			if ($category=="") {
				foreach ($date as $key => $value) {
					$data = StatisticsDay::where(['s_day'=>$value['s_day'],'url_id'=>$url])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}else{
				foreach ($date as $key => $value) {
					$data = StatisticsDay::where(['s_day'=>$value['s_day'],'url_id'=>$url,'c_id'=>$category])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}
		}
		
		echo json_encode($date);
	}

	public function d2_index($category="",$url="")
	{
		$category = Category::where('show_tag',1)-> select();
		$url = Urls::where('show_tag',1)->order('show_order asc')->select();
		$this->assign([
			'category' => $category,
			'url'=>$url,
		]);
		return view();
	}

	public function d2_data($url="",$category="")
	{
		$date = StatisticsMonth::field('s_month')->distinct(true)->order('s_month desc')->limit(0,12 )->select();
		if ($url=="") {
			if ($category=="") {
				foreach ($date as $key => $value) {
					$data = StatisticsMonth::where('s_month',$value['s_month'])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}else{
				foreach ($date as $key => $value) {
					$data = StatisticsMonth::where(['s_month'=>$value['s_month'],'c_id'=>$category])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}
			
		}else{
			if ($category=="") {
				foreach ($date as $key => $value) {
					$data = StatisticsMonth::where(['s_month'=>$value['s_month'],'url_id'=>$url])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}else{
				foreach ($date as $key => $value) {
					$data = StatisticsMonth::where(['s_month'=>$value['s_month'],'url_id'=>$url,'c_id'=>$category])->select();
					$sum = 0;
					foreach ($data as $k => $v) {
						$sum += $v['counts'];
					}
					// echo $sum.'<br>';
					$date[$key]['sum'] = $sum;
				}
			}
		}
		
		echo json_encode($date);
	}
}
