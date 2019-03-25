<?php
    namespace app\index\controller;
    use \think\Controller;
    class Login extends Common{
        // 登录
        public function login(){
            if(checkRequest()){
                // $account=input('post.account');
                // $user_pwd=input('post.user_pwd');
                // $remember_me=input('post.remember_me');
                $data=input('post.');
                // 验证
                if(empty($data['account'])){
                    fail("手机号或邮箱必填");
                }
                if(empty($data['user_pwd'])){
                    fail("密码必填");
                }

                // 处理条件
                $account=$data['account'];
                if(substr_count($account,'@')){
                    $where=[
                        'user_email'=>$account
                    ];
                }else{
                    $where=[
                        'user_tel'=>$account
                    ];
                }
                $userInfo=model('user')->where($where)->find();
                
                // 查询到账号
                $time=time();//当前时间
                $last_error_time=$userInfo['last_error_time'];//最后一次出错误时间
                $error_num=$userInfo['error_num'];//出现的次数
                $update_where=[
                    'user_id'=>$userInfo['user_id']
                ];
                if(!empty($userInfo)){
                    if(md5($data['user_pwd'])==$userInfo['user_pwd']){
                        // 错误次数
                        if($error_num>=5 && $time-$last_error_time<3600){
                            $open_time=60-(ceil(($time-$last_error_time)/60));
                            fail('手机号或密码已锁定,'.$open_time.'分钟之后可以登录');
                        }
                        //次数清零  时间清空
                        $updateInfo=[
                            'error_num'=>0,
                            'last_error_time'=>null
                        ];
                        $res=model('user')->where($where)->update($updateInfo);
                        
                        //判断是否记住密码   是否账号密码存cookie10天
                            /**注意cookie不能存储数组形式，要一个一个存 */
                        if($data['remember_me']=='true'){
                            $day=60*60*24*10;
                            cookie('account',$data['account'],$day);
                            cookie('user_pwd',$data['user_pwd'],$day);
                        }
                        // 用户信息存session
                        $sessionInfo=[
                            'user_id'=>$userInfo['user_id'],
                            'account'=>$account
                        ];
                        session('userInfo',$sessionInfo);
                        
                        //同步浏览记录到数据库
                        $this->asyncHistory();

                        //同步购物车
                        $this->asyncCart();
                        
                        successly('登陆成功');
                    }else{
                        // 距离上次错误时间超过一个小时  次数改为1 时间为当前时间
                        if($time-$last_error_time>3600){
                            $updateInfo=[
                                'error_num'=>1,
                                'last_error_time'=>$time
                            ];
                            // 改
                            $res=model('user')->where($where)->update($updateInfo);
                            if($res){
                                fail('你还可以输入四次');
                            }
                            
                        }else{
                            // 如果错误次数>=5次 提示已锁定
                            if($error_num>=5){
                                $second=60-(ceil(($time-$last_error_time)/60));
                                fail("邮箱或手机号已锁定,{$second}分钟后可以登录");
                            }else{
                                //次数累计
                                $num=$error_num+1;
                                $updateInfo=[
                                    'error_num'=>$num,
                                    'last_error_time'=>$time
                                ];
                                // 改
                                $res=model('user')->where($where)->update($updateInfo);
                                if($res){
                                    if($num==5){
                                        fail("邮箱或手机号已锁定,一小时后登陆");
                                    }else{
                                        fail('你还可以输入'.(5-$num).'次');                                        
                                    }

                                }

                            }
                        }
                    }
                    
                }else{
                    fail("账号不存在");
                }

            }else{
                $str=cookie('cookieInfo');
                $userInfo=unserialize($str);
                $this->view->engine->layout(false);
                $this->assign('userInfo',$userInfo);
                return view();
            }
           
        }
        // 注册
        public function register(){
            if(checkRequest()){
                $data=input('post.');
                $type=input("get.type");
                // dump($type);exit;
                // 验证器
                $validate=validate('user');
                if($type==1){
                    $res=$validate->scene('registerEmail')->check($data);
                    $account=$data['user_email'];
                }else{
                    $res=$validate->scene('registerTel')->check($data);
                    $account=$data['user_tel'];
                }
                
                if($res){
                    // 添加入库
                    $user=model('user');
                    $res=$user->allowField(true)->save($data);
                    if($res){
                        $userInfo=[
                            'user_id'=>$user->user_id,
                            'account'=>$account
                        ];
                        session('userInfo',$userInfo);
                        successly('注册成功');
                    }else{
                        fail('注册失败');
                    }
                }else{
                    fail($validate->getError());
                }
            }else{
                $this->view->engine->layout(false);
                return view(); 
            }           
        }
        // 邮箱发送验证码
        public function sendEmail(){
            $email=input('post.email');
            $where=[
                'user_email'=>$email
            ];
            $res=model('user')->where($where)->find();
            if($res){
                fail('邮箱已存在');
            }
            // 随机生成发送的验证码
            $code=createCode();
            $res=sendEmail($email,$code);

            if($res){
                $emailInfo=[
                    'email'=>$email,
                    'code'=>$code,
                    'time'=>time()
                ];
                session('emailInfo',$emailInfo);
                successly('发送成功');
            }else{
                fail('发送失败');
            }
        }
        // 手机号发送验证码
        public function sendTel(){
            $tel=input('post.tel');
            $where=[
                'user_tel'=>$tel
            ];
            $res=model('user')->where($where)->find();
            if($res){
                fail('手机号已存在');
            }
            //随机生成发送的验证码
            $code=createCode();
            $res=sendSms($tel,$code);
            
            if($res){
                $emailInfo=[
                    'tel'=>$tel,
                    'code'=>$code,
                    'time'=>time()
                ];
                session('emailInfo',$emailInfo);
                successly('发送成功');
            }else{
                fail('发送失败');
            }
        }
        // 退出
        public function quit(){
            session('userInfo',null);
            cookie('account',null);
            cookie('user_pwd',null);
            $this->redirect('Index/index');
        }
    }
?>