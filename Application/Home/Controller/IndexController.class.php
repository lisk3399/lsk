<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Upload\Driver\Qiniu\QiniuStorage;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

    public function index(){
    }
    
    /**
     * 七牛token
     */
    public function getToken() {
        if(IS_POST) {
            if(!is_login()) {
                $this->renderFailed('failed');
            }
            $config = array(
                'accessKey'=>'BX3FxNDH3aFGwGSb8Yue745EgiumlqGpqthQ8x1u',
                'secrectKey'=>'JqFwvXfT8TuLIb_UxyohIdnIS8oqzY-I9ifMqHyc',
                'bucket'=>'doushow',
                'domain'=>'vod.doushow.com'
            );
            $Qiniu = new QiniuStorage($config);
            $data['token'] = $Qiniu->UploadToken($config['secrectKey'], $config['accessKey'], $config);
            $this->renderSuccess('', $data);
        }
    }
}