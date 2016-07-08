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
	        
            $limit = 2;
            
	        $Group = M('group');
	        $map['uid'] = $uid;
	        $map['is_delete'] = 0;
	        $list = $Group->field('id,uid,group_name,cover_url')
	        ->where($map)->order('id desc')->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        //追加作品信息至班级
	        foreach ($list as &$row) {
	            $works = $this->getLimitGroupWorks($row['id'], $limit);
	            $row['works'] = '';
	            if(count($works) > 0) {
	                $row['works'] = $works;
	            }
	        }
	        
	        $this->renderSuccess('我创建的班级', $list);
	    }
	}
	
	/**
	 * 我创建的班级文字列表
	 */
	public function myGroupList() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	         
	        $limit = 10;
	        $Group = M('group');
	        $map['uid'] = $uid;
	        $map['is_delete'] = 0;
	        $list = $Group->field('id,uid,group_name')->limit($limit)
	        ->where($map)->order('id desc')->select();
	         
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	         
	        $this->renderSuccess('班级文字列表', $list);
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
	        
            $limit = 2;
	         
	        $Group = M('member_group');
	        $map['mg.uid'] = $uid;
	        $list = $Group->alias('mg')
	        ->field('g.id,g.uid,g.group_name')
	        ->join('__GROUP__ g on g.id = mg.group_id', 'left')
	        ->order('g.id desc')
	        ->where($map)->select();
	         
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        //追加作品信息至班级
	        foreach ($list as &$row) {
	            $works = $this->getLimitGroupWorks($row['id'], $limit);
	            $row['works'] = '';
	            if(count($works) > 0) {
	                $row['works'] = $works;
	            }
	        }
	         
	        $this->renderSuccess('我加入的群组', $list);
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
	        $cover_url = I('post.cover_url', '', 'trim');
	        if(empty($cover_url)) {
	            $this->renderFailed('班级图片为空');
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
	        $data['cover_url'] = $cover_url;
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
	 * 班级信息
	 */
	public function groupInfo() {
	    if(IS_POST) {
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        
	        $Group = M('group');
	        $map['id'] = $group_id;
	        $info = $Group->where($map)->find();
	        
	        $this->renderSuccess('班级信息', $info);
	    }
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
	    if(IS_POST) {
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	        
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $group_name = I('post.group_name', '', 'trim');
	        if(empty($group_name)) {
	            $this->renderFailed('班级名为空');
	        }
	        $Group = M('group');
	        $map['is_delete'] = 0;
	        $map['group_name'] = array('LIKE', '%'.$group_name.'%');
	        $list = $Group->page($page, $rows)
	        ->field('id,group_name')
	        ->where($map)
	        ->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        $this->renderSuccess('查询结果', $list);
	    }
	}
	
	/**
	 * 某群组下的作品列表
	 */
	public function groupWorks() {
	    if(IS_POST) {
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	         
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        
	        $list = $this->getGroupWorks($group_id, $page, $rows);
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	        
	        $this->renderSuccess('班级作品列表', $list);
	    }
	}
	
	/**
	 * 获取有限个数班级作品图
	 */
	private function getLimitGroupWorks($group_id, $limit) {
	    $Work = M('work');
	    $map['group_id'] = $group_id;
	    $list = $Work
	    ->field('id,ifnull(cover_url, "") as cover_url')
	    ->order('id desc')
	    ->limit($limit)
	    ->where($map)->select();
	         
	    return $list;
	}
	
	/**
	 * 获取某群组下的作品列表
	 */
	private function getGroupWorks($group_id, $page, $rows) {
	    $Group = M('group');
	    $map['g.id'] = $group_id;
	    $list = $Group->alias('g')
	    ->page($page, $rows)
	    ->field('g.group_name,w.id,ifnull(w.cover_url, "") as cover_url,m.nickname,m.avatar')
	    ->join('__WORK__ w on g.id = w.group_id', 'right')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'right')
	    ->order('w.id desc')
	    ->where($map)->select();
	    
	    return $list;
	}
	
	/**
	 * 某群组下的成员
	 */
	public function groupMembers() {
	    if(IS_POST) {
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	        
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	         
	        $list = $this->getGroupMembers($group_id, $page, $rows);
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	        
	        $this->renderSuccess('班级成员列表', $list);
	    }
	}
	
	/**
	 * 获取群组下的成员 
	 */
	private function getGroupMembers($group_id, $page, $rows) {
	    $Group = M('member_group');
	    $map['mg.group_id'] = $group_id;
	    $list = $Group->alias('mg')
	    ->page($page, $rows)
	    ->field('mg.uid,m.nickname,m.avatar')
	    ->join('__MEMBER__ m on mg.uid = m.uid', 'left')
	    ->order('mg.id desc')
	    ->where($map)->select();
	    
	    return $list;
	}
	
	/**
	 * 添加群组成员
	 */
	public function addGroupMembers() {
	    
	}
	
}
