<?php
    namespace app\admin\validate;
    use think\Validate;
    class Links extends Validate{
        protected $rule=[
            'name'=>'require|checkName',
            'url'=>'require|url'
        ];
        protected $message=[
            'name.require'=>"分类名称不能为空",
            'url.require'=>"友链网址不能为空",
            'url.url'=>"请输入正确的友链地址"
        ];
         // 验证分类名称的唯一性
         public function checkName($value,$rule,$data){
            if(empty($data['id'])){
                $where=[
                    'name'=>$value
                ];
            }else{
                $where=[
                    'id'=>['neq',$data['id']],
                    'name'=>$value
                ];
            }
            $arr=model('links')->where($where)->find();
            if(!empty($arr)){
                fail("分类名称已存在");
            }else{
                return true;
            }
            
        }
    }
 ?>