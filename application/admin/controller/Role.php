<?php
    namespace app\admin\controller;
    use think\Exception;

    class Role extends Common{
        /**角色添加 */
        public function roleAdd(){
            if(checkRequest()){
                $data=input("post.");
                if(empty($data['node_id'])){
                    fail("至少选择一个节点");
                }

                // 验证器
                $validate=validate('Role');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }
                

                $role_model=model("Role");
                $role_model->startTrans();
                //角色表
                try{
                    $res=$role_model->allowField(true)->save($data);
                    if(empty($res)){
                        $role_model->rollback();
                        throw new \Exception("角色添加失败");
                    }
                    //角色派生表
                    $role_id=$role_model->role_id;
                    $power_model=model("Power");
                    foreach($data['node_id'] as $k=>$v){
                        $powerData[]=["role_id"=>$role_id,"node_id"=>$v];
                    }
                    //入库
                    $res1=$power_model->saveAll($powerData);
                    if(empty($res1)){
                        $role_model->rollback();
                        throw new \Exception("角色的权限添加失败");
                    }
                    $role_model->commit();
                    successly("添加成功");

                }catch(\Exception $e){
                    fail($e->getMessage());
                }

            }else{
                $nodeInfo=$this->getAllNode();
                $this->assign("nodeInfo",$nodeInfo);
                return view();
            }
        }


        /** 角色列表展示*/
        public function roleList(){
            $role_model=model("Role");
            $roleInfo=$role_model->select();
            $this->assign("roleInfo",$roleInfo);
            return view();
        }

        //角色修改
        public function roleUpdate(){
            if(checkRequest()){
                $data=input("post.");
                $where=["role_id"=>$data['role_id']];
                $role_model=model("Role");
                $role_model->startTrans();
                try{
                    //修改角色表--角色名称
                    $res=$role_model->allowField(true)->save($data,$where);
                    if($res===false){
                        $role_model->rollback();
                        throw new \Exception("角色修改失败");
                    }
                    //修改角色__节点表  派生表
                    $power_model=model("Power");
                    $res1=$power_model->where($where)->delete();
                    if($res1===false){
                        $role_model->rollback();
                        throw new \Exception("角色修改失败");
                    }
                    foreach($data['node_id'] as $k=>$v){
                        $powerData[]=["role_id"=>$data['role_id'],"node_id"=>$v];
                    }
                    $res2=$power_model->saveAll($powerData);
                    if($res2===false){
                        $role_model->rollback();
                        throw new \Exception("角色修改失败");
                    }
                    $role_model->commit();
                    successly("修改成功");

                }catch (\Exception $e){
                    fail ($e->getMessage());
                }
            }else{
                $role_id=input("get.role_id",0,"intval");
                if(empty($role_id)){
                    fail("请选择要修改的角色");
                }
                //根据角色ID查询当前角色信息
                $roleWhere=["role_id"=>$role_id];
                $roleInfo=model("Role")->where($roleWhere)->find();
                //查询出所有的节点新的
                $nodeInfo=$this->getAllNode();

                //查询出当前角色选择的节点id
                $power_model=model("Power");
                $selectNodeId=$power_model->where($roleWhere)->column('node_id');
                $this->assign("nodeInfo",$nodeInfo);
                $this->assign("roleInfo",$roleInfo);
                $this->assign("selectNodeId",$selectNodeId);
                return view();
            }
        }

        //获取所有的节点
        public function getAllNode(){
            $node_model=model("Node");
            $nodeInfo=$node_model->select();
            $nodeInfo=getNodeCateInfo($nodeInfo);
            return $nodeInfo;
        }

        //角色删除
        public function roleDel(){
            $role_id=input("post.role_id",0,"intval");
            $role_model=model("Role");
            $role_model->startTrans();
            try{
                if(empty($role_id)){
                    throw new \Exception("请选择要删除的角色");
                }
                $where=["role_id"=>$role_id];
                //删除角色信息
                $res=$role_model->where($where)->delete();
                if($res===false){
                    $role_model->rollback();
                    throw new \Exception("角色删除失败");
                }
                //删除角色节点  派生表信息
                $power_model=model("Power");
                $res1=$power_model->where($where)->delete();
                if($res1===false){
                    $role_model->rollback();
                    throw new \Exception("角色删除失败");
                }
                $role_model->commit();
                successly("删除成功");
            }catch (\Exception $e){
                fail($e->getMessage());
            }
        }
    }
?>