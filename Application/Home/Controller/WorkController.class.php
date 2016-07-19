<?php
/**
 * Class WorkController
 * @name 作品内容管理
 */

namespace Home\Controller;

use User\Api\UserApi;
use Think\Upload\Driver\Qiniu\QiniuStorage;
use Common\Api\WorkApi;
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
	    
	    //发布顺序倒序排列，未删除且设置在首页发现显示的
	    $list = M('Work')->alias('w')
	    ->page($page, $rows)
	    ->field('w.id,w.uid,w.topic_id,w.material_id,w.activity_id,w.cover_url,w.video_url,w.views,w.likes,w.comments,w.type,d.title,d.cover_id,m.avatar,m.nickname,ifnull(t.topic_name, "") as topic_name')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__MEMBER__ m on m.uid =    w.uid', 'left')
	    ->join('__TOPIC__ t on t.id = w.topic_id', 'left')
	    ->where(array('is_delete'=>0,'is_display'=>1))
	    ->order('w.id desc')
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
		//设置默认头像
        $Api = new userapi;
        $list = $Api->setDefaultAvatar($list);
        
        //来源：from=index为从发现请求接口
        $from = I('from', '', 'trim');
        if(!empty($from) && $from == 'index') {
            //设置素材封面图
            foreach ($list as &$row) {
                $row['material_cover_url'] = !empty($row['cover_id'])?C('WEBSITE_URL').get_cover($row['cover_id'], 'path'):'';
                if($row['type'] == 'DUBBING' || $row['type']=="LOCAL") {//配音秀及本地视频
                    $row['cover_url'] = "http://vod.doushow.com/400-400px.png";
                } 
                if(!empty($row['topic_name'])) {
                    $row['title'] = $row['topic_name'];
                }
                if($row['type']=="ORIGINAL" || $row['type']=="LOCAL") {
                    $row['title'] = "原创";
                }
                unset($row['cover_id']);
            }
        }
        else {
            //设置素材封面图
            foreach ($list as &$row) {
                $row['material_cover_url'] = !empty($row['cover_id'])?C('WEBSITE_URL').get_cover($row['cover_id'], 'path'):'';
                if($row['type'] == 'DUBBING') {//配音秀
                    $row['cover_url'] = $row['material_cover_url'];
                }
                if(!empty($row['topic_name'])) {
                    $row['title'] = $row['topic_name'];
                }
                if($row['type']=="ORIGINAL" || $row['type']=="LOCAL") {
                    $row['title'] = "原创";
                }
                unset($row['cover_id']);
            }
            //追加活动信息在列表前
            $activities = $this->getActivities();
            if(!empty($activities) && $page == 1) {
                foreach ($activities as &$row) {
                    $row['cover_url'] = C('WEBSITE_URL').get_cover($row['cover_id'], 'path');
                    $row['act_name'] = '#'.$row['act_name'].'#';
                    array_unshift($list, $row);
                }
            }
        }        
        
	    //是否点赞输出
	    $uid = is_login();
	    if($uid) {
            $list = $Api->getIsLike($list, $uid);
	    } else {
	        foreach ($list as &$row) {
	            $row['is_like'] = 0;
	        }
	    }
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 获取最新活动
	 */
	public function getActivities() {
	    $Activity = M('activity');
	    $list = $Activity->limit(2)
	    ->order('id desc')
	    ->select();
	    
	    return $list;
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
	    ->field('w.id,w.uid,w.topic_id,w.cover_url,d.title,w.views')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->where(array('w.uid'=>$uid,'is_delete'=>0))
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
	    $Api = new UserApi;
	    $uids = $Api->getUserFollow($uid, $page, $rows);
	    if(!empty($uids)) {
	        //批量获取用户作品
	        $list = $this->batchUserWork($uids);
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
	    $Api = new UserApi;
	    $class = $Api->getClassByUid($uid);
	    $class_id = $class['classid'];
	    $uids = $Api->getClassUser($class_id, $page, $rows);
	    if(!empty($uids)) {
	        //批量获取用户作品
	        $list = $this->batchUserWork($uids);
	
	        //管理员发布作品需要放在最前面两个位置
	        //查找管理员作品
	        
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
    	    $title = I('title', '', 'trim');
    	    $description = I('description', '', 'trim');
    	    $type = I('type', '', 'trim');
    	    $topic_id = I('topic_id', '', 'intval');
    	    $activity_id = I('activity_id', '', 'intval');
    	    $group_id = I('group_id', '', 'intval');
    	    
    	    //作品类型：原创/对口型/配音秀/本地上传
    	    $types = array('ORIGINAL', 'LIPSYNC', 'DUBBING','LOCAL');
    	    if(!in_array($type, $types)) {
    	        $this->renderFailed('类型不正确');
    	    }
    	    //非原创素材id不为空
    	    if($type != 'ORIGINAL' && $type!='LOCAL'){
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
    	    if(!empty($title)) {
    	        $data['title'] = $title;
    	    }
    	    if(!empty($description)) {
    	        $data['description'] = $description;
    	    }
    	    $data['type'] = $type;
    	    $data['create_time'] = NOW_TIME;
    	    //发布话题/标签视频
    	    if(!empty($topic_id)) {
    	        $data['topic_id'] = $topic_id;
    	    }
    	    //发布活动视频
    	    if(!empty($activity_id)) {
    	        $data['activity_id'] = $activity_id;
    	    }
    	    //班级视频
    	    if(!empty($group_id)) {
    	        $data['group_id'] = $group_id;
    	    }
    	    
    	    $Work = M('work');
    	    $ret = $Work->add($data);
    	    if($ret) {
    	        //作品数增加：用户作品数和素材作品数(比一比)
    	        WorkApi::setWorksInc($material_id, $uid);
    	        
    	        $data['work_id'] = $ret;
    	        $this->renderSuccess('发布成功', $data);
    	    } else {
    	        $this->renderFailed('发布失败，请重试');
    	    }
	    }
	}
	
	/**
	 * 给作品创建话题
	 */
	public function createWorkTopic() {
	    if(IS_POST) {
	        $uid = is_login();
	        if($uid) {
	            $topic = I('topic', '广场', 'trim');
	            $work_id = I('work_id', '', 'intval');
	            if(empty($work_id)) {
	                $this->renderFailed('没有作品id');
	            }
	            if(!$this->checkWorkExists($work_id)) {
	                $this->renderFailed('作品不存在');
	            }
	            
	            //发布作品话题
	            if(!empty($topic)){
	                $topic_id = $this->isTopicExists($topic);
	                if(!$topic_id){
	                    $topic_id = $this->createTopic($topic, $uid);
	                }
	                $data['topic_id'] = $topic_id;
	                $data['id'] = $work_id;
	                if(M('work')->save($data)) {
	                    $this->renderSuccess('创建成功');
	                }
	            }
	        }
	    }
	}
	
	/**
	 * 作品详情
	 */
	public function workDetail() {
	    $uid = is_login();
	    
	    $work_id = I('id', '', 'intval');
	    if(empty($work_id)) {
	        $this->renderFailed('作品id为空');
	    }
	    if(!$this->checkWorkExists($work_id)) {
	        $this->renderFailed('作品不存在');
	    }
	    
	    $Work = M('work');
	    $detail = $Work->alias('w')
	    ->field('w.id,w.topic_id,m.uid,m.nickname,m.avatar,d.title,d.cover_id,w.material_id,w.type,w.cover_url,w.video_url,w.description,w.create_time,w.likes,w.views,w.comments')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__DOCUMENT_MATERIAL__ dm on dm.id = d.id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.id='.$work_id)
	    ->find();
	    
	    $detail['create_time'] = date('Y-m-d', $detail['create_time']);
        $detail['avatar'] = !empty($detail['avatar'])?$detail['avatar']:C('USER_INFO_DEFAULT.avatar');
	    
        //设置素材封面图
        if(!empty($detail['cover_id'])) {
            $detail['material_cover_url'] = C('WEBSITE_URL').get_cover($detail['cover_id'], 'path');
        }
        if($detail['type'] == 'DUBBING' || $detail['type'] == 'LOCAL') {//配音秀或本地
            $detail['cover_url'] = "http://vod.doushow.com/400-400px.png";
        }
	    //是否点赞输出
	    $Api = new UserApi;
	    $detail['is_like'] = 0;
	    if($uid) {
	        if($Api->isLike($uid, $work_id)) {
	            $detail['is_like'] = 1;
	        }
	    }
	    //标题可能为空
	    if(empty($detail['title'])) {
	        if($detail['type'] == 'ORIGINAL' || $detail['type'] == 'LOCAL') {
	            $detail['title'] = '原创';
	        }
	    }
	    
	    //更新查看数
	    $map['id'] = $work_id;
	    $Work->where($map)->setInc('views');
	    
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
	    ->field('w.id,w.uid,w.cover_url,w.views,d.title')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->where(array('w.uid'=>$uid,'is_delete'=>0))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	     
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 删除我的作品
	 */
	public function deleteMyWork() {
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
	        if(!$this->isMyWork($uid, $work_id)) {
	            $this->renderFailed('只能管理自己的作品');
	        }
	        
	        $Work = M('work');
	        //$map['id'] = array('IN', $ids);//批量
	        $map['id'] = $work_id;
	        $data['is_delete'] = 1;
	        //删除作品进入回收站
	        if($Work->where($map)->save($data)) {
	            $material_id = 0;
	            WorkApi::setWorkDec($material_id, $uid);
	            $this->renderSuccess('删除成功');
	        }
	        $this->renderFailed('删除失败，请稍后再试');
	    }
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
	    	    //更新作品点赞数
	    	    $map['id'] = $work_id;
	    	    M('work')->where($map)->setInc('likes');
	    	    //更新用户点赞数
	    	    $map['uid'] = $uid;
	    	    M('member')->where($map)->setInc('likes');
	    	    $this->renderSuccess('点赞成功');
	    	    //TODO 发送消息
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
	            //$this->renderFailed('没有给该作品点赞');
	        }
	         
	        //喜欢表去掉对应关系
	        $map['uid'] = $uid;
	        $map['work_id'] = $work_id;
	        $Likes = M('likes');
	        if($Likes->where($map)->delete()) {
	            //更新作品点赞数
	            $map['id'] = $work_id;
	            M('work')->where($map)->setDec('likes');
	            //更新用户点赞数
	            $map['uid'] = $uid;
	            M('member')->where($map)->setDec('likes');
	            
	            $this->renderSuccess('取消点赞');
	        }
	        else {
	            $this->renderFailed('取消点赞失败');
	        }
	    }
	}
	
	/**
	 * 批量获取用户作品
	 * @param string $uids 用户id字符串(1,2,3,4)
	 * @param int $rows
	 */
	public function batchUserWork($uids) {
	    $info = array();
	    $Work = M('work');
	    $info = $Work->alias('w')
	    ->field('w.id,w.uid,w.cover_url,w.views,d.title,m.avatar')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.uid in ('.$uids.')')
	    ->where(array('is_delete'=>0))
	    ->order('w.id desc')
	    ->select();
	    
	    //设置默认头像
	    if(is_array($info) && count($info)>0) {
            $Api = new userapi;
            $info = $Api->setDefaultAvatar($info);
	    }
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
	    
	    $Like = M('likes');
	    $work_list = $Like->page($page, $rows)
	    ->field('work_id')
	    ->where(array('uid'=>$uid))
	    ->order('work_id desc')
	    ->select();
	    
	    if(count($work_list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    //数组转换
	    $workArr = array();
	    foreach ($work_list as $key=>$row) {
	        $workArr[$key] = $row['work_id'];
	    }
	     
	    $ids = implode(',', $workArr);
	    $list = $this->getWorkByIds($ids, $rows);
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 根据id批量获取作品
	 */
	private function getWorkByIds($ids, $rows) {
	    $info = array();
	    $Work = M('work');
	    $info = $Work->alias('w')
	    ->field('w.id,w.uid,w.cover_url,w.views,d.title,m.avatar')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.id in ('.$ids.')')
	    ->order('w.id desc')
	    ->limit($rows)
	    ->select();
	    
	    //设置默认头像
	    if(is_array($info) && count($info)>0) {
	        $Api = new userapi;
	        $info = $Api->setDefaultAvatar($info);
	    }
	    
	    return $info;
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
	    
	    $Like = M('likes');
	    $work_list = $Like->page($page, $rows)
	    ->field('work_id')
	    ->where(array('uid'=>$uid))
	    ->order('work_id desc')
	    ->select();
	    
	    if(count($work_list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    //数组转换
	    $workArr = array();
	    foreach ($work_list as $key=>$row) {
	        $workArr[$key] = $row['work_id'];
	    }
	    
	    $ids = implode(',', $workArr);
	    $list = $this->getWorkByIds($ids, $rows);
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 检查作品是否存在
	 * @param int $work_id
	 */
	private function checkWorkExists($work_id) {
	    $Material = M('work');
	    $res = $Material->where(array('id'=>$work_id,'is_delete'=>0))->field('id')->find();
	    if(!$res['id']) {
	        return false;
	    }
	    return true;
	}

	/**
	 * 检查是否是自己的作品
	 * @param int $uid
	 * @param int $work_id
	 */
	private function isMyWork($uid, $work_id) {
	    $Material = M('work');
	    $res = $Material->where(array('id'=>$work_id, 'uid'=>$uid, 'is_delete'=>0))->field('id')->find();
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
	    
	    $Comment = M('comment');
	    $count = $Comment->alias('c')
	    ->field('m.uid')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->where(array('c.work_id'=>$work_id))->count();
	    
	    $list = $Comment->alias('c')
	    ->page($page, $rows)
	    ->field('m.uid,m.nickname,m.avatar,c.to_uid,c.content,c.create_time')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->where(array('c.work_id'=>$work_id))
	    ->order('c.id desc')
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    if($page>1 && count($list)==0) {
	        $this->renderFailed('没有更多了', -1);
	    }
	    //设置默认头像
	    $Api = new UserApi;
	    $list = $Api->setDefaultAvatar($list);
        foreach ($list as &$row) {
            $row['content'] = rawurldecode($row['content']);
        }
	    
        $extra['count'] = $count;
	    $this->renderSuccess('', $list, $extra);
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
	        $data['content'] = rawurlencode($content);
	        $data['create_time'] = NOW_TIME;
	        
	        //TODO:判断重复评论
// 	        if($this->checkComment($uid, $work_id)) {
// 	            
// 	        }
	        $Comment = M('comment');
	        if($Comment->add($data)){
	            //更新评论数
	            $Work = M('work');
	            $map = array('id' => $work_id);
	            $Work->where($map)->setInc('comments');
	            $this->renderSuccess('评论成功');
	        }
	        $this->renderFailed('评论失败');
	    }
	}
	
	/**
	 * 最新作品-某素材下的作品列表
	 */
	public function latestMaterialWork() {
	    if(IS_POST) {
    	    $page = I('page', '1', 'intval');
    	    $rows = I('rows', '20', 'intval');
    	    //限制单次最大读取数量
    	    if($rows > C('API_MAX_ROWS')) {
    	        $rows = C('API_MAX_ROWS');
    	    }
    	    
    		$material_id = I('mid', '', 'intval');
    		$order = I('order', '', 'trim');
    		if($order == 'likes') {
    		    $order = 'w.likes desc';
    		} else {
    		    $order = 'w.id desc';
    		}
    		
    		$Api = new UserApi();
    		//原创视频
    	    if(empty($material_id)) {
    	        $topic_id = I('topic_id', '', 'intval');

    	        $map['is_delete'] = 0;
    	        $map['topic_id'] = $topic_id;
    	        if(!$this->isTopicIDExists($topic_id)) {
    	            $map['topic_id'] = 1;//默认话题分类
    	        }

    	        //发布顺序倒序排列
    	        $Work = M('Work');
    	        $list = $Work->alias('w')
    	        ->page($page, $rows)
    	        ->field('w.id,w.uid,w.topic_id,w.material_id,w.cover_url,w.video_url,w.views,w.likes,w.comments,w.type,d.title,d.cover_id,m.avatar,m.nickname')
    	        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
    	        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
    	        ->join('__TOPIC__ t on w.topic_id = t.id')
    	        ->where($map)
    	        ->order('w.likes desc,w.id desc')
    	        ->select();
    	    }
    	    //非原创视频
    	    else {
    	        //素材是否存在
    	        if(!$Api->checkMaterialExists($material_id)) {
    	            $this->renderFailed('素材不存在');
    	        }
    	        	
    	        $Work = M('work');
    	        $list = $Work->alias('w')
    	        ->page($page, $rows)
    	        ->field('w.id,w.uid,w.cover_url,w.description,w.views,w.likes,w.comments,w.create_time,d.title,m.avatar,m.nickname')
    	        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
    	        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
    	        ->where(array('d.id'=>$material_id,'is_delete'=>0))
    	        ->order($order)
    	        ->select();
    	    }
    	    
    	    if(is_array($list) && count($list)>0) {
    	        $list = $Api->setDefaultAvatar($list);
    	        foreach ($list as &$row){
    	            $row['create_time'] = date('Y-m-d', $row['create_time']);
    	        }
    	    }
    	    
    	    //echo $Work->getLastSql();die;
    	    if(count($list) == 0) {
    	        $this->renderFailed('没有更多了');
    	    }
    	     
    	    $this->renderSuccess('', $list);
	    }
	}
	
	/**
	 * 作品增加浏览量
	 */
	public function addViews() {
	    if(IS_POST) {
	        if(!is_login()) {
	            $this->renderFailed('需要登录');
	        }
    	    $work_id = I('id', '', 'intval');
    	    if(empty($work_id)) {
    	        $this->renderFailed('id为空');
    	    }
    	    $Work = M('work');
    	    $map['id'] = $work_id;
    	    $ret = $Work->where($map)->setInc('views');
    	    
    	    if($ret) {
    	        $this->renderSuccess('success');
    	    }
	    }
	}
	
	/**
	 * 七牛video转gif
	 */
	public function video2gif() {
	    if(IS_POST) {
	        if(!is_login()) {
	            $this->renderFailed('访问拒绝');
	        }
	        //七牛视频key
    	    $key = I('key', '', 'trim');
    	    if(empty($key)) {
    	        $this->renderFailed('key错误');
    	    }
    	    
    	    $config = C('QINIU');
    	    $Qiniu = new QiniuStorage($config);
    	    
    	    $res = $Qiniu->info($key);
    	    if(empty($res['hash'])) {
    	        $this->renderFailed('视频不存在');
    	    }
    	    
    	    $Qiniu->video2gif($key, $config);
	    }
	}
	
	/**
	 * 发布作品创建话题
	 */
	private function createTopic($topic, $uid) {
	    $data['uid'] = $uid;
	    $data['topic_name'] = $topic;
	    $data['create_time'] = NOW_TIME;
	    $Topic = M('topic');
	    $topic_id = $Topic->add($data);
	    if($topic_id){
	        return $topic_id;
	    }
	    return false;
	}
	
	/**
	 * 话题是否存在
	 */
	private function isTopicExists($topic) {
	    $map['topic_name'] = $topic;
	    $res = M('topic')->field('id')->where($map)->find();
	    if($res['id']) {
	        return $res['id'];
	    }
	    return false;
	}
	
	/**
	 * 话题id是否存在
	 */
	private function isTopicIDExists($topic_id) {
	    $map['id'] = $topic_id;
	    $res = M('topic')->field('id')->where($map)->find();
	    if($res['id']) {
	        return true;
	    }
	    return false;
	}
	
	/**
	 * 某话题下作品列表,按照作品点赞数倒序
	 */
	public function topicWork() {
	    if(IS_POST) {
    	    $topic_id = I('topic_id', '', 'intval');
    	    if(empty($topic_id)) {
    	        $this->renderFailed('没有话题id');
    	    }
    	    if(!$this->isTopicIDExists($topic_id)) {
    	        $this->renderFailed('话题不存在');
    	    }
    	    $page = I('page', '1', 'intval');
    	    $rows = I('rows', '20', 'intval');
    	    
    	    //限制单次最大读取数量
    	    if($rows > C('API_MAX_ROWS')) {
    	        $rows = C('API_MAX_ROWS');
    	    }
    	    $Api = new userapi;
    	    $map['is_delete'] = 0;
    	    $map['topic_id'] = $topic_id;
    	    //发布顺序倒序排列
    	    $Work = M('Work');
    	    $list = $Work->alias('w')
    	    ->page($page, $rows)
    	    ->field('w.id,w.uid,w.topic_id,w.material_id,w.cover_url,w.video_url,w.views,w.likes,w.comments,w.type,d.title,d.cover_id,m.avatar,m.nickname')
    	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
    	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
    	    ->join('__TOPIC__ t on w.topic_id = t.id')
    	    ->where($map)
    	    ->order('w.likes desc,w.id desc')
    	    ->select();
    	    
    	    if(count($list) == 0) {
    	        $this->renderFailed('没有更多了');
    	    }
    	    
    	    //设置默认头像
    	    $list = $Api->setDefaultAvatar($list);
    	    
    	    //设置素材封面图
    	    foreach ($list as &$row) {
    	        $row['material_cover_url'] = !empty($row['cover_id'])?C('WEBSITE_URL').get_cover($row['cover_id'], 'path'):'';
    	        unset($row['cover_id']);
    	    }
    	    //是否点赞输出
    	    $uid = is_login();
    	    if($uid) {
    	        $list = $Api->getIsLike($list, $uid);
    	    } else {
    	        foreach ($list as &$row) {
    	            $row['is_like'] = 0;
    	        }
    	    }
    	    
    	    $this->renderSuccess('', $list);
	    }
	}
}
