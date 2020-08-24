<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\Super;
use app\index\model\SuperLogs;

class Login extends Controller
{
    /**
	 * 显示资源列表
	 *
	 * @return \think\Response
	 */
    public function index()
    {
        if (input('post.add') == 'add') {
            // echo 111;
            $data = request()->except('add');
            if (!captcha_check($data['captcha'])) {
                $this->assign('errors', '验证码错误！');
            } else {
                $res = $this->validate($data, 'Super.login');
                if (!$res == true) {
                    $this->assign('errors', $res);
                } else {
                    $info = Super::where("username='{$data['username']}'")->find();
                    if (empty($info)) {
                        $this->assign('errors', '该用户不存在！');
                    } else {
                        if (md5($data['password']) != $info['password']) {
                            $this->assign('errors', '密码错误！');
                        } else {
                            $u_data['times'] = $info['times'] + 1;
                            $u_data['last_time'] = date("Y-m-d,H:i:s");
                            $u_data['last_ip'] = $_SERVER["REMOTE_ADDR"];
                            $upd = Super::where("id='{$info['id']}'")->update($u_data);
                            session('super_name', $info['realname']);
                            session('super_id', $info['id']);
                            // session('super_realname', $info['realname']);
                            $sql_cont = Super::getLastSql();
                            $log_i = rizhi(session('super_name'), '登录成功', $sql_cont, '3');
                            SuperLogs::insert($log_i);
                            $this->redirect(url("index/Index/index"));
                        }
                    }
                }
            }
		}
		// echo 11;exit;
        return view();
    }

    public function logout()
    {
        $log_i = rizhi(session('super_name'), '安全退出');
        SuperLogs::insert($log_i);
        session(null);
        $this->redirect('index/Login/index');
    }

    public function setpw()
    {
        if (session('?super_name') != 1 || session('?super_id') != 1) {
            $this->redirect('index/Login/index');
        } else {
            if (input('post.sub') == 'sub') {
                $data = request()->except('sub');
                $username = session('super_name');
                $info = Super::where("username='{$username}'")->find();
                //非空验证
                if (empty($data['old_pw']) && empty($data['password']) && empty($data['re_pw'])) {
                    $this->assign('error', '原密码、新密码和再次新密码均不能为空！');
                    $log_i = rizhi($username, '原密码、新密码和再次新密码均不能为空');
                    SuperLogs::insert($log_i);
                } else {
                    //原密码是否正确
                    if (md5($data['old_pw']) != $info['password']) {
                        $this->assign('error', '原密码错误！');
                        $log_i = rizhi($username, '原密码错误');
                        SuperLogs::insert($log_i);
                    } else {
                        //两次密码是否相同
                        if ($data['password'] != $data['re_pw']) {
                            $this->assign('error', '新密码和再次新密码不一致！');
                            $log_i = rizhi($username, '新密码和再次新密码不一致');
                            SuperLogs::insert($log_i);
                        } else {
                            //更新数据
                            $u_data['password'] = md5($data['password']);
                            $u_data['updated_at'] = date("Y-m-d,H:i:s");
                            $upd = Super::where("username='{$username}'")->update($u_data);
                            if ($upd) {
                                $sql_cont = Super::getLastSql();
                                $log_i = rizhi($username, '密码修改成功', $sql_cont, '3');
                                SuperLogs::insert($log_i);
                                session(null);
                                $this->assign([
                                    'message' => "密码修改成功！",
                                    'waitSecond' => 2,
                                ]);
                                // $this->redirect(url("admin/login/loginout"));
                            } else {
                                $this->assign([
                                    'msg' => "密码修改失败",
                                    'waitSecond' => 2,
                                ]);
                                $sql_cont = Super::getLastSql();
                                $log_i = rizhi($username, '密码修改失败', $sql_cont);
                                SuperLogs::insert($log_i);
                            }
                        }
                    }
                }
            }
            return view();
        }
    }
}
