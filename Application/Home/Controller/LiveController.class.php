<?php
/**
 * Class LiveController
 * @name 直播管理
 */
 
namespace Home\Controller;


use Common\Api\LiveApi;
use User\Api\UserApi;
class LiveController extends HomeController { 
    
    //创建直播
    public function createLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            $title = I('title', '', 'trim');
            $stream_key = 'bipai'.$uid.'-'.NOW_TIME;
            
            $liveApi = new LiveApi();
            $stream_info = $liveApi->createStream($stream_key);
            
            if(empty($stream_info)) {
                $this->renderFailed('直播创建失败');
            }
            
            $data['stream_key'] = $stream_key;
            $data['publish'] = $stream_info['publish'];
            $data['play'] = $stream_info['play'];
            $data['uid'] = $uid;
            
            if(empty($title)) {
                $userApi = new UserApi();
                $username = $userApi->getUsername($uid);
                $data['title'] = $username.'的直播内容';
            }

            $data['cover_url'] = $stream_info['cover_url'];
            $data['hub'] = $stream_info['hub'];
            $data['publishSecurity'] = 'static';
            $data['create_time'] = NOW_TIME;
            
            $liveModel = M('live');
            $live_id = $liveModel->add($data);
            if(!$live_id) {
                $this->renderFailed('直播创建失败');
            }
            $data['id'] = $live_id;
            $data['publishKey'] = $stream_key;
            $data['publishSecurity'] = '';
            $data['disabledTill'] = 0;
            $data['disabled'] = false;
            $data['profiles'] = NUll;
            $data['hosts']['publish']['rtmp'] = $stream_info['publish_domain'];
            $data['hosts']['play']['rtmp'] = $stream_info['play_rtmp_domain'];
            $data['hosts']['live']['hdl'] = 'pili-live-hdl.bipai.tv';
            $data['hosts']['live']['hls'] = 'pili-live-hls.bipai.tv';
            $data['hosts']['live']['http'] = 'pili-media.bipai.tv';
            $data['hosts']['live']['rtmp'] = 'pili-live-rtmp.bipai.tv';
            $data['hosts']['live']['snapshot'] = 'pili-live-snapshot.bipai.tv';
            $data['hosts']['playback']['hsl'] = 'doubihai.com/index.php?m=home&c=live&a=callback';
            $data['hosts']['playback']['http'] = 'doubihai.com/index.php?m=home&c=live&a=callback';
            $data['hosts']['play']['http'] = 'pili-media.bipai.tv';
            $data['hosts']['play']['rtmp'] = 'pili-live-rtmp.bipai.tv';
            $data['createAt'] = NOW_TIME;
            $data['updatedAt'] = NOW_TIME;
            
            $this->renderSuccess('创建成功', $data);
        }
    }
	
    //获取直播列表
    public function getLiveList() {
        
    }
    
    
    public function getSingleLive() {
        
    }
    
    //结束直播转存直播数据
    public function endLive() {
        
    }
    
    public function saveLive() {
        
    }
}
