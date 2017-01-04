<?php
/**
 * Class LiveController
 * @name 直播管理
 */
 
namespace Home\Controller;


use Common\Api\LiveApi;
use User\Api\UserApi;
class LiveController extends HomeController { 
    
    const LIVE_STATUS_ON = 1;//直播状态：正在直播
    const LIVE_STATUS_OFF = 2;//直播状态：关闭(点播)
    
    //创建直播
    public function createLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            $title = I('title', '', 'trim');
            $group_id = I('group_id', '', 'intval');
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
            if(!empty($group_id)) {
                $data['group_id'] = $group_id;
            }
            
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
            
            $data['id'] = $live_id;
            $this->renderSuccess('创建成功', $data);
        }
    }
    
    //获取直播列表
    public function getList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
         
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        
        $map = array();
        $group_id = I('group_id', '1', 'intval');
        if(!empty($group_id)) {
            $map['group_id'] = $group_id;
        }
        
        $liveModel = M('live');
        $list = $liveModel->field('id,uid,title,play,cover_url,comments,likes,create_time')
        ->where($map)
        ->limit($page, $rows)->select();
        
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $this->renderSuccess('直播列表', $list);
    }
    
    //获取单个直播信息
    public function getSingle() {
        $live_id = I('live_id', '', 'intval');
        if(empty($live_id)) {
            $this->renderFailed('直播id为空');
        }
        
        $liveModel = M('live');
        $info = $liveModel->field('id,uid,title,play,cover_url,comments,likes,create_time')
        ->where(array('id'=>$live_id))->find();
        
        if(count($info) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $this->renderSuccess('直播信息', $info);
    }
    
    //结束直播
    public function endLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
        
            $live_id = I('live_id', '', 'intval');
            if(empty($live_id)) {
                $this->renderFailed('直播id为空');
            }
            
            $liveModel = M('live');
            $live_info = $liveModel->field('stream_key')->where(array('id'=>$live_id))->find();
            $stream_key = $live_info['stream_key'];
            if(!empty($stream_key)) {
                //失败最多尝试5次
                $try = 0;
                do {
                    if ($try == 5) {
                        break;
                    }
                    $ret = $this->saveLive($stream_key, 0, 0);
                    $try ++;
                } while(!$ret['fname']);

                if($ret['fname']) {
                    $data['status'] = 2;//点播
                    $data['update_time'] = NOW_TIME;
                    $data['play'] = C('QINIU.live_storage').'/'.$ret['fname'];
                    $liveModel->where(array('stream_key'=>$stream_key))->save($data);
                    $this->renderSuccess('保存成功');
                }
                $this->renderFailed('保存失败');
            }
            
        }
    }
    
    //转存直播
    private function saveLive($stream_key, $create_time, $end_time) {
        $liveApi = new LiveApi();
        return $liveApi->saveLive($stream_key, $create_time, $end_time);
    }
    
    //更新直播封面
    public function updateCover() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            
            $live_id = I('live_id', '', 'intval');
            if(empty($live_id)) {
                $this->renderFailed('直播id为空');
            }
            $cover_url = I('cover_url', '', 'trim');
            if(!empty($cover_url)) {
                $data['cover_url'] = $cover_url;
                $ret = M('live')->where(array('id'=>$live_id))->save($data);
                if(!$ret) {
                    $this->renderFailed('更新失败');
                }
            }
            $this->renderSuccess('更新成功');
        }
    }
    
    //创建班级直播
    public function createGroupLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            $title = I('title', '', 'trim');
            $group_id = I('group_id', '', 'intval');
            if(empty($group_id)) {
                $this->renderFailed('班级id异常');
            }
            $stream_key = 'bipai'.$uid.'-'.NOW_TIME;
            $liveApi = new LiveApi();
            $stream_info = $liveApi->createStream($stream_key);
            if(empty($stream_info['publish'])) {
                $this->renderFailed('直播创建失败');
            }
            
            $userApi = new UserApi();
            $userinfo = $userApi->info($uid);
            $data['title'] = '快来看看我的直播';
            if(!empty($title)) {
                $data['title'] = $title;
            }

            //添加内容
            $contentModel = M('content');
            $data['uid'] = $uid;
            $data['group_id'] = $group_id;
            $data['create_time'] = NOW_TIME;
            $content_id = $contentModel->add($data);
            if(!$content_id) {
                $this->renderFailed('直播添加失败');
            }
            //添加附件
            $play_url = $stream_info['play']; //播放地址
            $cover_url = $stream_info['cover_url'];
            $cmModel = M('content_material');
            $arr[0]['type'] = 'LIVE';
            $arr[0]['value'] = $play_url;
            $arr[0]['cover_url'] = $cover_url;
            $arr[0]['status'] = self::LIVE_STATUS_ON;
            $content_json = json_encode($arr);
            $cm_data['content_id'] = $content_id;
            $cm_data['content_json'] = $content_json;
            $cm_data['type'] = 'LIVE';
            $cm_data['value'] = $stream_key;
            $cm_data['create_time'] = NOW_TIME;
            
            if(!$cmModel->add($cm_data)) {
                $this->renderFailed('创建失败');
            }
        
            $data['publish'] = $stream_info['publish'];
            $data['nickname'] = $userinfo['nickname'];
            $data['avatar'] = $userinfo['avatar'];
            $data['id'] = $content_id;
            
            $this->renderSuccess('创建成功', $data);
        }
    }
    
    //更新班级直播封面
    public function updateGroupCover() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
        
            $id = I('id', '', 'intval');
            if(empty($id)) {
                $this->renderFailed('动态id错误');
            }
            $cover_url = I('cover_url', '', 'trim');
            if(empty($cover_url)) {
                $this->renderFailed('没有封面图');
            }
            $data['cover_url'] = $cover_url;
            $cmModel = M('content_material');
            
            $info = $cmModel->field('content_json')->where(array('content_id'=>$id))->find();
            if(empty($info['content_json'])) {
                $this->renderFailed('无法更新');
            }
            
            $arr = json_decode($info['content_json'], TRUE);
            $arr[0]['cover_url'] = $cover_url;
            $content_json = json_encode($arr);
            
            $map['content_json'] = $content_json;
            if(!$cmModel->where(array('content_id'=>$id))->save($map)) {
                $this->renderFailed('更新失败');
            }
            $this->renderSuccess('更新成功');
        }
    }
    
    //结束班级直播
    public function endGroupLive() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            
            $id = I('id', '', 'intval');
            if(empty($id)) {
                $this->renderFailed('动态id错误');
            }
        
            $cmModel = M('content_material');
            $info = $cmModel->field('value,content_json')->where(array('content_id'=>$id))->find();
            if(empty($info['content_json']) || empty($info['value'])) {
                $this->renderFailed('无法更新');
            }
            $content_json = $info['content_json'];
            $stream_key = $info['value'];
            //失败最多尝试5次
            $try = 0;
            do {
                if ($try == 5) {
                    break;
                }
                $ret = $this->saveLive($stream_key, 0, 0);
                $try ++;
            } while(!$ret['fname']);
            
            if(!$ret['fname']) {
                $this->renderFailed('保存失败');
            }
            
            $play_url = C('QINIU.live_storage').'/'.$ret['fname'];
            $arr = json_decode($content_json, TRUE);
            $arr[0]['value'] = $play_url;
            $arr[0]['status'] = self::LIVE_STATUS_OFF;
            
            $content_json = json_encode($arr);
            $ret = $cmModel->where(array('content_id'=>$id))->save(array('content_json'=>$content_json));
            if(!$ret) {
                $this->renderFailed('保存失败');
            }
            
            $this->renderSuccess('保存成功');
        }
    }
    
    //回调函数，用于结束直播流将直播状态改为点播
    public function callback() {
        //获取回调的body信息
        $callbackBody = file_get_contents('php://input');
        \Think\Log::write($callbackBody);
        //$data = get_nginx_headers();
        //$dat=$data['X-Pili-Md5'];
        //file_put_contents("dat.txt","callbackBody=".$callbackBody."md5=".$dat);
        //本地签名
        //$dataSign = str_replace(array('+', '/'), array('-', '_'), base64_encode(md5($callbackBody, true)));
        //if ($dataSign == $dat) {
            $data = json_decode($callbackBody, TRUE);
            if($data['data']['status'] == 'disconnected') {
                
                //file_put_contents("/mnt/xvdb1/virtualhost/log/da.txt", $callbackBody);
                $cmModel = M('content_material');
                $stream_key = $data['data']['id'];
                $map['type'] = 'LIVE';
                $map['value'] = $stream_key;
                $info = $cmModel->field('content_json')->where($map)->find();
                if(empty($info['content_json'])) {
                    $this->renderFailed('无法更新');
                }
                
                $content_json = $info['content_json'];
                //失败最多尝试5次
                $try = 0;
                do {
                    if ($try == 5) {
                        break;
                    }
                    $ret = $this->saveLive($stream_key, 0, 0);
                    $try ++;
                } while(!$ret['fname']);
                
                if(!$ret['fname']) {
                    $this->renderFailed('保存失败');
                }
                
                $play_url = C('QINIU.live_storage').'/'.$ret['fname'];
                $arr = json_decode($content_json, TRUE);
                $arr[0]['value'] = $play_url;
                $arr[0]['status'] = self::LIVE_STATUS_OFF;
                
                $content_json = json_encode($arr);
                $ret = $cmModel->where($map)->save(array('content_json'=>$content_json));
                if(!$ret) {
                    $this->renderFailed('保存失败');
                }
                $this->renderSuccess('保存成功');
            }
        //}
    }
    
    //查询流状态
    public function getStreamStatus() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('无权限', -1);
            }
            
            $id = I('id', '', 'intval');
            if(empty($id)) {
                $this->renderFailed('动态id错误');
            }
            
            $cmModel = M('content_material');
            $info = $cmModel->field('value,content_json')->where(array('content_id'=>$id))->find();
            if(empty($info['value'])) {
                $this->renderFailed('直播结束了');
            }
            
            $stream_key = $info['value'];
            $liveApi = new LiveApi();
            if(!$liveApi->getStreamStatus($stream_key)) {
                $this->renderFailed('直播结束');
            }
            $this->renderSuccess('正在直播');
        }
    }
}
