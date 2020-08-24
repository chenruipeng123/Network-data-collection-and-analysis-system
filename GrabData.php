<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Data;
use app\index\model\Rules;
use app\index\model\Urls;
use app\index\model\Category;

class GrabData extends Supers
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
		$data = Urls::where('show_tag',1)->order('show_order asc')->select();
		foreach ($data as $key => $value) {
			$r_data = Rules::where('id',$value['rules_id'])->find();
			$data[$key]['rules_name'] = $r_data['name'];
            $data[$key]['rules_level'] = $r_data['level'];
		}
        echo json_encode($data);
    }

    public function data1($url_id)
    {
		$data1 = Data::where('url_id',$url_id)->order('created_at desc')->select();
		// dump($data1);exit;
		foreach ($data1 as $key => $value) {
			$c_data = Category::where('id',$value['c_id'])->find();
			$data1[$key]['c_name'] = $c_data['name'];
		}
		foreach ($data1 as $key => $value) {
			$u_data = Urls::where('id',$value['url_id'])->find();
			$data1[$key]['url_name'] = $u_data['name'];
		}
		// dump($data1);exit;
        echo json_encode($data1);
    }

	public function indexlayer($id)
	{
		$data = Data::where('id',$id)->find();
        $info = Urls::where('id', $data['url_id'])->find();
		$m_url = $info['addr'];
        // echo $m_url;exit;
		$cont = str_replace("src=\"/","src=\"$m_url",$data['cont']);
		// echo strstr($cont,'src');exit;
		$title = $data['title'];
		$time = $data['release_time'];
		$article = "<h2 align='center'>".$title."</h2>"."<br>".$cont;
		echo $article;
	}

    public function delete($id)
    {
        $res = Data::where('url_id', $id)->delete();

        if ($res) {
            $this->assign([
                "message" => "操作成功！",
                "waitSecond" => 2,
            ]);
        } else {
            $this->assign([
                "errors" => "操作失败！",
            ]);
        }
        return view('index');
    }

    public function delete1($id)
    {
        $res = Data::where('id', $id)->delete();

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


    public function edit($id)
    {
        $list = Category::where('show_tag', 1)->select();
        $res = Data::where('id', $id)->find();
        // $info = Urls::where('name', $res['url_name'])->find();
        // $m_url = $info['addr'];
        // $content = str_replace("src=\"/", "src=\"$m_url", $res['cont']);
        // echo $content;
        // exit;
        if (input('post.sub') == 'sub') {
            $upd = request()->except('id,sub');
            $upd['updated_at'] = date("Y-m-d,H:i:s");
            $res1 = Data::where('id', $id)->update($upd);
            // echo Rules::getLastSql();exit;
            if ($res1) {
                $this->assign([
                    "message" => "修改成功！",
                    "waitSecond" => 2,
                ]);
            } else {
                $this->assign('errors', '修改失败！');
            }
        }
        $this->assign([
            "id" => $id,
            "list" => $list,
            "res" => $res,
        ]);
        return view();
    }

    //导出为word文档
    public function export($id)
    {
		$row = Data::where('id', $id)->find();
        $info = Urls::where('id', $row['url_id'])->find();
        $m_url = $info['addr'];
		$content = str_replace("src=\"/","src=\"$m_url",$row['cont']); //给是相对路径的图片加上域名变成绝对路径,导出来的word就会显示图片了
		$word = "<h2 align='center'>".$row['title']."</h2>"."<br>".$content;
        // echo $content;
        // exit;
        $filename =  $row['title'] . '——' . $info['name'];
        // $filename = iconv('utf-8', 'gb2312', $filename); 
        header('pragma:public');
        header('Content-type:application/vnd.ms-word;charset=utf-8;name="' . $filename . '".doc');
        header("Content-Disposition:attachment;filename=$filename.doc"); //attachment新窗口打印inline本窗口打印
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'; //这句不能少，否则不能识别图片
        echo $html . $word . '</html>';
	}
	
	// 2019/3/27新增选项卡——组合查询数据
	public function s_index()
	{
		$category = Category::where('show_tag',1)-> select();
		$url = Urls::where('show_tag',1)-> select();

		$this->assign([
			'url' => $url,
			'category' => $category,
		]);
		return view();
	}

	public function s_data($start="",$end="",$sel_url="",$sel_category="")
	{
		if (empty($start)&&empty($end)) {
			if (empty($sel_url)) {
				if (empty($sel_category)) {
					$last = date('Y-m-d');
					$data = Data::where('created_at','like',$last.'%')->select();
				}else{
					$data = Data::where('c_id',$sel_category)->select();
				}
			}else{
				if (empty($sel_category)) {
					$data = Data::where('url_id',$sel_url)->select();
				}else{
					$data = Data::where(['url_id'=>$sel_url,'c_id'=>$sel_category])->select();
				}
			}
		}else{
			//分类为空
			if (empty($sel_category)) {
				if (empty($sel_url)) {
					$data = Data::query("select * from data where `created_at`>='$start' and `created_at`<='$end'");
				}else {
					$data = Data::query("select * from data where `created_at`>='$start' and `created_at`<='$end' and `url_id`='$sel_url'");					
				}
			}else{
				if (empty($sel_url)) {
					$data = Data::query("select * from data where `created_at`>='$start' and `created_at`<='$end' and `c_id`='$sel_category'");					
				}else{
					$data = Data::query("select * from data where `created_at`>='$start' and `created_at`<='$end' and `c_id`='$sel_category' and `url_id`='$sel_url'");										
				}
			}
		}
		foreach ($data as $key => $value) {
			$url = Urls::where('id',$value['url_id'])->find();
			$data[$key]['url_name'] = $url['name'];
		}
		foreach ($data as $key => $value) {
			$category = Category::where('id',$value['c_id'])->find();
			$data[$key]['c_name'] = $category['name'];
		}
		echo json_encode($data);
	}

}


