<?php
/**
 * Class WorkController
 * @name 作品内容管理
 */

namespace Home\Controller;

/**
 * 作品控制器
 */
class WorkController extends HomeController {

	public function index(){
	}
	
	/**
	 * 用户作品列表
	 */
	public function myWork() {
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
	    
	    $list = M('Work')->alias('w')
	    ->page($page, $rows)
	    ->field('w.id,w.cover_url,d.title')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->where(array('w.uid'=>$uid))
	    ->select();
	     
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 作品详情
	 */
	public function workDetail() {
	    $uid = is_login();
	    if(!$uid) {
	        $this->renderFailed('请先登录');
	    }
	    $work_id = I('id', '', 'intval');
	    if(empty($work_id)) {
	        $this->renderFailed('作品id为空');
	    }
	    if(!$this->checkWorkExists($work_id)) {
	        $this->renderFailed('作品不存在');
	    }
	    
	    $detail = M('Work')->alias('w')
	    ->field('m.nickname,m.avatar,dm.outlink,d.title,w.description,w.create_time')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__DOCUMENT_MATERIAL__ dm on dm.id = d.id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid')
	    ->where(array('w.uid'=>$uid))
	    ->find();
	    
	    if(count($detail) == 0) {
	        $this->renderFailed('您还没有作品');
	    }
	    
	    $detail['create_time'] = date('Y-m-d', $detail['create_time']);
        $this->renderSuccess('', $detail);
	}
	
	/**
	 * 作品点赞/喜欢
	 */
	public function like() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	        
	        $work_id = I('id', '', 'intval');
	        if(empty($work_id)) {
	            $this->renderFailed('作品id为空');
	        }
	        if(!$this->checkWorkExists($work_id)) {
	            $this->renderFailed('作品不存在');
	        }
	        //判断是否已经点赞
	        if($this->checkLike($uid, $work_id)) {
	            $this->renderFailed('已经给该作品点赞');
	        }
	        
	        //喜欢表增加对应关系
	        $data['uid'] = $uid;
	        $data['work_id'] = $work_id;
	        $data['create_time'] = NOW_TIME;
	        $Likes = M('likes');
	    	if($Likes->add($data)) {
	    	    //增加作品点赞数
	    	    if($this->updateLike($work_id, 'add')) {
	               $this->renderSuccess('点赞成功');
	    	    } else {
	    	        $this->renderFailed('更新点赞失败', $this->error);
	    	    }
	        }
	        else {
	            $this->renderFailed('点赞失败');
	        }
	    }
	}
	
	/**
	 * 取消点赞/喜欢
	 */
	public function unlike() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	         
	        $work_id = I('id', '', 'intval');
	        if(empty($work_id)) {
	            $this->renderFailed('作品id为空');
	        }
	        if(!$this->checkWorkExists($work_id)) {
	            $this->renderFailed('作品不存在');
	        }
	        //判断是否已经点赞
	        if(!$this->checkLike($uid, $work_id)) {
	            $this->renderFailed('没有给该作品点赞');
	        }
	         
	        //喜欢表去掉对应关系
	        $map['uid'] = $uid;
	        $map['work_id'] = $work_id;
	        $Likes = M('likes');
	        if($Likes->where($map)->delete()) {
	            //减少作品点赞数
	            if($this->updateLike($work_id, 'minus')) {
	                $this->renderSuccess('取消点赞');
	            } else {
	                $this->renderFailed('取消点赞失败', $this->error);
	            }
	        }
	        else {
	            $this->renderFailed('取消点赞失败');
	        }
	    }
	}
	
    /**
     * 检查用户是否给某作品点赞
     * @param int $uid
     * @param int $work_id
     */
	private function checkLike($uid, $work_id) {
	    $Work = M('likes');
	    $map['uid'] = $uid;
	    $map['work_id'] = $work_id;
	    
	    return $Work->field('id')->where($map)->find();
	}
	
	/**
	 * 更新作品喜欢数
	 * @param int $work_id
	 * @param string $type
	 */
	private function updateLike($work_id, $type) {
	    $Work = M('work');
	    //类型检查
	    $types = array('add','minus');
	    if(!in_array($type, $types)) {
	        return false;
	    }
	    
	    $data['id'] = $work_id;
	    if($type === 'add') {
	        $data['likes'] = array('exp', '`likes`+1');
	    } else {
	        $data['likes'] = array('exp', '`likes`-1');
	    }
	    
	    return $Work->save($data);
	}
	/**
	 * 我喜欢的作品
	 */
	public function myLike() {
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
	    
	    $list = M('Work')->alias('w')
	    ->page($page, $rows)
	    ->field('w.id,w.cover_url,d.title')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__LIKES__ l on l.uid = w.uid', 'left')
	    ->where(array('w.uid'=>$uid))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	     
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 检查作品是否存在
	 * @param int $work_id
	 */
	private function checkWorkExists($work_id) {
	    $Material = M('work');
	    $res = $Material->where(array('id'=>$work_id))->field('id')->find();
	    if(!$res['id']) {
	        return false;
	    }
	    return true;
	}
}
