<?php
    namespace app\admin\controller;
    class Node extends Common{
        /**添加节点 */
        public function nodeAdd(){
            if(checkRequest()){
                
                $data=input("post.");
                // 验证器
                $validate=validate('Node');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }

                
                $node_model=model("Node");
                $res=$node_model->save($data);
                if($res){
                    successly("添加成功");
                }else{
                    fail("添加失败");
                }
            }else{
                $orderWhere=[
                    'pid'=>0
                ];
                $node_model=model("Node");
                $nodeInfo=$node_model->where($orderWhere)->select();
                $this->assign("nodeInfo",$nodeInfo);
                return view();
            }
        }
        // 验证唯一性
        public function checkName(){
            $node_name=input("param.node_name");
            $model=model('Node');
            $where=[
                'node_name'=>$node_name
            ];
            $arr=$model->where($where)->find();
            if($arr){
                echo "no";
            }else{
                echo "ok";
            }
        }

        // 分类列表展示
        public function nodeList(){
            $info=$this->getNodeInfo();
            $this->assign('info',$info);
            return view();
        }
        // 获取分类的数据
        public function getNodeInfo(){
            $model=model('Node');          
            $data=$model->select();
            $info=getNodeInfo($data);
            return $info;
        }
        /**即点即改 */
        public function nodeUpdateField(){
            $value=input('post.value');
            $field=input('post.field');
            $node_id=input('post.node_id');
            $model=model('Node');
            if($field=='node_name'){
                $where=[
                    'node_id'=>['neq',$node_id],
                    'node_name'=>$value
                ];
                $arr=$model->where($where)->find();
                if($arr){
                    fail('分类名称已存在');
                }
            }
            $where=[
                'node_id'=>$node_id
            ];
            $data=[
                $field=>$value
            ];
            $res=$model->save($data,$where);
            if($res===false){
                fail('修改失败');
            }else{
                successly('修改成功');
            }
            
        }

        /**删除 */
        public function nodeDel(){
            $node_id=input("post.node_id");
            // 查看此分类下是否有子类
            $model=model("Node");
            $where=[
                "pid"=>$node_id
            ];
            $count=$model->where($where)->count();
            if($count>0){
                fail("此节点下有分类，不能删除");
            }
            
            // 删除
            $nodeWhere=['node_id'=>$node_id];
            $res=$model->where($nodeWhere)->delete();
            if($res){
                successly('删除成功');
            }else{
                fail('删除失败');
            }
        }

        /**修改 */
        public function nodeUpdate(){
            if(checkRequest()){
                $data=input('post.');
                // dump($data);die;
                // 验证器
                $validate=validate('Node');
                $result=$validate->scene('edit')->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }
                $where=[
                    'node_id'=>$data['node_id']
                ];
                $res=model('Node')->save($data,$where);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }
            }else{
                $node_id=input("get.node_id");
                $orderWhere=['node_id'=>$node_id];
                $arr=model("Node")->where($orderWhere)->find();
                $nodeInfo=$this->getNodeInfo();

                $this->assign("arr",$arr);
                $this->assign("nodeInfo",$nodeInfo);
                return view();
            }
        }
    }
?>