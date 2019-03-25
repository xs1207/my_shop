<?php
    namespace app\index\model;
    use \think\Model;
    class Address extends Model{
         //定义时间戳字段名
         protected $updateTime=false;
         protected $table="shop_address";

         protected $insert=['user_id'];
         protected function setUserIdAttr(){
             return session("userInfo.user_id");
         }
         /**获取所有的收货地址 */
         public function getAddressInfo($where){
            $addressInfo=$this->where($where)->select();
            $area=model('area');
            foreach($addressInfo as $k=>$v){
                $areaWhere=[
                    'id'=>['in',[$v['address_province'],$v['address_city'],$v['address_county']]]
                ];
                $field=$area->where($areaWhere)->column('name');
                $addressInfo[$k]['address']=implode('',$field);
            }
            return $addressInfo;
         }
    }
?>