<?php
    namespace app\index\controller;
    use think\Controller;
    class Test extends Common{
        function test(){
            // $res=sendEmail('18252180309@163.com','561354');
            // dump($res);
             $res=sendSms('18237243730','12071207');
             echo $res->Code;
        }
        
        
        
    }
?>