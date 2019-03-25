<?php
    namespace app\admin\controller;
    use think\Controller;
    class Login extends Controller{
        // 登录
        public function login(){
            if(checkRequest()){
                $admin_name=input('post.admin_name');
                $admin_pwd=input('post.admin_pwd');
                $mycode=input('post.mycode');
                // 验证非空
                if(empty($admin_name)||empty($admin_pwd)||empty($mycode)){
                    fail('必填项不能为空') ;
                }
                // 验证码是否错误
                if(!captcha_check($mycode)){
                    fail("验证码有误") ;
                }
                // 检测账号是否错误
                $model=model('admin');
                $where=[
                    'admin_name'=>$admin_name
                ];
                $arr=$model->where($where)->find();
                //dump($arr);exit;
                if(!empty($arr)){
                    $salt=$arr['salt'];
                    $pwd=createPwd($admin_pwd,$salt);
                    if($pwd==$arr['admin_pwd']){
                        // 存管理员信息 id  名字 session中
                        session('adminInfo',['admin_id'=>$arr['admin_id'],'admin_name'=>$arr['admin_name']]);
                        // 改最后一次登录的时间
                        $updateWhere=[
                            'admin_id'=>$arr['admin_id']
                        ];
                        $updateInfo=[
                            'last_login_time'=>time(),
                            'last_login_ip'=>request()->ip()
                        ];
                        $model->save($updateInfo,$updateWhere);
                        // 提示文字  登陆成功
                        successly("登陆成功");
                    }else{
                        fail("账号或密码错误") ;
                    }
                }else{
                    fail("账号或密码错误") ;
                }
            }else{
                $this->view->engine->layout(false); 
                return view();
            }
            
        }
        // 退出
         public function quit(){
            // 清楚session
            session('adminInfo',null);
            $this->redirect('login/login');
        } 
    }
?>