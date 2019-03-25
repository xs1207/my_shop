<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:81:"E:\phpStudy\WWW\month4\11.8\tp5.0\public/../application/index\view\index\div.html";i:1544057049;}*/ ?>
<div cate_id="<?php echo $info['topData']['cate_id']; ?>" floor_num="<?php echo $floor_num; ?>">
        <div class="i_t mar_10">
            <span class="floor_num"><?php echo $floor_num; ?>F</span>
            <span class="fl"><?php echo $info['topData']['cate_name']; ?></span>                
            <span class="i_mores fr">
                <?php if(is_array($info['cateList']) || $info['cateList'] instanceof \think\Collection || $info['cateList'] instanceof \think\Paginator): $i = 0; $__LIST__ = $info['cateList'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                <a href="#"><?php echo $v['cate_name']; ?></a>
                <?php endforeach; endif; else: echo "" ;endif; ?> 
            </span>
        </div>
        <div class="content">

            <div class="fresh_mid">
                <ul>
                    <?php if(is_array($info['goodsInfo']) || $info['goodsInfo'] instanceof \think\Collection || $info['goodsInfo'] instanceof \think\Paginator): $i = 0; $__LIST__ = $info['goodsInfo'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                    <li>
                        <div class="name"><a href="<?php echo url('product/productdetail'); ?>?goods_id=<?php echo $v['goods_id']; ?>"><?php echo $v['goods_name']; ?></a></div>
                        <div class="price">
                            <font>￥<span><?php echo $v['self_price']; ?></span></font> &nbsp; <?php echo $v['goods_score']; ?>R
                        </div>
                        <div class="img"><a href="<?php echo url('product/productdetail'); ?>?goods_id=<?php echo $v['goods_id']; ?>"><img src="__ROOT__/goods_img/<?php echo $v['goods_img']; ?>" width="185" height="155" /></a></div>
                    </li>  
                    <?php endforeach; endif; else: echo "" ;endif; ?>                                                                   
                </ul>
            </div>

        </div> 
</div>