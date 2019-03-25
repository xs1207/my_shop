<?php
    namespace app\index\controller;
    use think\Controller;
    class UserOrder extends Common{

        // 订单展示
        public function orderlist(){
            //j检测是否登录
            if(!$this->checkUserLogin()){
                $this->error("请先登录",url('Login/login'));
            }
            //获取订单信息
            $where=[
                'user_id'=>$this->getUserId(),
                "is_show"=>1
            ];
            $orderInfo=model("Order")->where($where)->select();
            $this->assign("orderInfo",$orderInfo);

            return view();
        }
        // 订单详情
        public function express(){
            $order_number=input("get.order_number");
            $this->test($order_number);
        }
        // 生成订单号
        public function test($order_number){
            $showapi_appid = '83243';  //替换此值,在官网的"我的应用"中找到相关值
            $showapi_secret = '9bb9ae105dad47c394cd629dea291e2a';  //替换此值,在官网的"我的应用"中找到相关值
            $paramArr = array(
            'showapi_appid'=> $showapi_appid,
                'com'=> "zhongtong",
                'nu'=> "$order_number"
            //添加其他参数
            );

            //创建参数(包括签名的处理)
            function createParam ($paramArr,$showapi_secret) {
            $paraStr = "";
            $signStr = "";
            ksort($paramArr);
            foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '') {
            $signStr .= $key.$val;
            $paraStr .= $key.'='.urlencode($val).'&';
            }
            }
            $signStr .= $showapi_secret;//排好序的参数加上secret,进行md5
            $sign = strtolower(md5($signStr));
            $paraStr .= 'showapi_sign='.$sign;//将md5后的值作为参数,便于服务器的效验
            return $paraStr;
            }

            $param = createParam($paramArr,$showapi_secret);
            $url = 'http://route.showapi.com/64-19?'.$param;
            $result = file_get_contents($url);
            $result = json_decode($result);
            print_r($result->showapi_res_body->data);
        }


        //取消订单
        public function orderCancel(){
            $order_number=input("post.order_number");
            if(empty($order_number)){
                fail("无此订单");
            }
            $order_model=model("Order");
            $order_model->startTrans();
            try{
                //改变订单状态
                $where=["order_number"=>$order_number];
                $data=[
                    "order_status"=>2,
                    "is_show"=>2
                ];
                $res=$order_model->where($where)->update($data);
                if($res===false){
                    $order_model->rollback();
                    throw new \Exception("取消订单失败");
                }
                //两表联查 改变库存
                $orderInfo=model('Order')
                    ->alias('o')
                    ->field('goods_id,buy_number')
                    ->join('shop_order_detail d','d.order_id=o.order_id')
                    ->where($where)->find();
                $goodsWhere=[
                    "goods_id"=>$orderInfo['goods_id'],
                ];
                $goodsInfo=model("Goods")->where($goodsWhere)->select();
                foreach($goodsInfo as $k=>$v){
                    $goodsData=[
                        'goods_num'=>$v['goods_num']+$orderInfo['buy_number']
                    ];
                    $res2=model("Goods")->where($goodsWhere)->update($goodsData);
                }
                if($res2===false){
                    $order_model->rollback();
                    throw new \Exception("取消订单失败");
                }
                $order_model->commit();
                successly("取消订单成功");
            }catch (\Exception $e){
                fail($e->getMessage());
            }

        }

        //退款展示页面
        public function orderRefundList(){
            $order_number=input("get.order_number");
            //检测是否登陆
            if(!$this->checkUserLogin()){
                $this->error('您还没有登录，请先登录',url('Login/login'));exit;
            }
            //print_r($param);exit;
            $order_model=model('Order');
            $where=[
                'order_number'=>$order_number
            ];
            $orderInfoData=$order_model
                ->alias('o')
                ->join('shop_order_detail d','o.order_id=d.order_id')
                ->where($where)
                ->select();
            // echo $order_model->getLastSql();exit;
            // print_r(collection($orderInfoData)->toArray());exit;
            $this->assign('orderInfoData',$orderInfoData);
            return view();
        }

        public function orderRefund(){
            //接收订单id，商品id
            $order_id=input('post.order_id');
            $goods_id=input('post.goods_id');
            //获取到订单号
            $order_model=model('Order');
            $order_where=[
                'order_id'=>$order_id
            ];
            //检测退款数量、获取退款金额
            $detail_model=model('OrderDetail');
            $detail_where=[
                'order_id'=>$order_id,
                'goods_id'=>['in',$goods_id]
            ];
            $detailInfo=$detail_model->field('detail_id,self_price,buy_number')->where($detail_where)->select();
            $amount=0;
            $str='';
            foreach ($detailInfo as $k=>$v){
                $amount=$amount+$v['self_price']*$v['buy_number'];
                $str.=$v['detail_id'].',';
            }
            $str=rtrim($str,',');
            $orderInfo=$order_model->where($order_where)->find();
            $order_number=$orderInfo['order_number'];
                try{
                    $config=config('alipay_config');
                    require_once EXTEND_PATH.'alipay/pagepay/service/AlipayTradeService.php';
                    require_once EXTEND_PATH.'alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';
                    //商户订单号，商户网站订单系统中唯一订单号
                    $out_trade_no = $order_number;
                    //支付宝交易号
                    $trade_no ='';
                    //请二选一设置
                    //需要退款的金额，该金额不能大于订单金额，必填
                    $refund_amount = $amount;
                    //退款的原因说明
                    $refund_reason = '七天无理由';
                    //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
                    $out_request_no = $str;
                    //构造参数
                    $RequestBuilder=new \AlipayTradeRefundContentBuilder();
                    $RequestBuilder->setOutTradeNo($out_trade_no);
                    $RequestBuilder->setTradeNo($trade_no);
                    $RequestBuilder->setRefundAmount($refund_amount);
                    $RequestBuilder->setOutRequestNo($out_request_no);
                    $RequestBuilder->setRefundReason($refund_reason);
        
                    $aop = new \AlipayTradeService($config);
        
                    $response = $aop->Refund($RequestBuilder);
                    if($response->msg!='Success') {
                        throw new \Exception('退款失败！请联系客服后重试1');
                    }
                    $order_model->startTrans();
                    //更改订单状态
                    $order_data=[
                        'order_status'=>11,  //11 已退款
                    ];
                    $order_where=[
                        'order_id'=>$order_id
                    ];
                    $res1=$order_model->where($order_where)->update($order_data);
                    if($res1===false){
                        $order_model->rollback();
                        throw new \Exception('退款失败！请联系客服后重试2');
                    }
                    //更改订单详情状态，
                    $order_detail_model=model('OrderDetail');
                    $detail_where=[
                        'detail_id'=>['in',$str]
                    ];
                    $order_detail_data=[
                        'goods_status'=>2
                    ];
                    $res2=$order_detail_model->where($detail_where)->update($order_detail_data);
                    if(empty($res2)){
                        $order_model->rollback();
                        throw new \Exception('退款失败！请联系客服后重试3');
                    }
                    //归还库存
                    $detailWhere=[
                        'order_id'=>['in',$order_id],
                        'goods_id'=>['in',$goods_id]
                    ];
                    $orderDetailInfo=collection($this->getOrderDetailInfo($detailWhere))->toArray();
                    if(empty($orderDetailInfo)){
                        $this->error('商品数据异常！','UserOrder/orderlist');
                    }
                    $goods_model=model('Goods');
                    foreach ($orderDetailInfo as $k=>$v){
                        $goods_id=$v['goods_id'];
                        $goods_where=[
                            'goods_id'=>$goods_id
                        ];
                        $goodsInfo=$goods_model->where($goods_where)->find();
                        $goods_num=$goodsInfo['goods_num'];
                        $upInfo=[
                            'goods_id'=>$goods_id,
                            'goods_num'=>$goods_num+$v['buy_number']
                        ];
                        $res3=$goods_model->where($goods_where)->update($upInfo);
                    }
                    $order_model->commit();
                    successly('退款成功');
                }catch(\Exception $e){
                    echo $e->getMessage();
                }
        }


    }
?>