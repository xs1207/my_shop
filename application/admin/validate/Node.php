<?php
    namespace app\admin\validate;
    use think\Validate;
    class Node extends Validate{
        protected $rule=[
            'node_name'=>'require|checkName',
        ];
        protected $message=[
            'node_name.require'=>"节点名称不能为空",
        ];
        protected $scene=[
            "add"=>["node_name"],
            "edit"=>['node_name'],
        ];
        // 验证分类名称的唯一性
        public function checkName($value,$rule,$data){
                if(empty($data['node_id'])){
                    $where=[
                        'node_name'=>$value
                    ];
                }else{
                    $where=[
                        'node_id'=>['neq',$data['node_id']],
                        'node_name'=>$value
                    ];
                }
                $arr=model('Node')->where($where)->find();
                if(!empty($arr)){
                    fail("节点名称已存在");
                }else{
                    return true;
                }
                
            }

    }
 ?>