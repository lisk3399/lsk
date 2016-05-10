<?php
/**
 * Class WorkController
 * @name 作品内容管理
 */

namespace Home\Controller;

use User\Api\UserApi;
/**
 * 作品控制器
 */
class WorkController extends HomeController {

	public function index(){
	}
	
	/**
	 * 最新作品列表
	 */
	public function latestWork() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	     
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    //发布顺序倒序排列
	    $list = M('Work')->alias('w')
	    ->page($page, $rows)
	    ->field('w.id,w.cover_url,d.title')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->order('w.id desc')
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	     
	    $this->renderSuccess('', $list);
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
	 * 我关注的用户作品
	 */
	public function followUserWork() {
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
	        //批量获取用户作品
	        $list = $this->batchUserWork($uids, $page, $rows);
	         
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	         
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}
	
	/**
	 * 班级下所有用户作品
	 */
	public function classUserWork() {
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
	    
	    //获取班级用户列表
	    $User = new UserApi;
	    $class = $User->getClassByUid($uid);
	    $class_id = $class['classid'];
	    
	    $uids = $User->getClassUser($class_id, $page, $rows);
	    
	    if(!empty($uids)) {
	        //批量获取用户作品
	        $list = $this->batchUserWork($uids, $page, $rows);
	
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}
	
	/**
	 * 发布作品 
	 */
	public function pubWork() {
	    if(IS_POST) {
    	    $uid = is_login();
    	    if(!$uid) {
    	        $this->renderFailed('请先登录');
    	    }
    	    
    	    $material_id = I('mid', '', 'intval');
    	    $video_url = I('video_url', '', 'trim');
    	    $cover_url = I('cover_url', '', 'trim');
    	    $description = I('description', '', 'trim');
    	    $is_original = I('is_original', 0, 'intval');
    	    
    	    //非原创素材id不为空
    	    if($is_original == 0){
    	        if(empty($material_id)) {
    	            $this->renderFailed('素材为空');
    	        }
    	    }
    	    if(empty($video_url)) {
    	        $this->renderFailed('视频为空');
    	    }
    	    if(empty($cover_url)) {
    	        $this->renderFailed('首图为空');
    	    }
    	    
    	    $data['uid'] = $uid;
    	    $data['material_id'] = $material_id;
    	    $data['video_url'] = $video_url;
    	    $data['cover_url'] = $cover_url;
    	    $data['description'] = $description;
    	    $data['is_original'] = $is_original;
    	    $data['create_time'] = NOW_TIME;
    	    
    	    $Work = M('work');
    	    if($Work->add($data)) {
    	        $this->renderSuccess('发布成功');
    	    } else {
    	        $this->renderFailed('发布失败，请重试');
    	    }
	    }
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
	 * 获取某用户作品列表
	 */
	public function userWork() {
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
	    $User = new UserApi;
	    if(!$User->checkUidExists($uid)) {
	        $this->renderFailed('用户不存在');
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
// 	    	        $User = new UserApi();
// 	    	        $type = C('MESSAGE_TYPE.LIKE');
// 	    	        $User->sendMessage($uid, $type);
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
	 * 批量获取用户作品
	 * @param string $uids 用户id字符串(1,2,3,4)
	 * @param int $page
	 * @param int $rows
	 */
	public function batchUserWork($uids, $page, $rows) {
	    $info = array();
	    
	    $info = M('work')->alias('w')
	    ->page($page, $rows)
	    ->field('w.id,w.cover_url,d.title,m.avatar')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.uid in ('.$uids.')')
	    ->select();
	    
	    return $info;
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
	 * 用户喜欢的作品列表
	 */
	public function userLike() {
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
	    $User = new UserApi;
	    if(!$User->checkUidExists($uid)) {
	        $this->renderFailed('用户不存在');
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
	
	/**
	 * 某个作品评论列表
	 */
	public function commentList() {
	    $work_id = I('id', '', 'intval');
	    if(empty($work_id)) {
	        $this->renderFailed('作品id为空');
	    }
	    if(!$this->checkWorkExists($work_id)) {
	        $this->renderFailed('作品不存在');
	    }
	
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	     
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $count = M('comment')->alias('c')
	    ->field('m.uid,m.nickname,m.avatar,c.content,c.create_time')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->where(array('c.work_id'=>$work_id))->count();
	    
	    $list = M('comment')->alias('c')
	    ->field('m.uid,m.nickname,m.avatar,c.content,c.create_time')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->where(array('c.work_id'=>$work_id))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 发布评论
	 */
	public function pubComment() {
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
	
	        $content = I('content', '', 'trim');
	        if(empty($content)) {
	            $this->renderFailed('请输入内容');
	        }
	
	        $data['uid'] = $uid;
	        $data['work_id'] = $work_id;
	        $data['content'] = $content;
	        $data['create_time'] = NOW_TIME;
	        
	        //TODO:判断重复评论
// 	        if($this->checkComment($uid, $work_id)) {
// 	            
// 	        }
	        
	        if(M('comment')->add($data)){
	            $this->renderSuccess('评论成功');
	        }
	        $this->renderFailed('评论失败');
	    }
	}
}
