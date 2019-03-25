<?php
    namespace app\admin\controller;
    use think\Controller;
    class Common extends Controller{
        public function _initialize(){
            if(!session('?adminInfo')){
                $this->error('请先登录',url('login/login'));
            }
        
            /**查询当前用户所拥有的权限信息 */
            $nodeInfo=$this->getNodeData();
            $info=getRecursionNodeInfo($nodeInfo);
            $this->assign("Info",$info);
            //检测此管理员是否有权限访问此操作
            foreach($nodeInfo as $k=>$v){
                $node_id[]=$v['node_id'];
            }
            //print_r($node_id);die;
            $node_model=model("Node");
            $nodeWhere=[
                "controller_name"=>request()->controller(),
                "action_name"=>request()->action()
            ];
            $my_node_id=$node_model->where($nodeWhere)->value('node_id');
            if(!in_array($my_node_id,$node_id)){
                $this->error("权限不够",url('Index/index'));
            }
        }

        /** 获取当前管理员的节点信息*/
        public function getNodeData(){
            $admin_id= session('adminInfo.admin_id');
            $where=[
                "admin_id"=>$admin_id,
            ];
            $nodeInfo=model('Node')
                ->field("shop_node.*")
                ->alias('n')
                ->join('shop_power p','n.node_id=p.node_id')
                ->join('shop_role r','p.role_id=r.role_id')
                ->join('shop_admin_role a_r','r.role_id=a_r.role_id')
                ->where($where)
                ->select();
            $nodeInfo=collection($nodeInfo)->toArray();
            return $nodeInfo;

        }
    }
?>