<?php
    namespace app\index\controller;
    use think\Controller;
    class UserAddress extends Common{

        /**收货地址 */
        public function address(){
            //检测是否登录
            if(!$this->checkUserLogin()){
                $this->error('请先登录',url('Login/login'));
            }
            if(checkRequest()){
                $data=input("post.");
                //dump($data);die;
                if($data['address_province']==0){
                    fail('配送地址不能为空');
                }
                if($data['address_city']==0){
                    fail('配送地址不能为空');
                }
                if($data['address_county']==0){
                    fail('配送地址不能为空');
                }
                $address=model("address");
                // 验证器
                $validate=validate('address');
                $result=$validate->scene('add')->check($data);
                if(!$result){
                    fail($validate->getError());
                }
                // 判断是否设置为默认收货地址
                if($data['is_default']=="1"){
                    $where=[
                        'user_id'=>session('userInfo.user_id'),
                    ];
                    $res1=$address->where($where)->update(['is_default'=>2]);
                }
                // 入库
                $res=$address->save($data);
                if($res){
                    successly('添加成功');
                }else{
                    fail('添加失败');
                }
            }else{
                // 查询当前用户所有的收货地址
                $where=[
                    'user_id'=>session('userInfo.user_id')
                ];
                $address=model('address');
                $addressInfo=$address->getAddressInfo($where,4);
                // print_r($addressInfo);die;
                // 获取所有省份作为下拉菜单的值
                $provinceInfo=$this->getAreaInfo(0);

                $this->assign('province',$provinceInfo);
                $this->assign('address',$addressInfo);
                return view();
            }
            
        }
        /**三级联动获取区域 */
        public function getArea(){
            $id=input("post.id");
            $info=$this->getAreaInfo($id);
            echo json_encode($info);
        }
        /**获取区域信息 */
        public function getAreaInfo($id){
            $area=model('area');
            $where=[
                'pid'=>$id
            ];
            $info=$area->where($where)->select();
            return $info;
        }
        // 删除
        public function addressDel(){
            $id=input("post.id");
            $where=[
                'address_id'=>$id
            ];
            $address=model('address');
            $res=$address->where($where)->delete();
            if($res){
                successly("删除成功");
            }else{
                fail("删除失败");
            }
        }
        //设为默认
        public function addressDefault(){
            $id=input("post.id");
            $address=model('address');
            $where=[
                'user_id'=>session('userInfo.user_id'),
            ];
            $res1=$address->where($where)->update(['is_default'=>2]);
            $changewhere=[
                'user_id'=>session('userInfo.user_id'),
                'address_id'=>$id
            ];
            $res2=$address->where($changewhere)->update(['is_default'=>1]);
            if($res2){
                successly("设置成功");
            }else{
                fail("设置失败");
            }
        }
        // 修改
        public function addressUpdate(){
            if(checkRequest()){
                // 接收数据
                $data=input("post.");
                $address_id=input('post.address_id');
                if($data['address_province']==0){
                    fail('配送地址不能为空');
                }
                if($data['address_city']==0){
                    fail('配送地址不能为空');
                }
                if($data['address_county']==0){
                    fail('配送地址不能为空');
                }
                //dump($data);die;
                $address=model("address");
                // 验证器validate验证
                $validate=validate('address');
                $result=$validate->scene('edit')->check($data);
                if(!$result){
                    fail($validate->getError());
                }
                // 判断is_default==1  就把所有的is_default==2
                if($data['is_default']=="1"){
                    $where=[
                        'user_id'=>session('userInfo.user_id'),
                    ];
                    $res1=$address->where($where)->update(['is_default'=>2]);
                }
                // 修改
                $res=$address->update($data,$address_id);
                if($res===false){
                    fail('修改失败');
                }else{
                    successly('修改成功');
                }
            }else{
                $id=input('get.id',0,'intval');
                if(empty($id)){
                    fail("非法操作");
                }
                $where=[
                    'address_id'=>$id
                ];
                // 查询id所对应的所有信息
                $addressInfo=model('address')->where($where)->find()->toArray();
                // 查询所有的省份
                $provinceInfo=$this->getAreaInfo(0);

                // 查询所有的市
                $cityInfo=$this->getAreaInfo($addressInfo['address_province']);
                // 查询所有的区
                $countyInfo=$this->getAreaInfo($addressInfo['address_city']);
                $this->assign('province',$provinceInfo);
                $this->assign('city',$cityInfo);
                $this->assign('county',$countyInfo);
                $this->assign('addressInfo',$addressInfo);
                return view();
            }
        }
    }
?>