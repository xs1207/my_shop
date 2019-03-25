<?php
    namespace app\admin\controller;
    use think\Controller;
    class Links extends Common{
        // 友链的添加与验证器
        public function linksadd(){
            if(checkRequest()){
                $data=input('post.');
                // dump($data);die;
                // 验证器
                $validate=validate('links');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }

                // 入库
                $model=model('links');
                $res=$model->save($data);
                if($res){
                    successly('添加成功');
                }else{
                    fail('添加失败');
                }
            }else{
                return view();
            }
            
        }
        // 验证唯一性
        public function checkName(){
            $name=input("param.name");
            $model=model('links');
            $where=[
                'name'=>$name
            ];
            $arr=$model->where($where)->find();
            // dump($arr);die;
            if($arr){
                echo "no";
            }else{
                echo "ok";
            }
        }
        // 展示
        public function linkslist(){
            return view();
        }
        // 获取管理员数据  展示 加 分页
        public function linksInfo(){
            // 接收 页码  每页显示条数
            $page=input('get.page');
            $limit=input('get.limit');
            $data=model('links')->page($page,$limit)->select();
            $count=model('links')->count();
            $info=[
                'code'=>0,
                'msg'=>'',
                'count'=>$count,
                'data'=>$data
            ];
            echo json_encode($info);
        }
        // 即点即改
        public function linksFieldUpdate(){
            // 接收list页面得到的值
            $value=input('post.value');//值
            $id=input('post.id');//id
            $field=input('post.field');//字段
            if($field=='name'){
                $where=[
                    'id'=>['neq',$id],
                    'name'=>$value
                ];
                $arr=model('links')->where($where)->find();
                if($arr){
                    fail('友链名称已存在');
                }
            }
            $updatewhere=[
                'id'=>$id
            ];
            $data=[
                $field=>$value
            ];
            $res=model('links')->save($data,$updatewhere);
            if($res){
                successly('修改成功');
            }else{
                fail('修改失败');
            }
            
        }
        // 删除
        public function linksdel(){
            $id=input('post.id');
            $where=[
                'id'=>$id
            ];
            $res=model('links')->where($where)->delete();
            if($res){
                successly('删除成功');
            }else{
                fail('删除失败');
            }
        }
        // 修改
        public function linksUpdate(){
            if(checkRequest()){
               $data=input('post.');
            //    dump($data);die;
               $id=input('id');
            //    dump($id);die;
            //    验证器
               $validate=validate('links');
                $reslut=$validate->check($data);
                if(!$reslut){
                    fail($validate->getError());
                }
                $where=[
                    'id'=>$id
                ];
                $res=model('links')->save($data,$where);
                // echo model('links')->getLastSql();die;
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }
            }else{
                $id=input('get.id');
                $where=[
                    'id'=>$id
                ];
                $data=model('links')->where($where)->find();
                $this->assign('data',$data);
                return view();
            }
        }
    }
?>