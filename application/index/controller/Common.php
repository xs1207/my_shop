<?php
    namespace app\index\controller;
    use think\Controller;
    class Common extends Controller{
        
        public function __construct(){
            parent::__construct();

            // 获取网站信息
            $this->getWebConfig();

            // 获取购物车信息
           $cartInfo=$this->getTopCartInfo();
        }

        /**检测用户是否登录 */
        public function checkUserLogin(){
            if(session('?userInfo')){
                $userInfo=session('userInfo');
                return $userInfo;
            }else{
                // $this->error('请先登录',url('Login/login'));
                return false;
            }
        }

        /**获取配置 */
        public function getWebConfig(){
            $config=model('Config');
            $data=$config->select();
            foreach($data as $k=>$v){
                $configInfo[$v['name']]=$v['value'];
            }
            $this->assign('config',$configInfo);
        }

        // 获取首页的分类数据
        public function getIndexCateInfo(){
            $cate=model('category');
            $where=[
                'is_show'=>1
            ];
            $navwhere=[
                'is_navshow'=>1
            ];
            $data=$cate->where($where)->select();
            $arr=$cate->where($navwhere)->select();
            $cateInfo=getIndexCateInfo($data);
            $catenav=getIndexCateInfo($arr);
            $this->assign('cateInfo',$cateInfo);
            $this->assign('catenav',$catenav);
        }

        /**获取用户id */
        public function getUserId(){
            return session('userInfo.user_id');
        }

        /**同步浏览记录 */
        public function asyncHistory(){
            $history=cookie('history');
            if(!empty($history)){
                $arr=unserialize(base64_decode($history));
                $user_id=$this->getUserId();
                $model=model('history');
                foreach($arr as $k=>$v){
                    $v['user_id']=$user_id;
                    $model->insert($v);
                }
                cookie('history',null);
            }
        }   
        
        /**检测库存 */
        public function checkNumber($goodsInfo,$buy_number){
            $goods_num=$goodsInfo['goods_num'];
            if($buy_number>$goods_num){
                echo '此商品库存'.$goods_num.'件,您选择已超过库存';exit;
            }
        }

        /**同步购物车 */
        public function asyncCart(){
            $cart=cookie('cart');
            $cartInfo=unserialize(base64_decode($cart));
            //dump($cartInfo);exit;
            if(!empty($cartInfo)){
                //循环查询商品是否存在于购物车
                $user_id=$this->getUserId();
                //echo $user_id;die;
                $model=model('Cart');
                $goods=model('Goods');
                foreach($cartInfo as $k=>$v){
                    $goodsInfo=$goods->where(['goods_id'=>$v['goods_id']])->find();
                    //echo $goods->getLastSql();exit;
                    if(empty($goodsInfo)){
                        echo "请重新选择您要购买的商品";exit;
                    }
                    if($goodsInfo['goods_sell']==2){
                        echo "您选择的部分商品已下架";exit;
                    }
                    $cartWhere=[
                        'goods_id'=>$v['goods_id'],
                        'user_id'=>$user_id
                    ];
                    //dump($cartWhere);die;
                    $cartData=$model->where($cartWhere)->find();
                    // echo $model->getLastSql();exit;
                    if(!empty($cartData)){
                        //改
                        $updata=[
                            'buy_number'=>$cartData['buy_number']+$v['buy_number'],
                            'utime'=>time(),
                            'status'=>1
                        ];
                        $this->checkNumber($goodsInfo,$updata['buy_number']);
                        $res=$model->where($cartWhere)->update($updata);
                        //dump($res);
                    }else{
                        //增
                        $v['add_price']=$goodsInfo['self_price'];
                        $v['user_id']=$user_id;
                        $this->checkNumber($goodsInfo,$v['buy_number']);
                        $res=$model->insert($v);
                        //dump($res);
                    }
                }
                cookie('cart',null);
            }
        }
        /**面包屑导航栏 */
        public function getCrumbs($cate_id){
            $cate=model('Category');
            $where=[
                'is_show'=>1
            ];
            $arr=$cate->where($where)->select();
            $name=getCateName($arr,$cate_id);
            $new_name=array_reverse($name);
            $str="";
            foreach($new_name as $k=>$v){
                $str.="<a href='".url('product/productlist')."?cate_id=".$v['cate_id']."'>".$v['cate_name']."</a>".'>';
            }

            $cate_str=rtrim($str,">");
            return $cate_str;
        }

        /**从数据库里取出购物车数据 */
        protected function getCartInfoDb(){
            // 商品id  名称 价格  购买数量  购物车表  商品表
            $where=[
                'user_id'=>$this->getUserId(),
                'status'=>1,
                'goods_sell'=>1
            ];
            $cart=model('Cart');
            $cartInfo=$cart
                ->field("id,goods_name,self_price,buy_number,add_price,goods_img,goods_num,c.goods_id")
                ->alias('c')
                ->join("shop_goods g","c.goods_id=g.goods_id")
                ->where($where)
                ->order("utime","desc")
                ->select();
            return $cartInfo;
            
        }

        /**从cookie中获取购物车数据 */
        protected function getCartInfoCookie(){
            $cart_str=cookie('cart');
            $cartInfo=unserialize(base64_decode($cart_str));
            //print_r($cartInfo) ;die;
            if(empty($cartInfo)){
                 return [];
                //print_r($cartData);die;      
            }else{
                //echo "adwa";die;
                //print_r($cartInfo);exit;
                $goods=model('Goods');
                foreach($cartInfo as $k=>$v){
                    $where=[
                        'goods_id'=>$v['goods_id'],
                        'goods_sell'=>1,
                    ]; 
                    $goodsInfo=$goods
                        ->where($where)
                        ->find()->toArray();
                    //print_r($goodsInfo);exit;
                    $cartData[]=array_merge($goodsInfo,$v);
                    //print_r($cartData);die;
                }
                return $cartData;
            }
            
        }
        /**获取头部购物车信息 */
        public function getTopCartInfo(){
            if($this->checkUserLogin()){
                $cartInfo=$this->getCartInfoDb();
            }else{
                $cartInfo=$this->getCartInfoCookie();
            }
            if(!empty($cartInfo)){
                $this->assign("cartInfo",$cartInfo);
                $this->assign("is_cartInfo",1);
            }else{
                $this->assign("is_cartInfo",0);
            }
        }

        /**获取收货地址列表 */
        public function getAddressInfo($addressWhere,$address_id=''){
            $address_model=model("Address");
            $area_model=model("Area");
            if(empty($address_id)){
                $addressInfo=collection($address_model->where($addressWhere)->select())->toArray();
                if(!empty($addressInfo)){
                    // print_r($addressInfo);die;
                    $id=[];
                    foreach($addressInfo as $k=>$v){
                        $id[]=$v['address_province'];
                        $id[]=$v['address_city'];
                        $id[]=$v['address_county'];
                    }
                    $id=array_unique($id);
                    // print_r($id);die;//去重之后的
                    $areaWhere=["id"=>['in',$id]];
                    $areaInfo=collection($area_model->where($areaWhere)->select())->toArray();
                    // print_r($areaInfo);die;
                    $area_name=[];
                    foreach($areaInfo as $k=>$v){
                        $area_name[$v['id']]=$v['name'];

                    }
                    // print_r($area_name);die;
                    foreach($addressInfo as $k=>$v){
                        $addressInfo[$k]['province_name']=$area_name[$v['address_province']];
                        $addressInfo[$k]['city_name']=$area_name[$v['address_city']];
                        $addressInfo[$k]['county_name']=$area_name[$v['address_county']];
                    }
                    // print_r($addressInfo);
                    return $addressInfo;
                }else{
                    return [];
                }
               
            }else{
                $addressWhere["address_id"]=$address_id;
                // print_r($addressWhere);die;
                $addressInfo=$address_model->where($addressWhere)->find()->toArray();
                // print_r($addressInfo);die;
                if(!empty($addressInfo)){
                    $id[]=$addressInfo["address_province"];
                    $id[]=$addressInfo["address_city"];
                    $id[]=$addressInfo["address_county"];
                    // print_r($id);die;
                    $area_where=["id"=>["in",$id]];
                    $areaInfo=collection($area_model->where($area_where)->select())->toArray();
                    $area_name=[];
                    foreach($areaInfo as $k=>$v){
                        $area_name[$v['id']]=$v['name'];
                    }
                    $addressInfo['province_name']=$area_name[$addressInfo['address_province']];
                    $addressInfo['city_name']=$area_name[$addressInfo['address_city']];
                    $addressInfo['county_name']=$area_name[$addressInfo['address_county']];
                    //print_r($addressInfo);die;
                    return $addressInfo;
                }else{
                    return [];
                }
            }
        }

        /**获取订单一条信息 */
        public function getOrderInfo($orderWhere){
            $order_model=model("order");
            $orderInfo=$order_model->where($orderWhere)->find();
            // dump($orderInfo);die;
            return $orderInfo;
        }

    }
?>