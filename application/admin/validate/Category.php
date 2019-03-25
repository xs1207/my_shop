<?php
    namespace app\admin\validate;
    use think\Validate;
    class Category extends Validate{
        protected $rule=[
            'cate_name'=>'require|checkName',
        ];
        protected $message=[
            'cate_name.require'=>"分类名称不能为空",
        ];

        // 验证分类名称的唯一性
        public function checkName($value,$rule,$data){
                if(empty($data['cate_id'])){
                    $where=[
                        'cate_name'=>$value
                    ];
                }else{
                    $where=[
                        'cate_id'=>['neq',$data['cate_id']],
                        'cate_name'=>$value
                    ];
                }
                $arr=model('category')->where($where)->find();
                if(!empty($arr)){
                    fail("分类名称已存在");
                }else{
                    return true;
                }
                
            }

    }
 ?>