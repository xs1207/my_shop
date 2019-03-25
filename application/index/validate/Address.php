<?php
    namespace app\index\validate;
    use think\Validate;
    class Address extends Validate{
        // 验证规则
        protected $rule=[
            'address_name'=>'require',
            'address_detailed'=>'require',
            'address_tel'=>'require|checkTel',
        ];
        //错误提示
        protected $message=[
            'address_name.require'=>'收货人姓名不能为空',
            'address_detailed.require'=>'详细地址不能为空',
            'address_tel.require'=>'手机号不能为空',

        ];
        // 场景验证
        protected $scene=[
            'add'=>['address_tel','address_name','address_detailed'],
            'edit'=>['address_tel','address_name','address_detailed']
        ];

        // 自定义电话
        public function checkTel($value,$rule,$data){
            $reg='/^1[3-8]\d{9}$/';
            if(!preg_match($reg,$value)){
                return "请填写正确的手机号";
            }else{
                return true;
            }
        }
    }
?>