<?php
    namespace app\admin\validate;
    use think\Validate;
    class Role extends Validate{
        protected $rule=[
            'role_name'=>'require|checkName',
        ];
        protected $message=[
            'role_name.require'=>"节点名称不能为空",
        ];
        protected $scene=[
            "add"=>["role_name"],
            "edit"=>['role_name'],
        ];
        // 验证分类名称的唯一性
        public function checkName($value,$rule,$data){
                if(empty($data['role_id'])){
                    $where=[
                        'role_name'=>$value
                    ];
                }else{
                    $where=[
                        'role_id'=>['neq',$data['role_id']],
                        'role_name'=>$value
                    ];
                }
                $arr=model('role')->where($where)->find();
                if(!empty($arr)){
                    fail("角色名称已存在");
                }else{
                    return true;
                }
                
            }

    }
 ?>