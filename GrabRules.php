<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Rules;
use app\index\model\Super;
use app\index\model\SuperLogs;

class GrabRules extends Supers
{
    public function index()
    {
        return view();
    }

    public function data()
    {
        $data = Rules::order("show_order asc")->select();
        echo json_encode($data);
    }

    public function add()
    {
        if (input('post.add') == 'add') {
            $data = request()->except('add');
			$data = nd($data);
			// var_dump($data);exit;
			$res = Rules::insert($data);
			
            if ($res) {
                $this->assign([
                    'message' => '添加成功',
                    'waitSecond' => 2,
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["name"] . '&nbsp' . '添加成功', "", "1");
                SuperLogs::insert($log_i);
            } else {
                $this->assign([
                    'error' => '添加失败',
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["name"] . '&nbsp' . '添加失败', "", "1");
                SuperLogs::insert($log_i);
            }
        }

        $res_num = Rules::max("show_order");
        $this->assign('res_num', $res_num);
        return view();
    }

    public function edit($id)
    {
        $data = Rules::where('id', $id)->find();
        // dump($data);exit;
        if (input('post.sub') == 'sub') {
            $upd = request()->except('id,sub');
            $upd['updated_at'] = date("Y-m-d,H:i:s");
            $res = Rules::where('id', $id)->update($upd);
            // echo Rules::getLastSql();exit;
            if ($res) {
                $this->assign([
                    "message" => "修改成功！",
                    "waitSecond" => 2,
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["name"] . '&nbsp' . '修改成功', "", "3");
                SuperLogs::insert($log_i);
            } else {
                $this->assign('errors', '修改失败！');
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["name"] . '&nbsp' . '修改失败', "", "3");
                SuperLogs::insert($log_i);
            }
        }
        $this->assign('data', $data);
        $this->assign('id', $id);
        return view();
    }

    public function delete($id)
    {
        $res = Rules::where("id=$id")->delete();
        if ($res) {
            $this->assign([
                "message" => "删除成功！",
                "waitSecond" => 2,
            ]);
        } else {
            $this->assign("error", "删除失败！");
        }
        return view('index');
    }
}
