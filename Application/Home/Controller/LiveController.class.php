<?php
/**
 * Class LiveController
 * @name 直播管理
 */
 
namespace Home\Controller;


use Common\Api\LiveApi;
class LiveController extends HomeController { 
    
    //创建直播
    public function createLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录');
            }
            $title = I('title', '', 'trim');
            $stream_key = 'bipai'.$uid.'#'.NOW_TIME;
            
            $liveApi = new LiveApi();
            $stream_info = $liveApi->createStream($stream_key);
            
            if(empty($stream_info)) {
                $this->renderFailed('直播创建失败');
            }
            
            $data['uid'] = $uid;
            $data['stream_key'] = $stream_key;
            if(!empty($title)) {
                $data['title'] = $title;
            }
            $data['publish'] = $stream_info['publish'];
            $data['play'] = $stream_info['play'];
            $data['cover_url'] = $stream_info['cover_url'];
            $data['create_time'] = NOW_TIME;
            
            $liveModel = M('live');
            if(!$liveModel->add($data)) {
                $this->renderFailed('直播创建失败');
            }
            $this->renderSuccess('创建成功');
        }
    }
	
}
