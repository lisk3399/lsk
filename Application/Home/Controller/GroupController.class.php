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
	            $row['work_count'] = 0;
	            $work_count = count($works);
	            if($work_count > 0) {
	                $row['works'] = $works;
	                $row['work_count'] = $work_count; 
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
	 * 我加入的班级文字列表
	 */
	public function groupJoinedList() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	
	        $limit = 20;
	        $Group = M('member_group');
	        $map['mg.uid'] = $uid;
	        $map['g.is_delete'] = 0;
	        $list = $Group->alias('mg')
	        ->field('g.id,g.group_name')
	        ->join('__GROUP__ g on g.id = mg.group_id', 'left')
	        ->order('g.id desc')
	        ->where($map)->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	
	        $this->renderSuccess('我加入的班级文字列表', $list);
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
	        $map['g.is_delete'] = 0;
	        $list = $Group->alias('mg')
	        ->field('g.id,g.uid,g.group_name,cover_url')
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
	            $row['work_count'] = 0;
	            $work_count = count($works);
	            if($work_count > 0) {
	                $row['works'] = $works;
	                $row['work_count'] = $work_count;
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
	            $this->renderFailed('班级名为2-30个中文字母或数字');
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
	        $group_id = $Group->add($data);
	        
	        //@todo 保证数据一致性，使用事务
	        if($group_id) {
	            //创建班级后自己也加入班级
	            $map['uid'] = $uid;
	            $map['group_id'] = $group_id;
	            $map['create_time'] = NOW_TIME;
	            if(M('member_group')->add($map)) {
	                $this->renderSuccess('创建成功');
	            }
	            $this->renderFailed('创建失败');
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
	 * 检查某用户是否加入某班级
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
	    $map['is_delete'] = 0;
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
	    $map['is_delete'] = 0;
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
	 * 我的班级作品
	 */
	public function myGroupWork() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
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
	         
	        $list = $this->getMyGroupWorks($group_id, $uid, $page, $rows);
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	         
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	         
	        $this->renderSuccess('班级作品列表', $list);
	    }
	}
	
	/**
	 * 删除群组下的作品
	 */
	public function delGroupWork() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('您需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }  
	        $work_id = I('post.work_id', '', 'intval');
	        if(empty($work_id)) {
	            $this->renderFailed('作品id为空');
	        }
	        if(!$this->isGroupOwner($uid, $group_id)) {
	            $this->renderFailed('您不是该班级创建者，不能操作');
	        }
	        
	        $Work = M('work');
	        $data['id'] = $work_id;
	        $data['is_delete'] = 1;
	        
	        //判断作品是否属于某群组
	        $map['id']= $work_id;
	        $map['group_id'] = $group_id;
	        if(!$Work->where($map)->find()) {
	            $this->renderFailed('该作品不属于当前班级');
	        }
	        
	        if($Work->save($data)) {
	            $this->renderSuccess('删除成功');
	        }
	        $this->renderFailed('删除失败');
	    }
	}
	
	/**
	 * 获取有限个数班级作品图
	 */
	private function getLimitGroupWorks($group_id, $limit) {
	    $Work = M('work');
	    $map['group_id'] = $group_id;
	    $map['is_delete'] = 0;
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
	    $map['w.is_delete'] = 0;
	    $list = $Group->alias('g')
	    ->page($page, $rows)
	    ->field('g.group_name,w.id,ifnull(w.cover_url, "") as cover_url,w.create_time,m.nickname,m.avatar')
	    ->join('__WORK__ w on g.id = w.group_id', 'right')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'right')
	    ->order('w.id desc')
	    ->where($map)->select();

	    if(is_array($list) && count($list) > 0) {
	        foreach ($list as &$row) {
	            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
	        }
	    }
	    return $list;
	}
	
	/**
	 * 获取我的班级下的作品列表
	 */
	private function getMyGroupWorks($group_id, $uid, $page, $rows) {
	    $Group = M('group');
	    $map['g.id'] = $group_id;
	    $map['w.is_delete'] = 0;
	    $map['m.uid'] = $uid;
	    $list = $Group->alias('g')
	    ->page($page, $rows)
	    ->field('g.group_name,w.id,ifnull(w.cover_url, "") as cover_url,w.create_time,m.nickname,m.avatar')
	    ->join('__WORK__ w on g.id = w.group_id', 'right')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'right')
	    ->order('w.id desc')
	    ->where($map)->select();
	
	    if(is_array($list) && count($list) > 0) {
	        foreach ($list as &$row) {
	            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
	        }
	    }
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
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        $user_id = I('post.user_id', '', 'intval');
	        if(empty($user_id)) {
	            $this->renderFailed('未指定要添加的用户');
	        }
	        $Api = new UserApi;
	        if(!$Api->checkUidExists($user_id)) {
	            $this->renderFailed('指定的用户不存在或被禁用');
	        }
	        //当前登录用户不是班级创建者不能添加成员
	        if(!$this->isGroupOwner($uid, $group_id)) {
	            $this->renderFailed('您不是该班级创建者，不能添加成员');
	        }
	        //用户是否加入过班级
	        if($this->checkJoin($user_id, $group_id)) {
	            $this->renderFailed('您添加的用户已经加入了该班级');
	        }
	        
	        //将用户加入班级
	        $Group = M('member_group');
	        $data['uid'] = $user_id;
	        $data['group_id'] = $group_id;
	        $data['create_time'] = NOW_TIME;
	        if($Group->add($data)) {
	            $this->renderSuccess('添加成功');
	        }
	        $this->renderFailed('添加失败请稍候再试');
	    }
	}
	
	/**
	 * 是否班级创建者
	 */
	private function isGroupOwner($uid, $group_id) {
	    $Group = M('group');
	    $map['id'] = $group_id;
	    $map['uid'] = $uid;
	    $ret = $Group->where($map)->find();
	    if($ret['id']) {
	        return true;
	    }
	    return false;
	}

	/**
	 * 获取电话号码对应的注册用户
	 */
	public function getUserByPhone() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('您需要登录', -1);
	        }
	
	        $info = I('post.info', '', 'trim');
	        if(empty($info)) {
	            $this->renderFailed('缺少信息');
	        }
	        $info = str_replace("-", "", $info);
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
            
	        print_r($info);die;
	        $info = json_decode($info, true);
	        
	        //通过电话号码获取用户列表
	        $phone = array();
	        foreach ($info as $key=>&$row) {
	            array_push($phone,  $row['phoneNumber']);
	        }
	        $phone_str = implode(',', $phone);
	        $user = $this->getByPhone($phone_str);
	        
	        if(!$user) {
	            $this->renderFailed('未获取到注册用户信息');
	        }
	        
	        foreach ($user as $key=>&$row) {
	            if($row['uid']) {
	                //如果用户已经加入该班级则不显示
    	            if($this->checkJoin($row['uid'], $group_id)) {
    	                unset($user[$key]);
    	                continue;
    	            }
    	            $user[$key]['uid'] = $row['uid'];
    	            $user[$key]['nickname'] = $row['nickname'];
    	            $user[$key]['firstname'] = $info[$key]['firstName'];
	            }
	            else {
	                unset($user[$key]);
	            }
	        }

	        if(count($user) == 0) {
	            $this->renderFailed('没有更多信息');
	        }
	        
	        
	        //设置默认头像
	        $Api = new UserApi;
	        $user = $Api->setDefaultAvatar($user);

	        $this->renderSuccess('用户列表', $user);
	    }
	}
	
	/**
	 * 根据电话获取用户
	 */
	private function getByPhone($phone_str) {
	    $Member = M('ucenter_member')->alias('u');
	    $map['u.mobile'] = array('IN', $phone_str);
	    $ret = $Member->field('m.uid,m.nickname,m.avatar,u.mobile')
	    ->join('__MEMBER__ m on u.id = m.uid', 'left')
	    ->where($map)->select();
	    
	    if(is_array($ret)) {
	        return $ret;
	    }
	    return false;
	}
	
	/**
	 * 解散班级
	 */
	public function deleteGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('您需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        
	        //当前登录用户不是班级创建者不能解散
	        if(!$this->isGroupOwner($uid, $group_id)) {
	            $this->renderFailed('您不是该班级创建者，不能解散班级');
	        }
	        
	        $Group = M('group');
	        $map['id'] = $group_id;
	        $map['is_delete'] = 1;
	        if($Group->save($map)) {
	            $this->renderSuccess('解散成功');
	        }
	        $this->renderFailed('解散失败');
	    }
	}
	
	/**
	 * 退出班级
	 */
	public function quitGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('您需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	        
	        //是否加入班级判断
	        if(!$this->checkJoin($uid, $group_id)) {
	            $this->renderFailed('您未加入该班级');
	        }
	        
	        //创建者不能退出
	        if($this->isGroupOwner($uid, $group_id)){
	            $this->renderFailed('您是创建者不能退出班级');
	        }
	        
	        $Group = M('member_group');
	        $map['uid'] = $uid;
	        $map['group_id'] = $group_id;
	        
	        if($Group->where($map)->delete()) {
	            $this->renderSuccess('退出成功');
	        }
	        $this->renderFailed('退出失败，请重试');
	    }
	}

	/**
	 * 删除班级成员
	 */
	public function delGroupMember() {
	    if(IS_POST) {
	        $uid = I('post.uid', '', 'intval');
	        if(empty($uid)) {
	            $this->renderFailed('用户id为空');
	        }
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
	         
	        //是否加入班级判断
	        if(!$this->checkJoin($uid, $group_id)) {
	            $this->renderFailed('该用户不是该班级成员');
	        }
	         
	        //不能删除创建者
	        if($this->isGroupOwner($uid, $group_id)){
	            $this->renderFailed('不能删除创建者');
	        }
	         
	        $Group = M('member_group');
	        $map['uid'] = $uid;
	        $map['group_id'] = $group_id;
	         
	        if($Group->where($map)->delete()) {
	            $this->renderSuccess('删除成功');
	        }
	        $this->renderFailed('删除失败，请重试');
	    }
	}
}
