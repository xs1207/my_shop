<?php
    namespace app\index\controller;
    use page\AjaxPage;
    class Product extends Common{
        /**商品详情 */
        public function productDetail(){

            // 获取分类数据
            $this->getIndexCateInfo();
           
            // 商品详情
            $goods_id=input("get.goods_id",0,"intval");
            //dump($goods_id);exit;
            if(empty($goods_id)){
                fail("请选择商品");
            }
            //查询商品信息
            $where=[
                'goods_id'=>$goods_id
            ];
            $goods=model('goods');
            $goodsInfo=$goods->where($where)->find();
            $goodsInfo['goods_imgs']=explode("|",$goodsInfo['goods_imgs']);
            //print_r($goodsInfo);die;
            if(empty($goodsInfo)){
                fail("没有此商品,请重新选择");
            }
            if($goodsInfo['goods_sell']==2){
                fail('此商品已下架');
            }

            // 记录浏览记录
            if($this->checkUserLogin()){
                //存储数据库
                $this->saveHistoryDb($goods_id);
            }else{
                //存储cookie
                $this->saveHistoryCookie($goods_id);
            }
            // $history=unserialize(base64_decode(cookie('history')));
            // print_r($history);exit;
            //面包屑  导航栏
            $cate_str=$this->getCrumbs($goodsInfo['cate_id']);
            $this->assign('cate_str',$cate_str);
            $this->assign('goodsInfo',$goodsInfo);
            return view();
        }

        /**存浏览记录到cookie中 */
        public function saveHistoryCookie($goods_id){
            $now=time();
            $prevHistory=cookie('history');
            if(!empty($prevHistory)){
                //解开数组
                $history=unserialize(base64_decode($prevHistory));
                // print_r($history);die;
                //加入这一次的浏览记录
                $history[]=['goods_id'=>$goods_id,'ctime'=>$now];
                // 在存入cookie中
                $str=base64_encode(serialize($history));
                cookie("history",$str);
            }else{
                $arr[]=['goods_id'=>$goods_id,'ctime'=>$now];
                $str=base64_encode(serialize($arr));
                cookie("history",$str);
            }
            
        }
        /**存浏览记录到数据库中 */
        public function saveHistoryDb($goods_id){
            $history=[
                'user_id'=>$this->getUserId(),
                'goods_id'=>$goods_id
            ];
            $model=model('history')->save($history);
        }
        /**商品展示 */
        public function productList(){
            // 获取左侧分类数据
            $this->getIndexCateInfo();
            
            // 查询所有的品牌
            $cate_id=input('get.cate_id',0,'intval');
            if(empty($cate_id)){
                $where=[
                    'goods_sell'=>1
                ];
                cookie('cate_id',null);
            }else{
                $cateInfo=model('Category')->where(['is_show'=>1])->select();
                $cate_id=getCateId($cateInfo,$cate_id);
                $cate_Id=implode(',',$cate_id);
                cookie('cate_id',$cate_id);
                $where=[
                    'cate_id'=>['in',$cate_id],
                    'goods_sell'=>1
                ];
            }
            $brand=model('Brand');
            $brandInfo=$brand
                ->field('distinct(g.brand_id),brand_name')
                ->alias('b')
                ->join("shop_goods g","b.brand_id=g.brand_id")
                ->where($where)
                ->select();

            // 获取价格区间
            $goods=model('goods');
            $max_price=$goods->where($where)->value("max(self_price)");
            $priceInfo=$this->getPriceSection($max_price);
            
            // 获取默认商品数据
            // $goodsWhere=[
            //     'goods_sell'=>1
            // ];
            $p=1;
            $page_num=12;
            $goodsInfo=$goods
                ->where($where)
                ->order('sell_num','desc')
                ->page($p,$page_num)->select();
                // echo $goods->getLastSql();die;
            // 调用分页类 获取页码
            $count=$goods->where($where)->count();
            $page_str=AjaxPage::ajaxpager($p,$count,$page_num,url('product/productsearch'),'show');
            
            //查询浏览历史记录
            if($this->checkUserLogin()){
                $historyInfo=$this->getHistoryDb();
            }else{
                $historyInfo=$this->getHistoryCookie();
            }
            // print_r($historyInfo);die;
            $this->assign('historyInfo',$historyInfo);
            $this->assign('brandInfo',$brandInfo);
            $this->assign('priceInfo',$priceInfo);
            $this->assign('goodsInfo',$goodsInfo);
            $this->assign('page_str',$page_str);
            $this->assign('count',$count);
            return view();
        }
        /**获取价格区间 */
        public function getPriceSection($max_price){
            $price=[];
            for($i=0;$i<6;$i++){
                $start=($max_price/7)*$i;
                $end=($max_price/7)*($i+1)-0.01;
                $price[]=number_format($start,2,'.',',').'-'.number_format($end,2,'.',',');
            }
            $price[]=number_format($end+0.01,2,'.',',').'以上';
            return $price;
        }
        /**获取价格 */
        public function getPrice(){
            $brand_id=input('post.brand_id',0,'intval');
            $goods=model('goods');
            $where=[
                'goods_sell'=>1,
                'brand_id'=>$brand_id
            ];
            $max_price=$goods->where($where)->order('self_price','desc')->value('self_price');
           
            if(!empty($max_price)){
                $price=$this->getPriceSection($max_price);
                echo json_encode($price);
            }else{
                fail('此品牌下没有商品');
            }
            
        }
        /**搜索 */
        public function productSearch(){
            $p=input("post.p",1,'intval');
            $brand_id=input("post.brand_id",'');
            $flag=input("post.flag",0,'intval');
            $price=input("post.price",'');
            // dump($price);die;
            $price=str_replace(',','',$price);
            $order=input("post.order",'desc');
            $cate_id=cookie('cate_id');
            //筛选条件
            $where=[];
            if(!empty($cate_id)){
                $where['cate_id']=['in',$cate_id];
            }
            if(!empty($brand_id)){
                $where['brand_id']=$brand_id;
            }
            if(!empty($price)){
                if(substr_count($price,'-')>0){
                    $arr=explode('-',$price);
                    //print_r($arr);die;
                    $where['self_price']=['between',$arr];
                }else{
                    $asd= strpos($price,'以');
                    $qwe=substr($price,0,$asd);
                    $where['self_price']=['>=',$qwe];
                }
            }
            $field="sell_num";
            if($flag==2){
                $field="sell_num";
            }else if($flag==3){
                $field="self_price";
            }else if($flag==4){
                $where['goods_new']=1;
            }

            //查询商品
            $goods=model('goods');
            $page_num=12;

            //查询分页页码
            $goodsInfo=$goods
                ->where($where)
                ->order($field,$order)
                ->page($p,$page_num)->select();
                 //echo $goods->getLastSql();die;
            // 调用分页类 获取页码
            $count=$goods->where($where)->count();
            $page_str=AjaxPage::ajaxpager($p,$count,$page_num,url('product/productsearch'),'show');

            $this->assign('goodsInfo',$goodsInfo);
            $this->assign('page_str',$page_str);
            $this->view->engine->layout(false);
            $product=$this->fetch('product');
            $info=['product'=>$product,'count'=>$count];
            echo json_encode($info);
        }
        /**获取历史浏览记录 */
        public function getHistoryDb(){
            $where=[
                'user_id'=>$this->getUserId()
            ];
            $history=model('History');
            $goods_id=$history->where($where)->order('ctime','desc')->column('goods_id');
            if(!empty($goods_id)){
                $goods_id=array_slice(array_unique($goods_id),0,5);
                // print_r($goods_id);die;
                $goods=model('Goods');
                foreach ($goods_id as $k=>$v){
                    $where=[
                        'goods_id'=>$v,
                        'goods_sell'=>1
                    ];
                    $goodsInfo[]=$goods->field('goods_name,goods_img,goods_id,self_price,goods_score')->where($where)->find()->toArray();
                }
                return $goodsInfo;
            }else{
                return [];
            }
        }

        /**获取浏览记录---cookie */
        public function getHistoryCookie(){
            $history_str=cookie('history');
            $historyInfo=unserialize(base64_decode($history_str));
            // print_r($historyInfo);exit;
            if(!empty($historyInfo)){
                $historyInfo=array_reverse($historyInfo);
                foreach($historyInfo as $k=>$v){
                    $goods_id[]=$v['goods_id'];
                }
                $goods_id=array_slice(array_unique($goods_id),0,5);
                // print_r($goods_id);exit;
                //根据去重过后的商品id，查询对应的商品数据
                $goods=model('Goods');
                foreach ($goods_id as $k=>$v){
                    $where=[
                        'goods_id'=>$v,
                        'goods_sell'=>1
                    ];
                    $goodsInfo[]=$goods->field('goods_name,goods_id,goods_img,self_price,goods_score')->where($where)->find()->toArray();
                }
                return $goodsInfo;
            }else{
                return [];
            }
        }
    }
?>