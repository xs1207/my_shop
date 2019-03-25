<?php
    namespace app\index\validate;
    use think\Validate;
    class User extends Validate{
        //定义规则
        protected $rule=[
            'user_email'=>'require|email|checkEmail|unique:user',
            'user_tel'=>'require|checkTel|unique:user',
            'user_code'=>'require|checkCode',
            'user_pwd'=>'require|checkPwd',
            'user_pwd1'=>'require|confirm:user_pwd',
        ];
        // 错误提示
        protected $message=[
            'user_email.require'=>'邮箱必填',
            'user_email.email'=>'请输入正确的邮箱格式',
            'user_email.unique'=>'邮箱已存在',
            'user_tel.require'=>'手机号必填',
            'user_tel.unique'=>'手机号码已存在',
            'user_code.require'=>'验证码必填',
            'user_pwd.require'=>'密码必填',
            'user_pwd1.require'=>'确认密码必填',
            'user_pwd1.confirm'=>'确认密码必须和密码保持一致',
        ];

        // 验证场景
        protected $scene=[
            'registerEmail'=>['user_email','user_code','user_pwd','user_pwd1'],
            'registerTel'=>['user_tel','user_code','user_pwd','user_pwd1']
            
        ];

        // 自定义邮箱验证
        public function checkEmail($value,$rul,$data){
            $email=session('emailInfo.email');
            if($value!=$email){
                fail("当前邮箱与发送验证码邮箱不一致") ;
            }else{
                return true;
            }
        }
        //自定义手机号
        public function checkTel($value,$rul,$data){
            $tel=session('emailInfo.tel');
            $reg='/^1[3-8]\d{9}$/';
            if(!preg_match($reg,$value)){
                return '请输入正确的手机号';
            }else if($value!=$tel){
                return '当前手机号与发送手机号不一致';
            }else{
                return true;
            }
        }

        // 验证码
        public function checkCode($value,$rul,$data){
            $code=session('emailInfo.code');
            // dump($code);die;
            $time=session('emailInfo.time');
            if($code!=$value){
                fail("验证码有误") ;
            }else if(time()-$time>300){
                fail("验证码已过期，五分钟输入有效") ;
            }else{
                return true;
            }
        }

        
        //验证密码
        public function checkPwd($value,$rule,$data){
            $reg='/^[a-z0-9]{6,}$/';
            if(!preg_match($reg,$value)){
                fail('密码由数字，字母组成；6位及以上') ;
            }else{
                return true;
            }
        }

        
    }
?>