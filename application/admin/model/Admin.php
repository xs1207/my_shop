<?php
    namespace app\admin\model;
    use think\Model;
    class Admin extends Model{
        // protected $autoWriteTimestamp = true;
        protected $updateTime = false;
        protected $insert=['salt'];
        protected $salt;
        // 修改器处理密码
        public function setAdminPwdAttr($value){
            // 生成盐值
            $this->salt=$salt=createSalt();
            return createPwd($value,$salt);
        }
        public function setSaltAttr(){
            return $this->salt;
        }

    }
?>