<?php
    namespace app\admin\model;
    use think\Model;
    class Goods extends Model{
        protected $table="shop_goods";
        // protected $autoWriteTimestamp = true;
        protected $updateTime = false;
        public function getGoodsInfo($page,$limit,$where){
            $data=$this->field("g.*,cate_name,brand_name")->alias('g')->join("shop_category c","g.cate_id=c.cate_id")->join("shop_brand b","g.brand_id=b.brand_id")->where($where)->page($page,$limit)->select();

            foreach($data as $k=>$v){
                $data[$k]['goods_sell']= $v['goods_sell']==1?"是":"否";
                $data[$k]['goods_new']= $v['goods_new']==1?"√":"×";
                $data[$k]['goods_best']= $v['goods_best']==1?"√":"×";
                $data[$k]['goods_hot']= $v['goods_hot']==1?"√":"×";
            }
            return $data;

        }
        public function getgoodsCount($where){
            $count=$this->alias('g')->join("shop_category c","g.cate_id=c.cate_id")->join("shop_brand b","g.brand_id=b.brand_id")->where($where)->count();
             return $count;
        }
       
    }
?>