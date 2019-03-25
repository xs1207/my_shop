<?php
    namespace app\admin\controller;
    use think\Controller;
    class System extends Common{
        public function System(){
            if(checkRequest()){
                $data=input('post.');
                // dump($data);
                foreach($data as $k=>$v){
                    $info[]=['name'=>$k,'value'=>$v];
                }
                $model=model('config');
                $model->query("delete from shop_config");
                $res=$model->saveall($info);
                if($res){
                    successly('保存成功');
                }else{
                    fail('保存失败');
                }
            }else{
                $model=model('config');
                $data=collection($model->select())->toarray();
                // print_r($data);exit;
                if(!empty($data)){
                    foreach($data as $k=>$v){
                        $info[$v['name']]=$v['value'];
                    }
                    $this->assign('info',$info);
                }
                return view();
            }
            
        }
    }
?>