<?php
/**
 * Class MessageController
 * @name 消息管理
 */

namespace Home\Controller;

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
	    
	    $this->renderSuccess('消息列表', $list);        
	}
}
