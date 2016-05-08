<?php
/**
 * Class SnsController
 * @name 用户社交接口
 */

namespace Home\Controller;

/**
 * 作品控制器
 */
class SnsController extends HomeController {

	public function index(){
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
	 * 检查用户uid是否存在
	 * @param int $uid
	 */
	private function checkUidExists($uid) {
	    $Member = M('member');
	    $map['uid'] = $uid;
	    $ret = $Member->field('uid')->where($map)->find();
	    if(!$ret['uid']) {
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
