<?php
    namespace app\index\model;
    use \think\Model;
    class Score extends Model{
        //定义时间戳字段名
        protected $updateTime="utime";
        protected $createTime='ctime';

    }
?>