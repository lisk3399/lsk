<?php
/**
 * @todo 上线时间短来不及，重复函数，需要统一整合调用
 * Class GroupController
 * @name 群组管理/班级管理
 */

namespace Home\Controller;

use User\Api\UserApi;
class GroupController extends HomeController {
    
    const ORG_ADMIN = 1;
    const USER_JOINED_GROUP = 2;
    const USER_NOT_JOINED_GROUP = 3;
    
    //热门群组(固定的几个群组)
    public function hotGroup() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
         
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        
        $Group = M('group');
        $map['is_delete'] = 0;
        $map['id'] = array('IN', '171,172,173,174');
        $list = $Group->field('id,uid,group_name,cover_url')
        ->where($map)->order('id desc')->select();
         
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $this->renderSuccess('我加入的班级', $list);
    }
    
    //我加入的群组(某个机构)
    public function myJoinedGroup() {
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
            
            $map['mg.uid'] = $uid;
            $map['mg.status'] = 1;
            $map['g.is_delete'] = 0;
            $map['g.uid'] = array('NEQ', $uid);
            //指定机构下的班级
            $org_id = I('post.org_id', '', 'intval');
            if(!empty($org_id)) {
                $map['g.org_id'] = $org_id;
            }
            
            $Mg = M('member_group')->alias('mg');
            $list = $Mg->field('g.id,g.uid,g.group_name,g.cover_url,mg.group_id')
            ->join('__GROUP__ g on g.id = mg.group_id', 'left')
            ->where($map)->order('g.id desc')->select();

            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
        
            $this->renderSuccess('我加入的班级', $list);
        }
    }
    
    //当前机构未加入班级列表
    public function groupNotJoined() {
        if(IS_POST) {
            $uid = is_login();
            if(empty($uid)) {
                $this->renderFailed('需要登录', -1);
            }

            //指定机构下的班级
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            //查找机构下的所有班级id
            $Group = M('group');
            $map['org_id'] = $org_id;
            $map['is_delete'] = 0;
            $id_arr = $Group->field('id')->where($map)->select();
            if(count($id_arr) == 0) {
                $this->renderFailed('该机构下还没创建班级');
            }
            
            $group_ids = array();
            foreach ($id_arr as $row) {
                $group_ids[] = $row['id'];
            }
            
            //查找已经加入的班级
            $map['g.org_id'] = $org_id;
            $map['mg.status'] = 1;
            $map['g.is_delete'] = 0;
            $map['mg.uid'] = $uid;
            $map['g.uid'] = array('NEQ', $uid);
            
            $Mg = M('member_group')->alias('mg');
            $group_joined_arr = $Mg->field('g.id')
            ->join('__GROUP__ g on g.id = mg.group_id', 'left')
            ->where($map)->select();
            
            //如果已经有加入班级
            if(count($group_joined_arr) != 0) {
                $group_joined_ids = array();
                foreach ($group_joined_arr as $row) {
                    $group_joined_ids[] = $row['id'];
                }
                
                //所有班级排除已经加入的班级
                $group_ids = array_diff($group_ids, $group_joined_ids);
            }
            
            if(count($group_ids) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            $map = array();
            $map['is_delete'] = 0;
            $map['id'] = array('in', $group_ids);
            $list = $Group->field('id,group_name,description,cover_url')->where($map)->select();
            
            $this->renderSuccess('当前机构还未加入的班级列表', $list);
        }
    }
    
	/**
	 * 我创建的群组
	 */
	public function myGroup() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
	        //指定机构下的班级
	        $org_id = I('post.org_id', '', 'intval');
	        if(!empty($org_id)) {
	            $map['org_id'] = $org_id;
	        }
	        
	        $Group = M('group');
	        $map['uid'] = $uid;
	        $map['is_delete'] = 0;
	        $list = $Group->field('id,uid,group_name,cover_url')
	        ->where($map)->order('id desc')->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
// 	        //追加作品信息至班级
// 	        foreach ($list as &$row) {
// 	            $works = $this->getLimitGroupWorks($row['id'], $limit);
// 	            $row['works'] = '';
// 	            $row['work_count'] = 0;
// 	            $work_count = count($works);
// 	            if($work_count > 0) {
// 	                $row['works'] = $works;
// 	                $row['work_count'] = $work_count; 
// 	            }
// 	        }
	        
	        $this->renderSuccess('我创建的班级', $list);
	    }
	}
	
	/**
	 * 我创建的班级文字列表（弃用）
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
	 * 我加入的群组（弃用）
	 */
	public function groupJoined(){
	    if(IS_POST) {
	        $uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
            $limit = 2;
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $Mg = M('member_group');
            $mg_arr = $Mg->field('group_id')->where(array('uid'=>$uid, 'status'=>1))->select();
            $group_ids = array();
            foreach ($mg_arr as $row) {
                $group_ids[] = $row['group_id'];
            }
            
            $map['is_delete'] = 0;
            $map['id'] = array('IN', $group_ids);
            $map['uid'] = array('NEQ', $uid);
            $Group = M('group');
            $list = $Group->field('id,uid,group_name,cover_url')
            ->page($page, $rows)
            ->where($map)
            ->order('id desc')
            ->select();
            
// 	        $Group = M('member_group');
// 	        $map['mg.uid'] = $uid;
// 	        $map['g.is_delete'] = 0;
// 	        $map['g.uid'] = array('NEQ', $uid);
// 	        $map['mg.status'] = 1;
// 	        $list = $Group->alias('mg')
// 	        ->field('g.id,g.uid,g.group_name,cover_url')
// 	        ->join('__GROUP__ g on g.id = mg.group_id', 'left')
// 	        ->order('g.id desc')
// 	        ->where($map)->select();
	        
//             echo $Group->getLastSql();die;
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
	            $this->renderFailed('您已经申请过加入或已经加入该班级');
	        }
	        $groupInfo = M('group')->field('org_id')->where(array('id'=>$group_id))->find();
	        if(empty($groupInfo['org_id'])) {
	            $this->renderFailed('该班级不属于任何机构，请通知管理员补充机构信息');
	        }
	        
	        $from = I('post.from', '', 'trim');
	        
	        $data['uid'] = $uid;
	        $data['group_id'] = $group_id;
	        $data['create_time'] = NOW_TIME;
	        $data['status'] = 0;//待审核
	        $member_groupid = M('member_group')->add($data);
	        if($member_groupid) {
	            
	            //给班级管理员发送消息通知
	            $group_info = $this->getGroupInfo($group_id);
	            $group_ownerid = $group_info['uid'];
	            $extra_info['group_name'] = $group_info['group_name'];
	            $extra_info['uid'] = $uid;
	            $extra_info['member_groupid'] = $member_groupid;
	            $Api = new UserApi;
	            $Api->sendMessage($group_ownerid, C('MESSAGE_TYPE.ADD_GROUP'), $extra_info);
// 	            //非二维码扫码加班级提示
// 	            if($from != 'qrcode') {
// 	                $this->renderFailed('您的加入班级申请已经发送给管理员');
// 	            }
	            $this->renderFailed('加入申请已经发给管理员');
	        }
	        $this->renderFailed('加入失败，请稍后再试');
	    }
	}
	
	/**
	 * 管理员确认审核加入班级
	 */
	public function confirmJoinGroup() {
	    if(IS_POST) {
	        $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录');
            }
            
            $member_groupid = I('post.member_groupid', '', 'intval');
            if(empty($member_groupid)) {
                $this->renderFailed('申请id不存在');
            }
            $message_id = I('post.message_id', '', 'intval');
            if(empty($message_id)) {
                $this->renderFailed('消息id不存在');
            }
            //获取申请人uid
            $mg = M('member_group')->field('uid,group_id')->where(array('id'=>$member_groupid))->find();
            $apply_uid = $mg['uid'];
            $group_id = $mg['group_id'];
            
            //判断班级是否属于机构
            $groupInfo = M('group')->field('org_id')->where(array('id'=>$group_id))->find();
            if(empty($groupInfo['org_id'])) {
                $this->renderFailed('该班级不属于任何机构，请通知管理员补充机构信息');
            }
            //如果申请人不在班级所在机构，将用户加入该机构
            $orgController = new OrgnizationController();
            if(!$orgController->isJoinOrg($apply_uid, $groupInfo['org_id'])) {
                $data['uid'] = $apply_uid;
                $data['org_id'] = $groupInfo['org_id'];
                $data['create_time'] = NOW_TIME;
                M('member_org')->add($data);
            }
            
            //通过用户申请
            $map['id'] = $member_groupid;
            if(M('member_group')->data(array('status'=>1))->where($map)->save()) {
                $extra_info['content'] = '管理员同意了您加入班级申请';
                $Api = new UserApi;
                //给申请人发消息
                $Api->sendMessage($apply_uid, C('MESSAGE_TYPE.SYSTEM'), $extra_info);
                
//                 //发送优惠券
//                 $extra_info['content'] = '恭喜你获得优惠券，点击查看'; 
//                 $extra_info['extra'] = 'html5地址';
//                 $Api->sendMessage($apply_uid, C('MESSAGE_TYPE.WAP'), $extra_info);
                
                //设置已读
                M('Message')->data(array('is_read'=>1))->where(array('id'=>$message_id))->save();
                
                $this->renderSuccess('审核通过');
            }
            $this->renderFailed('审核失败');
	    }
	}
	
	//获取班级信息
	private function getGroupInfo($group_id) {
	    $Group = M('group');
	    $map['id'] = $group_id;
	    return $info = $Group->where($map)->find();
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
	        $title_len = mb_strlen($group_name, 'utf-8');
	        if($title_len>30 || $title_len<=2) {
	            $this->renderFailed('班级名为2-30个字');
	        }
	        //群组名是否存在
	        if($this->checkGroupExists($group_name)) {
	            $this->renderFailed('已存在该班级名');
	        }
	        //创建群组数量限制
	        if($uid != 246) { //官方帐号可以无限制创建
    	        if($this->checkGroupNum($uid) >= 10) {
    	            $this->renderFailed('您最多只能创建10个班级');
    	        }
	        }
	        
	        //创建班级必须指定机构
	        $org_id = I('post.org_id', '', 'intval');
	        if(empty($org_id)) {
                $this->renderFailed('需要先指定机构');
	        }
	        $org_con = new OrgnizationController();
	        if(!$org_con->checkOrgIdExists($org_id)) {
	            $this->renderFailed('该机构不存在');
	        }
	        
	        $data['org_id'] = $org_id;
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
	            $map['status'] = 1;
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
	    if(IS_POST) {
	    	$uid = is_login();
	        if(empty($uid)) {
	            $this->renderFailed('需要登录', -1);
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->isGroupOwner($uid, $group_id)) {
	            $this->renderFailed('不是自己的群组不能编辑');
	        }
	        
	        $map['id'] = $group_id;
	        $group_name = I('post.group_name', '', 'trim');
	        if(!empty($group_name)) {
	            $data['group_name'] = $group_name;
	        }
	        
	        $description = I('post.description', '', 'trim');
	        if(!empty($description)) {
	            $data['description'] = $description;
	        }
	        $cover_url = I('post.cover_url', '', 'trim');
	        if(!empty($cover_url)) {
	            $data['cover_url'] = $cover_url;
	        }
	        $background_url = I('post.background_url', '', 'trim');
	        if(!empty($background_url)) {
	            $data['background_url'] = $background_url;
	        }
	        
	        if(M('group')->where($map)->save($data)) {
	            $this->renderSuccess('更新成功');
	        }
	        $this->renderFailed('没有更新');
	    }
	}
	
	//班级首页信息
	public function groupIndexInfo() {
	    $group_id = I('group_id', '', 'intval');
	    if(empty($group_id)) {
	        $this->renderFailed('班级为空');
	    }
	    if(!$this->checkGroupidExists($group_id)) {
	        $this->renderFailed('班级不存在');
	    }
	    
	    $member_num = '';
	    
	    $Group = M('group');
	    $map['id'] = $group_id;
	    $info = $Group->field('id,org_id,group_name,description,cover_url,background_url')->where($map)->find();
	    
	    $info['member_num'] = M('member_group')->where(array('group_id'=>$group_id, 'status'=>'1'))->count();
	    $info['content_num'] = M('content')->where(array('group_id'=>$group_id, 'status'=>1))->count();
	    $back_arr = array('/Public/static/app/group_cover_url1.jpg', '/Public/static/app/group_cover_url2.jpg', '/Public/static/app/group_cover_url3.jpg');
	    $i = rand(0,2);
	    $info['background_url'] = !empty($info['background_url']) ? $info['background_url'] : C('WEBSITE_URL').$back_arr[$i];
	    
	    if($group_id == 178) {
	        $info['member_num'] = $info['member_num'] + 80;
	        $info['content_num'] = $info['content_num'] + 660;
	    }
	    
	    //是否管理员
	    $uid = is_login();
	    $info['is_admin'] = 0;
	    if($uid) {
	        $Org = new OrgnizationController();
	        //是否机构管理员或者班级创建者
	        if($Org->isOrgAdmin($uid, $info['org_id']) || $this->isGroupOwner($uid, $group_id)) {
	            $info['is_admin'] = self::ORG_ADMIN;
	        }
    	    else {
    	        //是否加入班级,历史问题
    	        if($this->checkJoin($uid, $group_id)) {
    	            $info['is_admin'] = self::USER_JOINED_GROUP;
    	        } else {
    	            $info['is_admin'] = self::USER_NOT_JOINED_GROUP;
    	        }
    	    }
	    }
	    
	    $this->renderSuccess('班级信息', $info);
	}
	
	/**
	 * 班级信息(弃用)
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
	public function checkJoin($uid, $group_id) {
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
	        $rows = I('rows', '10', 'intval');
	        
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $group_name = I('post.group_name', '', 'trim');
	        if(empty($group_name)) {
	            $this->renderFailed('班级名为空');
	        }
	        
	        //搜索机构下班级
	        $org_id = I('post.org_id', '', 'intval');
	        if(!empty($org_id)) {
	            $map['org_id'] = $org_id;
	        }
	        
	        $Group = M('group');
	        $map['is_delete'] = 0;
	        $map['group_name'] = array('LIKE', '%'.$group_name.'%');
	        $list = $Group->alias('g')->page($page, $rows)
	        ->join('__MEMBER__ m on m.uid = g.uid', 'left')
	        ->field('g.id,g.group_name,g.cover_url,m.nickname')
	        ->where($map)
	        ->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        
	        foreach ($list as &$row) {
                $group_stat = $this->groupStat($row['id']);
                $row['work_num'] = intval($group_stat['work_num']);
                $row['member_num'] = intval($group_stat['member_num']);
	        }
	        
// 	        //追加作品信息至班级
// 	        $limit = 2;
// 	        foreach ($list as &$row) {
// 	            $works = $this->getLimitGroupWorks($row['id'], $limit);
// 	            $row['works'] = '';
// 	            $work_count = count($works);
// 	            if($work_count > 0) {
// 	                $row['works'] = $works;
// 	            } else {
// 	                unset($row['works']);
// 	            }
// 	        }
	        
	        $this->renderSuccess('查询结果', $list);
	    }
	}
	
	/**
	 * 搜索班级成员
	 */
	public function searchGroupMember() {
	    if(IS_POST) {
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	         
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        $keywords = I('post.keywords', '', 'trim');
	        if(empty($keywords)) {
	            $this->renderFailed('搜索名为空');
	        }
	        
	        $Group = M('member_group');
	        $map['mg.group_id'] = $group_id;
	        $map['mg.status'] = 1;
	        $map['m.nickname'] = array('LIKE', "%$keywords%");
	        
	        $list = $Group->alias('mg')
	        ->page($page, $rows)
	        ->field('mg.uid,m.nickname,m.avatar')
	        ->join('__MEMBER__ m on mg.uid = m.uid', 'left')
	        ->order('mg.id desc')
	        ->where($map)->select();
	        
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	         
	        $Api = new UserApi;
	        $list = $Api->setDefaultAvatar($list);
	         
	        $this->renderSuccess('搜索成员列表', $list);
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
	    $map['w.task_id'] = 0;
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
	    $map['w.task_id'] = 0;
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
	    $map['mg.status'] = 1;
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
	
	        $info = I('info', '', 'trim');
	        if(empty($info)) {
	            $this->renderFailed('缺少信息');
	        }
	        if(ini_get('magic_quotes_gpc')) {
	            $info = stripslashes($info);
	        }
	        
	        $info = str_replace("-", "", $info);
	        
	        $group_id = I('post.group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('班级id为空');
	        }
	        if(!$this->checkGroupidExists($group_id)) {
	            $this->renderFailed('班级不存在');
	        }
            
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
	        $login_uid = is_login();
	        if(!$login_uid) {
	            $this->renderFailed('需要先登录');
	        }
	        
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
	        //不能删除自己
	        if($uid == $login_uid) {
	            $this->renderFailed('不能删除自己');
	        }
	        //不能删除创建者
	        if($this->isGroupOwner($uid, $group_id)){
	            $this->renderFailed('不能删除创建者');
	        }
	        //登录用户不是管理员无权限删除
	        if(!$this->isGroupOwner($login_uid, $group_id)){
	            $this->renderFailed('不是管理员不能删除');
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
	
	//获取班级作品及成员数
	private function groupStat($group_id) {
	    $map['group_id'] = $group_id;
	    $map['status'] = 1;
	    $work_num = M('content')->where($map)->count();
	    $member_num = M('member_group')->where($map)->count();
	    
	    $group_stat['work_num'] = !empty($work_num) ? $work_num : 0;
	    $group_stat['member_num'] = !empty($member_num) ? $member_num : 0;

	    return $group_stat;
	}
	
	//根据班级id获取机构id
	public function getOrgIdByGroupId($group_id) {
	    $map['id'] = $group_id;
	    $map['is_delete'] = 0;
	    $ret = M('group')->field('org_id')->where($map)->find();
	    return $ret['org_id'];
	}
}	
