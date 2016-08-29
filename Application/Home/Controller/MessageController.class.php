<?php
/**
 * Class MessageController
 * @name 消息管理
 */

namespace Home\Controller;

use User\Api\UserApi;
class MessageController extends HomeController {

    //某用户消息列表
	public function messageList(){
	    $uid = is_login();
	    if(!$uid) {
	        $this->renderFailed('请先登录', -1);
	    }
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '10', 'intval');
	    
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    $map['uid'] = $uid;
	    $list = M('Message')->where($map)->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    foreach ($list as &$row) {
	        $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
	        //添加班级消息
	        if($row['type'] == C('MESSAGE_TYPE.ADD_GROUP') && !empty($row['extra'])) {
	            $extra = explode('#', $row['extra']);
	            $row['member_groupid'] = $extra[1];
	        }
	    }
	    
	    $extra_info['count'] = M('Message')->where(array('type'=>'ADD_GROUP','is_read'=>'0','uid'=>$uid))->count();
	    $Api = new UserApi;
	    $list = $Api->setDefaultAvatar($list);
	    
	    $this->renderSuccess('消息列表', $list, $extra_info);        
	}

	//删除消息
	public function deleteMessage() {
	    if(IS_POST) {
    	    $uid = is_login();
    	    if(!$uid) {
    	        $this->renderFailed('请先登录', -1);
    	    }
    	    
    	    $message_id = I('post.message_id', '', 'trim');
    	    if(empty($message_id)) {
    	        $this->renderFailed('消息id为空');
    	    }
    	    if(!$this->isMyMessage($uid ,$message_id)) {
    	        $this->renderFailed('没有权限删除该消息');
    	    }
    	    
    	    if(M('Message')->where(array('id'=>$message_id))->delete()) {
    	        $this->renderSuccess('删除成功');
    	    }
    	    $this->renderFailed('删除失败，请稍后重试');
	    }
	}
	
	//是否自己的消息
	private function isMyMessage($uid, $message_id) {
	    $map['uid'] = $uid;
	    $map['id'] = $message_id;
	    return M('Message')->where($map)->find();
	}
}
