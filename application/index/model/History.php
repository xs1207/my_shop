<?php
    namespace app\index\model;
    use \think\Model;
    class History extends Model{
        //定义时间戳字段名
        protected $updateTime=false;
        protected $createTime='ctime';

    }
?>