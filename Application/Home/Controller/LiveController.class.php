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
            
            if(empty($stream_info['publish'])) {
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
                $title = $data['title'];
            }

            $data['cover_url'] = $stream_info['cover_url'];
            $data['create_time'] = NOW_TIME;
            
            $liveModel = M('live');
            $live_id = $liveModel->add($data);
            if(!$live_id) {
                $this->renderFailed('直播创建失败');
            }
            
            $this->renderSuccess('创建成功', $data);
        }
    }
	
    //获取直播列表
    public function getLiveList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
         
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        
        $liveModel = M('live');
        $list = $liveModel->field('id,uid,title,play,cover_url,comments,likes,create_time')
        ->limit($page, $rows)->select();
        
        if(count($list) == 0) {
            $this->renderFailed('获取列表失败');
        }
        
        $this->renderSuccess('直播列表', $list);
    }
    
    //获取单个直播信息
    public function getSingleLive() {
        $live_id = I('live_id', '', 'intval');
        if(empty($live_id)) {
            $this->renderFailed('直播id为空');
        }
        
        $liveModel = M('live');
        $info = $liveModel->field('id,uid,title,play,cover_url,comments,likes,create_time')
        ->where(array('id'=>$live_id))->find();
        
        if(count($info) == 0) {
            $this->renderFailed('获取信息失败');
        }
        
        $this->renderSuccess('直播信息', $info);
    }
    
    //结束直播
    public function endLive() {
        //转存直播
        $this->saveLive();
    }
    
    //转存直播
    private function saveLive() {
        $liveApi = new LiveApi();
        $liveApi->saveLive();
    }
    
    //更新直播封面
    public function updateCover() {
        
    }
}
