<?php
/**
 * Class GroupController
 * @name 群组管理/班级管理
 */

namespace Home\Controller;

use User\Api\UserApi;
class GroupController extends HomeController {
    
	/**
	 * 我创建的群组
	 */
	public function myGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	        
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $Group = M('group');
	        $map['g.uid'] = $uid;
	        $list = $Group->alias('g')
	        ->page($page, $rows)
	        ->field('g.id,g.uid,g.group_name,ifnull(w.cover_url, "") as cover_url')
	        ->join('__WORK__ w on g.id = w.group_id', 'left')
	        ->where($map)->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	        
	        $this->renderSuccess($list);
	    }
	}
	
	/**
	 * 我加入的群组
	 */
	public function groupJoined(){
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	         
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	         
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	         
	        $Group = M('member_group');
	        $map['mg.uid'] = $uid;
	        $list = $Group->alias('mg')
	        ->page($page, $rows)
	        ->field('g.id,g.uid,g.group_name,ifnull(w.cover_url, "") as cover_url')
	        ->join('__GROUP__ g on g.id = mg.group_id', 'left')
	        ->join('__WORK__ w on mg.id = w.group_id', 'left')
	        ->where($map)->select();
	         
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	         
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	         
	        $this->renderSuccess($list);
	    }
	}
	
	/**
	 * 加入群组
	 */
	public function joinGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        //判断班级id是否存在
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        //是否已经加入
	        if($this->checkJoin($uid, $group_id)){
	            $this->renderFailed('您已经加入该班级');
	        }
	        $data['uid'] = $uid;
	        $data['group_id'] = $group_id;
	        $data['create_time'] = NOW_TIME;
	        if(M('member_group')->add($data)) {
	            $this->renderSuccess('加入成功');
	        }
	        $this->renderFailed('加入失败，请稍后再试');
	    }
	}
	
	/**
	 * 创建群组
	 */
	public function createGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        $group_name = I('post.group_name', '', 'trim');
	        if(empty($group_name)) {
	            $this->renderFailed('班级名为空');
	        }
	        //创建班级字符限制
	        if(!preg_match('/^[0-9a-zA-Z\x{4e00}-\x{9fa5}]{2,30}$/u', $group_name)) {
	            $this->renderFailed('班级名只能输入长度为2-30');
	        }
	        //群组名是否存在
	        if($this->checkGroupExists($group_name)) {
	            $this->renderFailed('已存在该班级名');
	        }
	        //创建群组数量限制
	        if($this->checkGroupNum($uid) >= 10) {
	            $this->renderFailed('您最多只能创建10个班级');
	        }
	        $data['uid'] = $uid;
	        $data['group_name'] = $group_name;
	        $data['is_delete'] = 0;
	        $data['create_time'] = NOW_TIME;
	        
	        $Group = M('group');
	        if($Group->add($data)) {
	            $this->renderSuccess('创建成功');
	        }
	        $this->renderFailed('创建失败');
	    }
	}
	
	/**
	 * 编辑群组信息
	 */
	public function editGroup() {
	    
	}
	
	/**
	 * 检查是否加入某班级
	 */
	private function checkJoin($uid, $group_id) {
	    $Group = M('member_group');
	    $map['uid'] = $uid;
	    $map['group_id'] = $group_id;
	    return $Group->where($map)->find();
	}
	
	/**
	 * 检查群组是否存在
	 */
	private function checkGroupidExists($group_id) {
	    $Group = M('group');
	    $map['id'] = $group_id;
	    return $Group->where($map)->find();
	}
	
	/**
	 * 检查群组名是否存在
	 */
	private function checkGroupExists($group_name) {
	    $Group = M('group');
	    $map['group_name'] = $group_name;
	    return $Group->where($map)->find();
	}
	
	/**
	 * 检查用户创建班级数量
	 */
	private function checkGroupNum($uid) {
	    $Group = M('group');
	    $map['uid'] = $uid;
	    return $Group->where($map)->count();
	}
	/**
	 * 搜索群组
	 */
	public function searchGroup() {
	    
	}
	
	/**
	 * 某群组下的作品列表
	 */
	public function groupWorks($group_id) {
	    
	}
	
	/**
	 * 某群组下的成员
	 */
	public function groupMembers($group_id) {
	    
	}
	
}
