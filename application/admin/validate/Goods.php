<?php
  namespace app\admin\validate;
  use think\Validate;
  class Goods extends Validate{
              protected $rule =   [
                'goods_name'  => 'require',
                'self_price'=>'require',
                'market_price'=>'require',
                'goods_num'=>'require',
                'goods_score'=>'require',
                'goods_img'=>'require',
                'goods_imgs'=>'require',
             ];
            protected $message  =   [
              'goods_name.require'=>'商品名称不能为空',
              'self_price.require'=>'本店价格不能为空',
              'market_price.require'=>'市场价格不能为空',
              'goods_num.require'=>'库存不能为空',
              'goods_score.require'=>'赠送积分不能为空',
              'goods_img.require'=>'商品图片不能为空',
              'goods_imgs.require'=>'轮播图不能为空',
            ];
            protected $scene = [ 
              'add'=>['goods_name','self_price','market_price','goods_sell','goods_img','goods_imgs','goods_num','goods_score'],
              'edit'=>['goods_name','goods_marketprice','goods_up','goods_num']
            ];
  }
?>