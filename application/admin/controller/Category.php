<?php
    namespace app\admin\controller;
    use think\Controller;
    header('content-type:text/html; charset=utf-8');
    class Category extends Common{
        // 分类添加与验证器
        public function cateadd(){
            if(checkRequest()){
                $data=input('param.');
                // 验证器
                $validate=validate('category');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }

                // 入库
                $model=model('category');
                $res=$model->save($data);
                if($res){
                    successly('添加成功');
                }else{
                    fail('添加失败');
                }
            }else{
                $info=$this->getCateInfo();
                $this->assign('info',$info);
                return view();
            }
           
        }
        // 验证唯一性
        public function checkName(){
            $cate_name=input("param.cate_name");
            $model=model('cate');
            $where=[
                'cate_name'=>$cate_name
            ];
            $arr=$model->where($where)->find();
            if($arr){
                echo "no";
            }else{
                echo "ok";
            }
        }
        // 分类列表展示
        public function catelist(){
            $info=$this->getCateInfo();
            $this->assign('info',$info);
            return view();
        }
        // 获取分类的数据
        public function getCateInfo(){
            $model=model('category');          
            $data=$model->select();
            $info=getCateInfo($data);
            return $info;
        }
        // 即点即改
        public function cateUpdateField(){
            $value=input('post.value');
            $field=input('post.field');
            $cate_id=input('post.cate_id');
            $model=model('category');
            if($field=='cate_name'){
                $where=[
                    'cate_id'=>['neq',$cate_id],
                    'cate_name'=>$value
                ];
                $arr=$model->where($where)->find();
                if($arr){
                    fail('分类名称已存在');
                }
            }
            $where=[
                'cate_id'=>$cate_id
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
        // 删除
        public function catedel(){
            $cate_id=input('param.cate_id');

            // 此分类下是否有子类
            $cate=model('category');
            $cateWhere=[
                'pid'=>$cate_id
            ];
            $count=$cate->where($cateWhere)->count();
            if($count>0){
                fail('此分类下有子类或者商品，不能删除');
            }
            // 查询子分类下是否有商品
            $goods=model('goods');
            $goodswhere=[
                'cate_id'=>$cate_id
            ];
            $cot=$goods->where($goodswhere)->count();
            if($cot>0){
                fail('此分类下有子类或者商品，不能删除');
            }
            // 删除
            $res=$cate->where($goodswhere)->delete();
            if($res){
                successly('删除成功');
            }else{
                fail('删除失败');
            }
            
        }
        // 修改
        public function cateUpdate(){
            if(checkRequest()){
                $data=input('post.');
                // 验证器
                $validate=validate('category');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }
                $where=[
                    'cate_id'=>$data['cate_id']
                ];
                $res=model('category')->save($data,$where);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }
            }else{
                // 接收ID
                $cate_id=input('get.cate_id');
                // dump($cate_id);die;
                // 根据ID查询出来一条数据
                $where=[
                    'cate_id'=>$cate_id
                ];
                $arr=model('category')->where($where)->find();
                // echo model('category')->getLastSql();die;
                $info=$this->getCateInfo();
                $this->assign('info',$info);   
                $this->assign('arr',$arr);
                return view();
            }
            
        }
       
    }
?>