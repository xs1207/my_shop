<?php
    namespace app\admin\controller;
    use think\Controller;
    class Goods extends Common{
        // 商品添加验正器
        public function goodsadd(){
            if(checkRequest()){
                $data=input('post.');
                // dump($data);die;
                // 验证器验证
                $validate=validate('goods');
                $result=$validate->check($data);
                if(!$result){
                    fail($validate->getError());
                    exit;
                }

                // 入库
                $model=model('goods');
                $res=$model->allowField(true)->save($data);
                if($res){
                    successly('添加成功');
                 }else{
                     fail('添加失败');
                 }
            }else{
                // 查询分类
                $cateInfo=$this->getCateInfo();

                // 查询品牌
                $where=[
                    'brand_show'=>1
                ];
                $brand=model('brand');
                $brandInfo=$brand->where($where)->select();
                $this->assign('cateInfo',$cateInfo);
                $this->assign('brandInfo',$brandInfo);
                return view();
            }
            
        }
        // 商品展示
        public function goodslist(){
           // 查询分类
           $cateInfo=$this->getCateInfo();

           // 查询品牌
           $where=[
               'brand_show'=>1
           ];
           $brand=model('brand');
           $brandInfo=$brand->where($where)->select();
           $this->assign('cateInfo',$cateInfo);
           $this->assign('brandInfo',$brandInfo);
            return view();
        }
        //获取分类数据
        public function getCateInfo(){
            $model=model('category');
            $data=collection($model->select())->toArray();
            $info=getCateInfo($data);
            return $info;
        }
        // 上传图片和轮播图
        public function goodsupload(){
            $type=input('get.type');
            $dir=$type==1?'goods_img':'goods_imgs';
            // echo $dir;exit;
            $info=$this->upload($dir);
           
        }
        //文件上传
        public function upload($dir){
            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . $dir);
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
        //展示  商品所有数据 以及 分页
        public function goodsInfo(){
            $page=input('get.page');
            $limit=input('get.limit');
            $goods_name=input('get.goods_name');
            $cate_name=input('get.cate_name');
            $brand_name=input('get.brand_name');
            $where=[];
            if(!empty($goods_name)){
                $where['goods_name']=['like',"%$goods_name%"];
            }
            if(!empty($cate_name)){
                $where['cate_name']=$cate_name;
            }
            if(!empty($brand_name)){
                $where['brand_name']=$brand_name;
            }
            $model=model('goods');
            $data=$model->getGoodsInfo($page,$limit,$where);
            $count=$model->getGoodscount($where);
            $info=[
                'code'=>0,
                'msg'=>'',
                'count'=>$count,
                'data'=>$data
            ];
            echo json_encode($info);
        }
        // 即点即改
        public function goodsfieldupdate(){
            $value=input('post.value');
            
            $field=input('post.field');
            $goods_id=input('post.goods_id');
            
            $model=model('goods');
            $where=[
                'goods_id'=>$goods_id
            ];
            $data=[
                $field=>$value
            ];
            $res=$model->save($data,$where);
            //echo $model->getLastSql();
            if($res===false){
                fail('修改失败');
            }else{
                successly('修改成功');
            }
        }
        // 删除
        public function goodsDel(){
            $goods_id=input('post.goods_id');
            // dump($goods_id);die;
            $where=[
                'goods_id'=>$goods_id
            ];
            $res=model('goods')->where($where)->delete();
            if($res){
                successly('删除成功');
            }else{
                fail('删除失败');
            }
        }
        // 修改
        public function goodsupdate(){
            if(checkRequest()){
                $data=input('post.');
                $goods_id=input('post.goods_id');
                // 验证器
                $validate=validate('goods');
                $result=$validate->scene('edit')->check($data);
                if(!$result){
                    fail($validate->getError());
                }
                $model=model('goods');
                $where=[
                    'goods_id'=>$goods_id
                ];
                $res=$model->save($data,$where);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }

            }else{
                $goods_id=input('get.goods_id');
                $where=[
                    'goods_id'=>$goods_id
                ];
                $arr=model('goods')->where($where)->find();
                $this->assign('arr',$arr);
                $this->goodsadd();
                return view();
            }
        }
        /**富文本编辑器的图片上传 */
        public function goodseditimgs(){
            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'goodseditimgs');
            if($info){
                $arr=[
                    'code'=>0
                    ,'font'=>''
                    ,'data'=>[
                        'src'=>'http://localhost/month4/11.8/tp5.0/public/goodseditimgs/'.$info->getsavename(),
                        'title'=>'aa'
                    ]
                ];
                echo json_encode($arr);
            }else{
                // 上传失败获取错误信息
                fail($file->getError()) ;
            }
        }

    }
?>