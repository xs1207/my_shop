<?php

    namespace app\admin\validate;
    use think\Validate;
    class Admin extends Validate{
        protected $rule=[
            'admin_name'=>'require|checkName',
            'admin_pwd'=>'require|checkPwd',
            'admin_email'=>'require|email',
            'admin_tel'=>'require|checkTel'
        ];
        protected $message=[
            'admin_name.require'=>"账号不能为空",
            'admin_name.unique'=>'账号已存在',
            'admin_pwd.require'=>'密码不能为空',
            'admin_email.require'=>'邮箱不能为空',
            'admin_email.email'=>'邮箱格式不正确',
            'admin_tel.require'=>'电话不能为空'

        ];
        // 验证场景
        protected $scene=[
            'edit'=>['admin_name','admin_email','admin_tel'],
            'add'=>['admin_name','admin_pwd','admin_email','admin_tel']
        ];
        // 自定义 验证用户名
        public function checkName($value,$rule,$data){
            $reg='/^[a-z_]\w{3,11}$/i';
            if(!preg_match($reg,$value)){
                fail('账号由数字、字母、下划线组成，不能数字开头，4-12位') ;
            }else{
                if(empty($data['admin_id'])){
                    $where=[
                        'admin_name'=>$value
                    ];
                }else{
                    $where=[
                        'admin_id'=>['neq',$data['admin_id']],
                        'admin_name'=>$value
                    ];
                }
                $arr=model('admin')->where($where)->find();
                if(!empty($arr)){
                    fail("账号已存在");
                }else{
                    return true;
                }
                
            }
        }
        // 自定义 验证密码
        public function checkPwd($value,$rule,$data){
            $reg='/^.{6,16}$/';
            if(!preg_match($reg,$value)){
                fail('密码必须在6-16位之间');
            }else{
                return true;
            }
        }
        // 自定义 验证电话
        public function checkTel($value,$rule,$data){
            $reg='/^1[3-9]\d{9}$/';
            if(!preg_match($reg,$value)){
                fail('请输入正确的手机号') ;
            }else{
                return true;                                                
            }
        }
    }
?>