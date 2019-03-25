<?php
namespace app\index\controller;
use think\Controller;
class Index extends Common{
    public function index(){


        // 获取首页的分类数据
        $this->getIndexCateInfo();


        // 处理楼层数据
        $cate_id=1;
        $info=$this->getFloorInfo($cate_id);
        $this->assign('info',$info);
        return view();
    }


    // 获取楼层信息
    public function getFloorInfo($cate_id){
        $model=model('category');
        // 处理的顶级分类的名称  ID
        $topWhere=[
            'cate_id'=>$cate_id,
            'is_show'=>1
        ];
        $info['topData']=$model->field('cate_id,cate_name')->where($topWhere)->find();

        
        // 获取二级分类数据
        $cateWhere=[
            'pid'=>$cate_id,
            'is_show'=>1
        ];
        $info['cateList']=$model->field('cate_id,cate_name')->where($cateWhere)->select();
        

        // 获取商品分类
        $cateInfo=$model->where(['is_show'=>1])->select();
        $cateId=getCateId($cateInfo,$cate_id);
        $cateId=implode(',',$cateId);
        $goods=model('goods');
        $goodsWhere=[
            'goods_sell'=>1,
            'cate_id'=>['in',$cateId]
        ];
        $info['goodsInfo']=$goods->where($goodsWhere)->select();
        return $info;
    }

    // 获取下一楼层的id
    public function getMoreFloor(){
        //接收分类Id
        $cate_id=input('post.cate_id');
        $floor_num=input('post.floor_num');
        $floor_num=$floor_num+1;
        // echo $cate_id;die;
        //根据分类id  查询下一楼层的分类id
        $where=[
            'cate_id'=>['>',$cate_id],
            'pid'=>0,
            'is_show'=>1
        ];
        $cate=model('category');
        $arr=$cate->field('cate_id')->where($where)->order('cate_id','asc')->find();
        $cate_id=$arr['cate_id'];
        if(!empty($cate_id)){
             // 获取楼层数据
            $info=$this->getFloorInfo($cate_id);
            $this->view->engine->layout(false);
            $this->assign('info',$info);
            $this->assign('floor_num',$floor_num);
            echo $this->fetch('div');
        }
       


        
    }
}
?>