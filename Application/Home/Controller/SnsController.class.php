<?php
/**
 * Class SnsController
 * @name 用户社交接口
 */

namespace Home\Controller;

use User\Api\UserApi;
/**
 * 作品控制器
 */
class SnsController extends HomeController {

	public function index(){
	}
	
	/**
	 * 我关注的用户列表
	 */
	public function myFollow() {
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
	    //获取关注用户列表
        $User = new UserApi;
        $uids = $User->getUserFollow($uid, $page, $rows);
	    
        if(!empty($uids)) {
            //批量获取用户信息
            $list = $User->batchMemberInfo($uids);
            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
            $this->renderSuccess('', $list);
        }
        $this->renderFailed('没有更多了');
	}
	
	/**
	 * 获取某用户的关注列表
	 */
	public function userFollow() {
	    if(!is_login()) {
	        $this->renderFailed('请先登录');
	    }
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    $uid = I('uid', '', 'intval');
	    if(empty($uid)) {
	        $this->renderFailed('id为空');
	    }
	    if(!$this->checkUidExists($uid)) {
	        $this->renderFailed('用户不存在');
	    }
	    $Follow = M('follow');
	    $member_list = $Follow
	    ->page($page, $rows)
	    ->field('follow_who')
	    ->where(array('who_follow'=>$uid))
	    ->select();
	     
	    //数组转换
	    $memberArr = array();
	    foreach ($member_list as $key=>$row) {
	        $memberArr[$key] = $row['follow_who'];
	    }
	    $uids = implode(',', $memberArr);
	    
	    if(!empty($uids)) {
	        //批量获取用户信息
	        $User = new UserApi;
	        $list = $User->batchMemberInfo($uids);
	        if(count($list) == 0) {
	            
	        }
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}
	
	/**
	 * 获取某用户的粉丝列表
	 */
	public function userFans() {
	    if(!is_login()) {
	        $this->renderFailed('请先登录');
	    }
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $uid = I('uid', '', 'intval');
	    if(empty($uid)) {
	        $this->renderFailed('id为空');
	    }
	    if(!$this->checkUidExists($uid)) {
	        $this->renderFailed('用户不存在');
	    }
	    
	    $Follow = M('follow');
	    $member_list = $Follow
	    ->page($page, $rows)
	    ->field('who_follow')
	    ->where(array('follow_who'=>$uid))
	    ->select();
	     
	    //数组转换
	    $memberArr = array();
	    foreach ($member_list as $key=>$row) {
	        $memberArr[$key] = $row['who_follow'];
	    }
	    $uids = implode(',', $memberArr);
	    
	    if(!empty($uids)) {
	        //批量获取用户信息
	        $User = new UserApi;
	        $list = $User->batchMemberInfo($uids);
	        if(count($list) == 0) {
	             
	        }
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}
	
	/**
	 * 我的粉丝列表
	 */
	public function myFans() {
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
	    $Follow = M('follow');
	    $member_list = $Follow
	    ->page($page, $rows)
        ->field('who_follow')
	    ->where(array('follow_who'=>$uid))
	    ->select();
	    
	    //数组转换
	    $memberArr = array();
	    foreach ($member_list as $key=>$row) {
	        $memberArr[$key] = $row['who_follow'];
	    }
	    $uids = implode(',', $memberArr);
	    if(!empty($uids)) {
	        //批量获取用户信息
	        $User = new UserApi;
	        $list = $User->batchMemberInfo($uids);
	        if(count($list) == 0) {
	            
	        }
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}
	
	/**
	 * 关注某个用户
	 */
	public function follow() {
	    if(IS_POST) {
	        $who_follow = is_login();
	        if(!$who_follow) {
	            $this->renderFailed('请先登录');
	        }
	        if(empty($who_follow)) {
	            $this->renderFailed('用户为空');
	        }
	        $follow_who = I('uid', '', 'intval');
	        if(!$this->checkUidExists($follow_who)) {
	            $this->renderFailed('关注用户不存在');
	        }
	        if($who_follow == $follow_who) {
	            $this->renderFailed('不能关注自己');
	        }
	        //查看是否已经关注
	        if($this->checkFollow($who_follow, $follow_who)) {
	            $this->renderFailed('已经关注过该用户');
	        }
	        
	        //关注
	        if($this->followOne($who_follow, $follow_who)) {
	            $this->renderSuccess('关注成功');
	        } else {
	            $this->renderFailed('关注失败');
	        }
	    }
	}
	
	/**
	 * 取消关注
	 */
	public function unFollow() {
	    if(IS_POST) {
	        $who_follow = is_login();
	        if(!$who_follow) {
	            $this->renderFailed('请先登录');
	        }
	        if(empty($who_follow)) {
	            $this->renderFailed('用户为空');
	        }
	        $follow_who = I('uid', '', 'intval');
	        if(!$this->checkUidExists($follow_who)) {
	            $this->renderFailed('取消关注用户不存在');
	        }
	        //查看是否已经关注
	        if(!$this->checkFollow($who_follow, $follow_who)) {
	            $this->renderFailed('还未关注该用户');
	        }
	         
	        $Follow = M('follow');
	        $map['who_follow'] = $who_follow;
	        $map['follow_who'] = $follow_who;
	    
	        //取消关注
	        if($Follow->where($map)->delete()) {
	            $this->renderSuccess('取消关注成功');
	        } else {
	            $this->renderFailed('取消关注失败');
	        }
	    }
	}
	
	/**
	 * 批量关注用户
	 */
	public function batchFollow() {
	    if(IS_POST) {
	        $who_follow = is_login();
	        if(!$who_follow) {
	            $this->renderFailed('请先登录');
	        }
	        $follow_list = I('uids', '', 'trim');
	        if(empty($follow_list)) {
	            $this->renderFailed('批量用户为空');
	        }
            if(!$this->check_ids($follow_list)) {
                $this->renderFailed('用户列表格式错误');
            }
            //批量关注
            $followArr = explode(',', $follow_list);
            foreach ($followArr as $follow_who) {
                //不能关注自己
                if($who_follow == $follow_who) {
                    continue;
                }
                //如果已经关注直接跳过
                if($this->checkFollow($who_follow, $follow_who)) {
                    continue;
                }
                //关注用户不存在
                if(!$this->checkUidExists($follow_who)) {
                    continue;
                }
                //关注
                $this->followOne($who_follow, $follow_who);
            }
            
            $this->renderSuccess('关注成功');
	    }
	}
	
	/**
	 * 判断当前用户是否关注了某用户 
	 */
	public function isFollow() {
	    if(IS_POST) {
	        $who_follow = is_login();
	        if(!$who_follow) {
	            $this->renderFailed('请先登录');
	        }
	        
	        $follow_who = I('uid', '', 'intval');
	        if(empty($follow_who)) {
	            $this->renderFailed('用户为空');
	        }
	        if(!$this->checkUidExists($follow_who)) {
	            $this->renderFailed('用户不存在');
	        }
	        //关注判断
	        if($this->checkFollow($who_follow, $follow_who)) {
	            $this->renderSuccess('已经关注了该用户');
	        } else {
	            $this->renderFailed('未关注该用户');
	        }
	    }
	}
	
	/**
	 * 搜索用户
	 */
	public function searchUser() {
	    if(IS_POST) {
	        $keywords = I('keywords', '', 'trim');
	        if(empty($keywords)) {
	            $this->renderFailed('请输入搜索关键词');
	        }
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	    
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	         
	        $result = M('member')->page($page, $rows)->field('uid,nickname,avatar')->where('nickname like "%'.$keywords.'%"')->select();
	        if(is_array($result) && count($result) > 0) {
	            $this->renderSuccess('查询结果', $result);
	        }
	        $this->renderFailed('暂无结果');
	    }
	}
	
	/**
	 * 推荐关注用户列表
	 */
	public function recommendUser() {
	    if(IS_POST) {
	        $page = I('page', '1', 'intval');
	        $rows = I('rows', '20', 'intval');
	         
	        //限制单次最大读取数量
	        if($rows > C('API_MAX_ROWS')) {
	            $rows = C('API_MAX_ROWS');
	        }
	        
	        $result = M('member')->page($page, $rows)->field('uid,nickname,avatar,sex')->order('uid desc')->select();
	        if(is_array($result) && count($result) > 0) {
	            //默认头像处理
	            foreach ($result as &$row) {
	                $row['avatar'] = !empty($row['avatar'])?$row['avatar']:C('USER_INFO_DEFAULT.avatar');
	            }
	            $this->renderSuccess('查询结果', $result);
	        }
	        $this->renderFailed('暂无结果');
	    }
	}
	
	/**
	 * 检查用户uid是否存在
	 * @param int $uid
	 */
	private function checkUidExists($uid) {
	    $User = new UserApi;
	    if(!$User->checkUidExists($uid)) {
	        return false;
	    }
	    return true;
	}
	
    /**
     * 检查是否关注过某用户
     * @param int $who_follow 谁关注
     * @param int $follow_who 关注谁
     */
	private function checkFollow($who_follow, $follow_who) {
	    $Follow = M('follow');
	    $map['who_follow'] = $who_follow;
	    $map['follow_who'] = $follow_who;
	    
	    $ret = $Follow->where($map)->field('id')->find();
	    if(!$ret['id']) {
	        return false;
	    }
        return true;
	}
	
    /**
     * 单个关注操作
     * @param int $who_follow
     * @param int $follow_who
     */
	private function followOne($who_follow, $follow_who) {
	    $Follow = M('follow');
	    $data['who_follow'] = $who_follow;
	    $data['follow_who'] = $follow_who;
	    $data['create_time'] = NOW_TIME;
	    
	    if($Follow->add($data)) {
	        return true;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * 检查批量id格式是否正确(1,2,3,4)
	 * @param string $str
	 */
	private function check_ids($str) {
	    if (preg_match("/^\d+(,\d+)*$/", $str)) {
	        return true;
	    }
	    return false;
	}
	
}
