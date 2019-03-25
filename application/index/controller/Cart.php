<?php
    namespace app\index\controller;
    class Cart extends Common{
        /**加入购物车 */
        public function cartadd(){
            $goods_id=input("post.goods_id",0,'intval');
            $buy_number=input("post.buy_number",0,'intval');
            if(empty($goods_id)){
                echo '请选择商品';exit;
            }
            if(empty($buy_number)){
                echo '请输入购买的数量';exit;
            }
            $goodsWhere=[
                'goods_id'=>$goods_id
            ];
            $goods=model('goods');
            $goodsInfo=$goods->where($goodsWhere)->find();
            if(empty($goodsInfo)){
                echo '请选择购买的商品';exit;
            }
          
            if($this->checkUserLogin()){
                //购物车信息存入数据库中
                $res=$this->cartAddDb($goodsInfo,$buy_number);
            }else{
                
                //购物车信息存入cookie中
                $res=$this->cartAddCookie($goodsInfo,$buy_number);
               
              /*   $cart=unserialize(base64_decode(cookie('cart')));
                print_r($cart);exit; */
            }
            if($res){
                successly("加入购物车成功，是否进入购物车列表？");
            }else{
                fail("加入购物失败");
            }
        }

        /**购物车信息存入cookie中 */
        public function cartAddCookie($goodsInfo,$buy_number){
            $goods_id=$goodsInfo['goods_id'];
            $now=time();
            //取出cookie信息
            $cartInfo=$this->getCartCookie();
            // dump($cartInfo);die;
            if(!empty($cartInfo[$goods_id])){
                // 数量做累加
                $cartInfo[$goods_id]['buy_number']=$cartInfo[$goods_id]['buy_number']+$buy_number;
                $cartInfo[$goods_id]['utime']=$now;
                //print_r($cartInfo);die;
                $this->checkNumber($goodsInfo,$cartInfo[$goods_id]['buy_number']);
                $cart=base64_encode(serialize($cartInfo));
                $second=60*60*20;
                cookie('cart',$cart,$second);
                return true;
            }else{
                // echo 1111;die;
                $cartInfo[$goods_id]=[
                    'goods_id'=>$goods_id,
                    'buy_number'=>$buy_number,
                    'ctime'=>$now,
                    'utime'=>$now
                ];
                $this->checkNumber($goodsInfo,$buy_number);
                $cart=base64_encode(serialize($cartInfo));
                $second=60*60*20;
                $cart= cookie('cart',$cart,$second);
                return true;
            }
        }

        /**取出购物车的cookie信息 */
        public function getCartCookie(){
            $cart=cookie('cart');
            //dump($cart);die;
            $cartInfo=[];
            if(empty($cart)){
                return $cartInfo;
            }else{
                $cartInfo=unserialize(base64_decode($cart));
                return $cartInfo;
            }
        }

        /**购物车信息存数据库 */
        public function cartAddDb($goodsInfo,$buy_number){
            $goods_id=$goodsInfo['goods_id'];
            // 根据商品 id 查询购物车表
            $cart=model('cart');
            $user_id=$this->getUserId();
            $cartWhere=[
                'goods_id'=>$goods_id,
                'user_id'=>$user_id
            ];
            $cartInfo=$cart->where($cartWhere)->find();
            if($cartInfo){
                //累加  改
                $update=[
                    'buy_number'=>$cartInfo['buy_number']+$buy_number,
                    'utime'=>time(),
                    'status'=>1
                ];
                //检查库存
                $this->checkNumber($goodsInfo,$update['buy_number']);
                $res=$cart->where($cartWhere)->update($update);
                if($res){
                    return true;
                }else{
                    return false;
                }
            }else{
                //添加
                $insert=[
                    'goods_id'=>$goods_id,
                    'add_price'=>$goodsInfo['self_price'],
                    'buy_number'=>$buy_number,
                    'user_id'=>$this->getUserId()
                ];
                //检查库存
                $this->checkNumber($goodsInfo,$buy_number);
                $res=$cart->save($insert);
                if($res){
                    return true;
                }else{
                    return false;
                }
            }
        }

        /**购物车展示 */
        public function cartlist(){
            // 查询左侧的数据
            $this->getIndexCateInfo();

            if($this->checkUserLogin()){
                $cartInfo=$this->getCartInfoDb();
                $login=1;
            }else{
                $cartInfo=$this->getCartInfoCookie();
                //print_r($cartInfo);exit;
                $login=2;
            }
            
            if(!empty($cartInfo)){
                $this->assign("cartInfo",$cartInfo);
            }else{
                $this->assign("cartInfo",'');
            }
            $this->assign("login",$login);
            return view();
        }

        /**购物车修改 */
        public function cartUpdate(){
            $buy_number=input("post.buy_number",0,"intval");
            $id=input("post.id",0,"intval");
            if(empty($buy_number)){
                fail("请输入购买数量");
            }
            if($this->checkUserLogin()){
                $id=input("post.id",0,"intval");
                if(empty($id)){
                    fail("请选择要更改数量的商品");
                }
                $res=$this->updateNumDb($id,$buy_number);
            }else{
                $goods_id=input("post.goods_id",0,"intval");
                if(empty($goods_id)){
                    fail("请选择要更改数量的商品");
                }
                // echo $goods_id;die;
                $res=$this->updateNumCookie($goods_id,$buy_number);
            }
            if($res){
                successly("");
            }else{
                fail("修改失败");
            }
        }
        /**修改数据库中购买的数量 */
        public function updateNumDb($id,$buy_number){
            $where=[
                'id'=>$id
            ];
            $data=[
                'buy_number'=>$buy_number
            ];
            $cart=model("Cart");
            $res=$cart->where($where)->update($data);
            dump($res);
        }

        /**修改cookie中购买的数量 */
        public function updateNumCookie($goods_id,$buy_number){
            $cart_str=cookie('cart');
            if(empty($cart_str)){
                fail("购物车太空了");
            }
            $cartInfo=unserialize(base64_decode($cart_str));
            // print_r($cartInfo);
            $cartInfo[$goods_id]["buy_number"]=$buy_number;
            $cart=base64_encode(serialize($cartInfo));
            $second=24*24*60;
            cookie("cart",$cart,$second);
            return true;
        }

        /**购物车的删除 */
        public function cartDel(){
            // 接收购物车id  商品id  type 
            $type=input("post.type",0,"intval");
            $goods_id=input("post.goods_id",0);
            //dump($goods_id);die;
            //echo $goods_id;
            $id=input("post.id",0);
            //echo $id;die;
            // dump("$type");die;

            if(empty($goods_id)){
                fail("请选择要删除商品");
            }
            if($this->checkUserLogin()){
                //登录 删除数据库
                if(empty($id)){
                    fail("操作有误，请选择要删除的商品数据");
                }
                if($type==1){
                    // 单删
                    $where=['id'=>$id];
                    $data=[
                        "status"=>2,
                        'buy_number'=>0
                    ];
                    $cart=model("Cart");
                    $res=$cart->where($where)->update($data);   
                }else{
                    // 批删
                    $awhere=['id'=>["in",$id]];
                    $adata=[
                        "status"=>2,
                        'buy_number'=>0
                    ];
                    $cart=model("Cart");
                    $res=$cart->where($awhere)->update($adata);
                }
                if($res){
                    successly("删除成功");
                }else{
                    fail("删除失败");
                }

            }else{
                //未登录 

                if(empty($goods_id)){
                    fail("操作有误，请选择要删除的商品数据");
                }
                $cookie_str=cookie('cart');
                if(empty($cookie_str)){
                    fail("购物车太空了，没有商品让你删了");
                }
                if($type==1){
                    $cartInfo=unserialize(base64_decode(cookie("cart")));
                    unset($cartInfo[$goods_id]);
                    $cart=base64_encode(serialize($cartInfo));
                    cookie("cart",$cart);
                    successly("删除成功");
                }else{
                    $cookie_str=cookie("cart");
                    if(empty($cookie_str)){
                        fail("购物车太空了，没有商品让你删了");
                    }
                    $goods_id=explode(",",$goods_id);
                    $cartInfo=unserialize(base64_decode($cookie_str));
                    foreach($goods_id as $k=>$v){
                        unset($cartInfo[$v]);
                    }
                    $cart=base64_encode(serialize($cartInfo));
                    cookie("cart",$cart);
                    successly("删除成功");
                }
                
                
            }
        }

        /**添加收藏 */
        public function addCollection(){
            if(!checkRequest()){
                fail("请按正确流程加入收藏");
            }
            if(!$this->checkUserLogin()){
                fail("请先登录");
            }
            
            $goods_id=input("post.goods_id",0);
            if(empty($goods_id)){
                fail("请选择要收藏的商品");
            }


            $type=input("post.type",0,"intval");
            $user_id=$this->getUserId();
            $model=model("UserGoods");
            if($type==1){
                $data=[
                    'user_id'=>$user_id,
                    'goods_id'=>$goods_id
                ];
                // print_r($data);die;
                $count=$model->where($data)->count();
                if($count>0){
                    fail("此商品已被收藏");
                }
                $res=$model->save($data);
                if($res){
                    successly("收藏成功");
                }
            }else{
                $goods_id=explode(",",$goods_id);
                $num=0;
                // dump($goods_id);
                foreach($goods_id as $k=>$v){
                    $data=['user_id'=>$user_id,'goods_id'=>$v];
                    // print_r($data);
                    $count=$model->where($data)->count();
                    
                    if($count==0){
                        $res=$model->insert($data);
                    }else{
                        $num+=1;
                    }
                }

                if(!empty($res)){
                    if($num>0){
                        successly("收藏成功，您有".$num."件商品已收藏");
                    }
                    successly("收藏成功");
                }else{
                    fail("所有商品都已收藏过");
                }
            }
        }

        /**清空购物车 */
        public function emptyCart(){
            //接收id
            $id=input("post.id",0);
            if(empty($id)){
                fail("操作有误，请选择要删除的商品数据");
            }

            // 单删
            $where=[
                'id'=>["in",$id]
            ];
            $data=[
                "status"=>2,
                'buy_number'=>0
            ];
            $cart=model("Cart");
            $res=$cart->where($where)->update($data);
            if($res){
                successly("删除成功");
            }else{
                fail("删除失败");
            }
            
        }
    }
?>