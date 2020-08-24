<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Rules;
use app\index\model\Supers;
use app\index\model\SuperLogs;

class GrabRules extends Supers
{
    public function index()
    {
        return view();
    }

    public function data()
    {
        $data = GrabRules::order("show_order asc")->select();
        echo json_encode($data);
    }

    public function add()
    {
        if (input('post.add') == 'add') {
            $data = request()->except('add');
            $data = nd($data);
			$res = GrabRules::insert($data);
			// var_dump($res)
            if ($res) {
                $this->assign([
                    'message' => '添加成功',
                    'waitSecond' => 2,
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["rule_name"] . '&nbsp' . '添加成功', "", "1");
                SuperLog::insert($log_i);
            } else {
                $this->assign([
                    'error' => '添加失败',
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["rule_name"] . '&nbsp' . '添加失败', "", "1");
                SuperLog::insert($log_i);
            }
        }

        $res_num = GrabRules::max("show_order");
        $this->assign('res_num', $res_num);
        return view();
    }

    public function edit($id)
    {
        $data = GrabRules::where('id', $id)->find();
        // dump($data);exit;
        if (input('post.sub') == 'sub') {
            $upd = request()->except('id,sub');
            $upd['updated_at'] = date("Y-m-d,H:i:s");
            $res = GrabRules::where('id', $id)->update($upd);
            // echo GrabRules::getLastSql();exit;
            if ($res) {
                $this->assign([
                    "message" => "修改成功！",
                    "waitSecond" => 2,
                ]);
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["rule_name"] . '&nbsp' . '修改成功', "", "3");
                SuperLog::insert($log_i);
            } else {
                $this->assign('errors', '修改失败！');
                $log_i = rizhi(session('super_name'), '抓取规则' . '&nbsp' . $data["rule_name"] . '&nbsp' . '修改失败', "", "3");
                SuperLog::insert($log_i);
            }
        }
        $this->assign('data', $data);
        $this->assign('id', $id);
        return view();
    }

    public function delete($id)
    {
        $res = GrabRules::where("id=$id")->delete();
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
