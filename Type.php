<?php

namespace app\index\controller;

use app\index\model\Category;
use think\Controller;
use think\Request;
use app\index\model\SuperLogs;

class Type extends Supers
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
        $data = Category::order("show_order asc")->select();
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
            $res = Category::insert($data);
            if ($res) {
                $this->assign([
                    'message' => '添加成功',
                    'waitSecond' => 2,
                ]);
                $log_i = rizhi(session('super_name'), '分类' . '&nbsp;' . $data["name"] . '&nbsp;' . '添加成功', "", "1");
                SuperLogs::insert($log_i);
            } else {
                $this->assign([
                    'error' => '添加失败',
                ]);
                $log_i = rizhi(session('super_name'), '分类' . '&nbsp;' . $data["name"] . '&nbsp;' . '添加失败', "", "1");
                SuperLogs::insert($log_i);
            }
        }
        $res_num = Category::max("show_order");
        $this->assign('res_num', $res_num);
        return view();
    }

    /**
	 * 保存新建的资源
	 *
	 * @param  \think\Request  $request
	 * @return \think\Response
	 */
    public function edit($id)
    {
        $res_num = Category::max('show_order');
        $res = Category::where('id', $id)->find();
        if (input("post.add") == 'add') {
            $data = request()->except('id,add');
            $data['updated_at'] = date("Y-m-d,H:i:s");
            $rst = Category::where('id', $id)->update($data);
            if ($rst) {
                $this->assign([
                    "message" => "修改成功！",
                    "waitSecond" => 2,
                ]);
                $log_i = rizhi(session('super_name'), '分类' . '&nbsp;' . $data["name"] . '&nbsp;' . '修改成功', "", "3");
                SuperLogs::insert($log_i);
            } else {
                $this->assign("errors", "修改失败！");
                $log_i = rizhi(session('super_name'), '分类' . '&nbsp;' . $data["name"] . '&nbsp;' . '修改失败', "", "3");
                SuperLogs::insert($log_i);
            }
        }
        $this->assign([
            "id" => $id,
            "res" => $res,
            "res_num" => $res_num,
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
        $res = Category::where('id', $id)->delete();
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
}
