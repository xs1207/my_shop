<?php
namespace app\index\controller;
use think\Controller;
class User extends Common{
    public function index(){
        //检测是否登录
        if(!$this->checkUserLogin()){
            $this->error('请先登录',url('Login/login'));
        }
        return view();
    }
}
?>