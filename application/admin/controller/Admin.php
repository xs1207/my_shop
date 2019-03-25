<?php
    namespace app\admin\controller;
    use think\Controller;
    header('content-type:text/html; charset=utf-8');
    class Admin extends Common{
        // 验证器验证  以及添加入库
        public function adminadd(){
            if(checkRequest()){
                $data=input('param.');
                // dump($data);
                // 验证器
                $validate=validate('Admin');
                $result=$validate->scene('add')->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }
                //保存到数据库
                $model=model('admin');
                $result=$model->save($data);
                if($result){
                   successly('添加成功');
                }else{
                    fail('添加失败');
                }
            }else{
                return view();
            }  
        }
        // 验证管理员唯一性
        public function checkName(){
            $admin_name=input("param.admin_name");
            $model=model('admin');
            $where=[
                'admin_name'=>$admin_name
            ];
            $arr=$model->where($where)->find();
            if($arr){
                echo "no";
            }else{
                echo "ok";
            }
        }
        // 列表展示
        public function adminlist(){
            return view();
        }
        // 获取管理员数据  展示 加 分页
        public function getAdminInfo(){
            // 接收 页码  每页显示条数
            $page=input('get.page');
            $limit=input('get.limit');
            $data=model('admin')->page($page,$limit)->select();
            $count=model('admin')->count();
            $info=[
                'code'=>0,
                'msg'=>'',
                'count'=>$count,
                'data'=>$data
            ];
            echo json_encode($info);
        }
        // 即点即改
        public function adminFiledupdate(){
            $value=input('post.value');
            $field=input('post.field');
            $admin_id=input('post.admin_id');
            $model=model('admin');
            if($field=='admin_name'){
                $where=[
                    'admin_id'=>['neq',$admin_id],
                    'admin_name'=>$value
                ];
                $arr=$model->where($where)->find();
                if($arr){
                    fail('账号已存在');exit;
                }
            }
            $updatewhere=[
                'admin_id'=>$admin_id
            ];
            $data=[
                $field=>$value
            ];
            $res=$model->save($data,$updatewhere);
            if($res){
                successly('修改成功');
            }else{
                fail('修改失败');
            }

        }
        // 删除
        public function adminDel(){
            $admin_id=input('post.admin_id');
            $admin_role_model=model("AdminRole");
            $admin_role_model->startTrans();
            try{
                $where=[
                    'admin_id'=>$admin_id
                ];
                $res=$admin_role_model->where($where)->delete();
                if($res===false){
                    $admin_role_model->rollback();
                    throw new \Exception("删除失败");
                }

                $res1=model('Admin')->where($where)->delete();
                if($res1===false){
                    $admin_role_model->rollback();
                    throw new \Exception("删除失败");
                }
                $admin_role_model->commit();
                successly("删除成功");
            }catch (\Exception $e){
                fail($e->getMessage());
            }
            
        }
        // 修改
        public function adminUpdate(){
            if(checkRequest()){
                $data=input('post.');
                $admin_id=input('post.admin_id');
                //dump($data);exit;
                // 验证器
                $validate=validate('admin');
                if(!$result=$validate->scene('edit')->check($data)){
                    fail($validate->getError());
                }

                $model=model('admin');
                $where=[
                    'admin_id'=>$admin_id
                ];
                $res=$model->save($data,$where);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }

            }else{
                $admin_id=input('get.admin_id');
                $where=[
                    'admin_id'=>$admin_id
                ];
                $data=model('admin')->where($where)->find();
                $this->assign('data',$data);
                return view();
            }
           
        }
        // 修改密码
        public function adminUpdatePwd(){
            if(checkRequest()){
                $old_pwd=input('post.old_pwd');
                $new_pwd1=input('post.new_pwd1');
                $new_pwd2=input('post.new_pwd2');
                $model=model('admin');
                if(empty($old_pwd)){
                    fail("原密码必填");
                }else{
                    //获取到当前登录用户的正确信息
                    $admin_id=session('adminInfo.admin_id');
                    $where=[
                        'admin_id'=>$admin_id
                    ];
                    $info=$model->where($where)->find();
                    $admin_pwd=createPwd($old_pwd,$info['salt']);
                    if($admin_pwd!=$info['admin_pwd']){
                        echo "原密码有误";
                    }
                }
                //验证新密码
                if(empty($new_pwd1)){
                    echo "新密码必填";exit;
                }else if(strlen($new_pwd1)<6 && strlen($new_pwd1)>16){
                    fail("密码必须为6-16位") ;
                }
                if($new_pwd2!=$new_pwd1){
                    fail("确认密码必须和密码一致");
                }
                // 修改密码
                $updateInfo=[
                    'admin_pwd'=>createPwd($new_pwd1,$info['salt'])
                ];
                $res=$model->where($where)->update($updateInfo);
                if($res){
                    successly('修改成功');
                }else{
                    fail('修改失败');
                }
            }else{
                return view();
            }
            
        }
        //给管理员赋予角色
        public function giveRole(){
            if(checkRequest()){
                $data=input("post.");
                
                $admin_role_model=model("AdminRole");
                $admin_role_model->startTrans();
                try{
                    if(empty($data['role_id'])){
                        $admin_role_model->rollback();
                        throw new \Exception("角色必选");
                    }
                    // 管理员id删除派生表数据
                    $where=["admin_id"=>$data['admin_id']];
                    $res=$admin_role_model->where($where)->delete();
                    if($res===false){
                        $admin_role_model->rollback();
                        throw new \Exception("操作失败");
                    }


                    //添加派生表数据
                    $insertData=[];
                    foreach($data['role_id'] as $k=>$v){
                        $insertData[]=[
                            "admin_id"=>$data['admin_id'],
                            "role_id"=>$v
                        ];
                    }
                    $res1=$admin_role_model->saveAll($insertData);
                    if($res1===false){
                        $admin_role_model->rollback();
                        throw new \Exception("操作失败");
                    }

                    $admin_role_model->commit();
                    successly("操作成功");
                }catch( \Exception $e){
                    fail($e->getMessage());
                }
                
                
            }else{
                $admin_id=input("get.admin_id");

                //查询出所有的角色
                $model=model("Role");
                $roleInfo=collection($model->select())->toArray();

                //查询当前管理员所选择的角色id
                $where=[
                    "admin_id"=>$admin_id
                ];
                $admin_role_model=model("AdminRole");
                $selectRoleId=$admin_role_model->where($where)->column('role_id');


                $this->assign("roleInfo",$roleInfo);
                $this->assign("admin_id",$admin_id);
                $this->assign("selectRoleId",$selectRoleId);
                return view();
            }

        }
    }
?>