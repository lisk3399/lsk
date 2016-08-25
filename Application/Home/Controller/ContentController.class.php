<?php

namespace Home\Controller;
use User\Api\UserApi;

class ContentController extends HomeController {
	/**
	 * 发布内容
	 */
	public function pubContent() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	        $title = I('title', '', 'trim');
	        if(empty($title)) {
	            $this->renderFailed('标题不能为空');
	        }
	        $title_len = mb_strlen($title, 'utf-8');
	        if($title_len>30 || $title_len<4) {
	            $this->renderFailed('标题字数在4-30个字');
	        }
	        $content = I('content', '', 'trim');
	        if(empty($content)) {
	            $this->renderFailed('发布内容不能为空');
	        }
	        if(!$this->is_valid_json($content)) {
	            $this->renderFailed('json格式不对');
	        }

	        $content_arr = json_decode($content, TRUE);
	        //创建内容content表插入数据，返回content_id
	        $Content = M("Content");
	        $data = array();
	        $data['uid'] = $uid;
	        $data['title'] = $title;
	        $data['create_time'] = NOW_TIME;
	        $content_id = $Content->data($data)->add();
	        //内容素材表插入数据
	        if($content_id) {
	            $ContentMaterial = M("Content_material");
	            $create_time = NOW_TIME;
	            foreach ($content_arr as $row) {
	                $dataList[] = array(
	                    'content_id'=>$content_id,
	                    'type'=>$row['type'],
	                    'value'=>$row['value'],
	                    'cover_url'=>(!empty($row['cover_url'])?$row['cover_url']:''),
	                    'create_time'=>$create_time
	                );
	            }
	            if(!$ContentMaterial->addAll($dataList)) {
	                $this->renderFailed('添加失败，请稍后再试');
	            }
	            $this->renderSuccess('添加成功');
	        }
	    }
	}

    private function is_valid_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    public function viewContent() {
        
    }    
    
    public function contentList() {
        
    }
    
    public function editContent() {
        
    }
    
    public function deleteContent() {
        
    }
    
    public function addToDrafts() {
        
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
                $Content = M('Content');
                $map = array('id' => $work_id);
                $Content->where($map)->setInc('comments');
                $this->renderSuccess('评论成功');
            }
            $this->renderFailed('评论失败');
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
                $this->renderFailed('已经点过赞');
            }
             
            //喜欢表增加对应关系
            $data['uid'] = $uid;
            $data['work_id'] = $work_id;
            $data['create_time'] = NOW_TIME;
            $Likes = M('likes');
            if($Likes->data($data)->add()) {
                //更新作品点赞数
                $map['id'] = $work_id;
                M('Content')->where($map)->setInc('likes');
                //更新用户点赞数
                $map['uid'] = $uid;
                M('Member')->where($map)->setInc('likes');
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
    
            //喜欢表去掉对应关系
            $map['uid'] = $uid;
            $map['work_id'] = $work_id;
            $Likes = M('likes');
            if($Likes->where($map)->delete()) {
                //更新作品点赞数
                $map['id'] = $work_id;
                M('Content')->where($map)->setDec('likes');
                //更新用户点赞数
                $map['uid'] = $uid;
                M('Member')->where($map)->setDec('likes');
                
                $this->renderSuccess('取消点赞');
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
        $Likes = M('likes');
        $map['uid'] = $uid;
        $map['work_id'] = $work_id;
         
        return $Likes->field('id')->where($map)->find();
    }
    
    /**
     * 更新作品喜欢数
     * @param int $work_id
     * @param string $type
     */
    private function updateLike($work_id, $type) {
        $Content = M('Content');
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
         
        return $Content->save($data);
    }
    
    /**
     * 检查作品是否存在
     * @param int $work_id
     */
    private function checkWorkExists($work_id) {
        $Content = M('Content');
        $res = $Content->where(array('id'=>$work_id,'status'=>1))->field('id')->find();
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
        $Content = M('Content');
        $res = $Content->where(array('id'=>$work_id, 'uid'=>$uid, 'status'=>1))->field('id')->find();
        if(!$res['id']) {
            return false;
        }
        return true;
    }
    
}
 