<?php
    namespace app\index\model;
    use \think\Model;
    class User extends Model{
        //定义时间戳字段名
        protected $updateTime=false;

        // 密码修改器
        public function setUserPwdAttr($value){
            return md5($value);
        }

    }
?>








