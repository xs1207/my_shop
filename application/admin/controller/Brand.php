<?php
    namespace app\admin\controller;
    use think\Controller;
    class Brand extends Common{
        // 品牌添加
        public function brandadd(){
            if(checkRequest()){
                $data=input('post.');
                // 验证器验证
                $validate=validate('Brand');
                $result=$validate->scene('add')->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }

                // 入库
                $model=model('brand');
                $res=$model->allowField(true)->save($data);
                if($res){
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
            $brand_name=input("param.brand_name");
            $model=model('brand');
            $where=[
                'brand_name'=>$brand_name
            ];
            $arr=$model->where($where)->find();
            if($arr){
                echo "no";
            }else{
                echo "ok";
            }
        }
        // 品牌展示
        public function brandlist(){
            return view();
        }
        // 获取管理员数据  展示 加 分页
        public function brandInfo(){
            // 接收 页码  每页显示条数
            $page=input('get.page');
            $limit=input('get.limit');
            $model=model('brand');
            $data=$model->getbrandInfo($page,$limit);
            $count=model('brand')->count();
            $info=[
                'code'=>0,
                'msg'=>'',
                'count'=>$count,
                'data'=>$data
            ];
            echo json_encode($info);
        }
        // 即点即改
        public function brandFiledUpdate(){
            $value=input('post.value');
            
            $field=input('post.field');
            $brand_id=input('post.brand_id');
            
            $model=model('brand');
            if($field=='brand_name'){
                $where=[
                    'brand_id'=>['neq',$brand_id],
                    'brand_name'=>$value
                ];
                $arr=$model->where($where)->find();
                if($arr){
                    fail('品牌名称已存在');
                }
            }
            $updatewhere=[
                'brand_id'=>$brand_id
            ];
            $data=[
                $field=>$value
            ];
            $res=$model->save($data,$updatewhere);
            //echo $model->getLastSql();
            if($res){
                successly('修改成功');
            }else{
                fail('修改失败');
            }
        }
        // 删除
        public function brandDel(){
            $brand_id=input('post.brand_id');
            // 判断品牌下是否有商品
            $model=model('goods');
            $where=[
                'brand_id'=>$brand_id
            ];
            $con=$model->where($where)->count();
            if($con>0){
                fail('此品牌下有商品，不能删除');
            }
            $res=model('brand')->where($where)->delete();
            if($res){
                successly('删除成功');
            }else{
                fail('删除失败');
            }
        }
        // 修改
        public function brandUpdate(){
            if(checkRequest()){
                $data=input('post.');
                $brand_id=input('post.brand_id');
                // 验证器
                $validate=validate('brand');
                $reslut=$validate->scene('edit')->check($data);
                if(!$reslut){
                    fail($validate->getError());
                }

                $model=model('brand');
                $where=[
                    'brand_id'=>$brand_id
                ];
                $res=$model->allowField(true)->save($data,$where);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }
            }else{
                $brand_id=input('get.brand_id');
                $where=[
                    'brand_id'=>$brand_id
                ];
                $data=model('brand')->where($where)->find();
                $this->assign('data',$data);
                return view();
            }
        }
        // 品牌图片的上传
        public function brandLogo(){
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('file');
            //dump($file);exit;
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'brandlogo');
            if($info){
                $arr=[
                    'code'=>1
                    ,'font'=>'上传成功'
                    ,'src'=>$info->getSaveName()
                ];
                echo json_encode($arr);
            }else{
                // 上传失败获取错误信息
                fail($file->getError()) ;
            }
                
        }

    }
?>