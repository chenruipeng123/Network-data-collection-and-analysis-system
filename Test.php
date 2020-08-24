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

header("Content-type:text/html;charset=utf-8");

class Test extends Controller
{
    public function index()
    {
		/* 
		初始dom类
		*/
		$dom = new \simple_html_dom();
        $keywords = Category::field('keywords')->where('show_tag', '1')->order('show_order asc')->select();
        $url_list = Urls::where('show_tag', '1')->order('show_order asc')->limit(18,19)->select();
        foreach ($url_list as $key => $value) {
			$url = $value['addr'];
			echo $url;
			$url_rule = Rules::where('id',$value['rules_id'])->find();
			// dump($url_rule);exit;
			$rules_level = $url_rule->getData('level');
			$rules_name = $url_rule['name'];
			$rules_l1 = $url_rule['l1_rule'];
			$l2_title = $url_rule['l2_title'];
			$l2_main = $url_rule['l2_main'];
			$l2_info = $url_rule['l2_info'];
            $l2_cont = $url_rule['l2_cont'];
            $url_s = explode('/', $url);
			$n_url = $url_s[0] . "//" . $url_s[2] . "/";
			$array = @get_headers($url,1); 
			
			echo "<p></p>";
				// $html->clear();
        }
    }
}
