<?php
    namespace app\index\controller;
    class Order extends Common{
        /**确认结算 */
        public function confirmSettlement(){
            //获取左侧数据
            $this->getIndexCateInfo();
            //检测是否登录
            if(!$this->checkUserLogin()){
                $this->error("请先登录",url("Login/login"));
            }
            //接收购物车id
            $cid=input("get.cid","");
            if(empty($cid)){
                $this->error("请选择购物车商品结算",url('Cart/cartList'));
            }
            //查询购物车id  是否是当前用户的
            $user_id=$this->getUserId();
            $cartWhere=[
                'id'=>['in',$cid],
                'user_id'=>$user_id,
                'status'=>1
            ];
            $cart=model("Cart");
            $cartInfo=$cart->alias('c')
                ->join("shop_goods g","c.goods_id=g.goods_id")
                ->where($cartWhere)
                ->select();
            if(empty($cartInfo)){
                $this->error("购物车无此商品",url("Index/index"));
            }
            //获取用户收货地址
            $addressWhere=[
                'user_id'=>$user_id,
                'status'=>1
            ];
            $addressInfo=$this->getAddressInfo($addressWhere);
            $this->assign("cartInfo",$cartInfo);
            $this->assign("addressInfo",$addressInfo);
            return view();
        }
        /**确认订单 */
        public function confirmOrder(){
            if(!$this->checkUserLogin()){
                fail("请先登录",url("login/login"));
            }

            //加测购物车数据
            $cid=input("post.cid","");
            $user_id=$this->getUserId();
            if(empty($cid)){
                fail("购物车没有商品数据，无法下单");
            }
            //查询购物车id  是否是当前用户的
            $cartWhere=[
                'id'=>['in',$cid],
                'user_id'=>$user_id,
                'status'=>1
            ];
            $cart=model("Cart");
            $cartInfo=$cart->alias('c')
                ->join("shop_goods g","c.goods_id=g.goods_id")
                ->where($cartWhere)
                ->select();
                //判断用户是否还能下订单
            foreach($cartInfo as $k=>$v){
                if($k["goods_num"]>$v["buy_number"]){
                    unset($cartInfo[$k]);
                }
            }
            if(empty($cartInfo)){
                $this->error("购物车无此商品",url("Index/index"));
            }

            $order_model=model("Order");
            // 开启事务
            $order_model->startTrans();
            
            try{
                $pay_type=input("post.pay_type");
                $order_message=input("post.order_message");
                //积分表写入
//                $score=[];
//                $score['user_id']=$user_id;
//                $goods_score=0;
//                foreach($cartInfo as $k=>$v){
//                    $goods_score+=$v["buy_number"]*$v["self_price"];
//                }
//                $score['goods_score']=$goods_score;
//                $ress=model("score")->insert($score);
//                if(empty($ress)){
//                    $order_model->rollback();
//                    throw new \Exception("下单失败");
//                }

                $score_model=model("Score");
                $score=[];
                $score['user_id']=$user_id;
                $goods_score=0;
                foreach($cartInfo as $k=>$v){
                    $goods_score+=$v["buy_number"]*$v["self_price"];
                }
                $score['goods_score']=$goods_score;
                $scoreWhere=["user_id"=>$user_id];
                $scoreInfo=$score_model->where($scoreWhere)->find();
                if($scoreInfo){
                    //累加  改
                    $update=[
                        'goods_score'=>$scoreInfo['goods_score']+$goods_score
                    ];
                    $ress=$score_model->where(["user_id"=>$user_id])->update($update);
                }else{
                    $ress=$score_model->insert($score);
                }

                if(empty($ress)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }


                //订单表   数据写入
                $order=[];
                $order_number=$this->getOrderNumber();
                $order['order_number']=$order_number;
                $order['user_id']=$user_id;
                $order_amount=0;
                $order_score=0;
                foreach($cartInfo as $k=>$v){
                    $order_amount+=$v["buy_number"]*$v["self_price"];
                    $order_score+=$v["buy_number"]*$v["goods_score"];
                }
                $order['order_amount']=$order_amount;
                $order['order_score']=$order_score;
                $order['pay_type']=$pay_type;
                $order['order_message']=$order_message;
                if($pay_type==3){
                    $order['order_status']=4;
                }else{
                    $order['order_status']=1;
                }
                // print_r($order);die;
                        //添加入库
                $res1=$order_model->save($order);
                if(empty($res1)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }
                $order_id=$order_model->order_id;       

                //订单详情表   数据写入
                $order_detail=[];
                foreach($cartInfo as $k=>$v){
                    $order_detail[]=[
                        'user_id'=>$user_id,
                        'order_id'=>$order_id,
                        'goods_id'=>$v['goods_id'],
                        'goods_name'=>$v['goods_name'],
                        'self_price'=>$v['self_price'],
                        'goods_img'=>$v['goods_img'],
                        'buy_number'=>$v['buy_number']
                    ];
                }
                $order_detail_model=model("OrderDetail");
                $res2=$order_detail_model->saveAll($order_detail);
                if(empty($res2)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }

                //订单收货表  数据写入
                $address_id=input("post.address_id");
                $order_address=[];
                $addressWhere=[
                    'user_id'=>$user_id
                ];
                $addressInfo=$this->getAddressInfo($addressWhere,$address_id);
                $order_address['user_id']=$user_id;
                $order_address['order_id']=$order_id;
                $order_address['address_province']=$addressInfo['address_province'];
                $order_address['address_city']=$addressInfo['address_city'];
                $order_address['address_county']=$addressInfo['address_county'];
                $order_address['address_name']=$addressInfo['address_name'];
                $order_address['address_tel']=$addressInfo['address_tel'];
                $order_address['address_detailed']=$addressInfo['address_detailed'];
                // print_r($order_address);die;
                $order_address_model=model("OrderAddress");
                $res3=$order_address_model->insert($order_address);
                if(empty($res3)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }

                //购物车吧数据清空
                $cart=model("Cart");
                $carWhere=[
                    'user_id'=>$user_id,
                    'id'=>['in',$cid]
                ];
                $cartData=['status'=>2,'buy_number'=>0];
                $res4=$cart->where($carWhere)->update($cartData);
                if(empty($res4)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }

                //商品表  修改库存
                $goods_model=model("Goods");
                foreach($cartInfo as $k=>$v){
                    $goodsWhere=['goods_id'=>$v["goods_id"]];
                    $goodsData=[
                        'goods_num'=>$v["goods_num"]-$v["buy_number"]
                    ];
                    $res5=$goods_model->where($goodsWhere)->update($goodsData); 
                }
                if(empty($res5)){
                    $order_model->rollback();
                    throw new \Exception("下单失败");
                }
                $order_model->commit();
                echo json_encode(['font'=>"下单成功","code"=>1,"order_id"=>$order_id]);
            }catch (\Exception $e){
                fail($e->getMessage());
            }
            
        }

//        public function total(){
//
//        }

        /**获取订单号 */
        public function getOrderNumber(){
            //标志+年月日+用户id+4位随机数
            $data=date("ymd");
            // echo $data;
            $user_id=$this->getUserId();
            $user_id=str_repeat("0",4-strlen($user_id)).$user_id;
            // echo $user_id;
            $order_number="1".$data.$user_id.rand(1000,9999);
            // echo $order_number;
            return $order_number;
        }
        /**下单成功页面 */
        public function orderSuccess(){
            $order_id=input("get.order_id",0,"intval");
            try{
                if(empty($order_id)){
                    fail("订单操作错误");
                }
                $orderWhere=[
                    'order_id'=>$order_id
                ];
                $orderInfo=model("Order")->where($orderWhere)->find();
                $this->assign("orderInfo",$orderInfo);
            }catch(\Exception $e){
                fail($e->getMassage());
            }
            //获取左侧数据
            $this->getIndexCateInfo();
            return view();
        }
        /**支付宝支付 */
        public function alipay(){
            //获取订单号
            $order_number=input("get.order_number",0);

            //查询订单的信息
            $orderWhere=[
                'order_number'=>$order_number
            ];
            $orderInfo=$this->getOrderInfo($orderWhere);
            // dump($orderInfo);die;
            if(empty($orderInfo)){
                $this->error("订单不存在",url('index/index'));
            }

            //支付宝支付
            $config=config("alipay_config");
            require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';
            require_once EXTEND_PATH.'alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';

            //商户订单号，商户网站订单系统中唯一订单号，必填
            $out_trade_no = $orderInfo['order_number'];

            //订单名称，必填
            $subject ="电子商城————支付";

            //付款金额，必填
            $total_amount = $orderInfo['order_amount'];

            //商品描述，可空
            $body = "";

            //构造参数
            $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
            $payRequestBuilder->setBody($body);
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setOutTradeNo($out_trade_no);

            $aop = new \AlipayTradeService($config);

            $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);

            //输出表单
            var_dump($response);
        }

        /**同步通知地址 */
        public function paySuccess(){
            $param=input("get.");

            //验证订单号
            $orderWhere=[
                'order_number'=>$param['out_trade_no']
            ];
            $orderInfo=$this->getOrderInfo($orderWhere);
            // dump($orderInfo);die;
            if(empty($orderInfo)){
                $this->error("当前支付的订单不存在",url('index/index'));
            }

            //验证订单金额
            if($orderInfo['order_amount']!=$param['total_amount']){
                $this->error("当前支付总金额不存在",url('index/index'));
            }
            //验证签名
            $config=config("alipay_config");
            require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';

            $alipaySevice = new \AlipayTradeService($config); 
            $result = $alipaySevice->check($param);
            if($result) {
                $this->getIndexCateInfo();
                //验证成功
                $this->assign("orderInfo",$orderInfo);
                return view();
            }
            else {
                //验证失败
                echo "验证失败";
            }
        }

        /**异步通知地址 */
        public function notify(){
            $param=input("post.");
            $filename='/data/wwwroot/default/tp5.0/notify.log';
            file_put_contents(
                $filename,
                print_r($param,true),
                FILE_APPEND
            );

            //验证订单是否正确
            $order_number=$param['out_trade_no'];
            $orderWhere=[
                'order_number'=>$param['out_trade_no']
            ];
            $orderInfo=$this->getOrderInfo($orderWhere);
            if(empty($orderInfo)){
                file_put_contents(
                    $filename,
                    'order_number not found',
                    FILE_APPEND
                );
                echo "order_number error";exit;
            };
            //验证订单金额
            if($orderInfo['order_amount']!=$param['total_amount']){
                file_put_contents(
                    $filename,
                    'order_amount error',
                    FILE_APPEND
                );
                echo "order_amount error";exit;
            }
            //验证签名
            $config=config("alipay_config");
            require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';

            $alipaySevice = new \AlipayTradeService($config);
            $result = $alipaySevice->check($param);
            if(empty($result)){
                file_put_contents(
                    $filename,
                    'sign error',
                    FILE_APPEND
                );
                echo "sign error";exit;
            }
            //验证应用id
            if($config['app_id']!=$param['app_id']){
                file_put_contents(
                    $filename,
                    'app_id error',
                    FILE_APPEND
                );
                echo "app_id error";exit;
            }
            //验证支付状态是否为已支付  success
            if($orderInfo['pay_status']==2){
                file_put_contents(
                    $filename,
                    'pay_status pass',
                    FILE_APPEND
                );
                echo "success";
            }
            //改支付状态  支付时间  success   fail
            if($orderInfo['pay_status']==1){
               $data=[
                   'pay_status'=>2,
                   'order_status'=>3,
                   'pay_time'=>time(),
                   'utime'=>time()
               ];
               $orderWhere=[
                   'order_number'=>$order_number
               ];
               $order_model=model("Order");
               $res=$order_model->where($orderWhere)->update($data);
               if($res){
                   file_put_contents(
                       $filename,
                       'pay_status pass',
                       FILE_APPEND
                   );
                   echo "success";
               }else{
                   file_put_contents(
                       $filename,
                       'pay_status fail',
                       FILE_APPEND
                   );
                   echo "fail";exit;
               }
            }
        }
    }












?>