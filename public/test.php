<?php
    function res($str){
        $s="";//定义一个空的变量
        $len=strlen($str);//查看变量的长度
        for($i=$len-1;$i>=0;$i--){
            $s.=$str{$i};
        }
        return $s;

    }
    $str="0123456789";
    echo res($str);

?>