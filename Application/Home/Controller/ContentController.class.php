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
	        $description = I('description', '', 'trim');
	        $content = I('content', '', 'trim');
	        //发布必须指定班级
	        $group_id = I('group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('未指定要发到的班级');
	        }
	        if(!$this->isGroupidExists($group_id)) {
	            $this->renderFailed('该班级id不存在');
	        }
	        
	        //描述和详细内容不能同时为空
	        if(empty($description) && empty($content)) {
	            $this->renderFailed('内容不能为空');
	        }
	        $is_hav_content = 0;
	        if(!empty($content)) {
    	        if(ini_get('magic_quotes_gpc')) {
    	            $content = stripslashes($content);
    	        }
    	        if(!is_valid_json($content)) {
    	            $this->renderFailed('json格式不对');
    	        }
    	        $is_hav_content = 1;
	        }
	        //创建内容content表插入数据，返回content_id
	        $Content = M("Content");
	        $data = array();
	        $data['uid'] = $uid;
	        $data['title'] = $title;
	        $data['description'] = $description;
	        $data['create_time'] = NOW_TIME;
	        $data['group_id'] = $group_id;
	        
	        //发布标签
	        $tag_id = I('tag_id', '', 'intval');
	        if(!empty($tag_id)) {
	            $data['tag_id'] = $tag_id;
	        }
	        
	        $content_id = $Content->data($data)->add();
	        
	        //插入详细内容
	        if($content_id && $is_hav_content) {
	            $ContentMaterial = M("Content_material");
	            $create_time = NOW_TIME;
	            $dataList = array();
	            $content_arr = json_decode($content, TRUE);
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
	        elseif($content_id) {
	            $this->renderSuccess('添加成功');
	        }
	        else {
	            $this->renderFailed('添加失败');
	        }
	    }
	}

	//获取官方发布内容标签
	public function officialTags() {
	    $map['type'] = 'OFFICIAL';
	    $Tags = M('tags');
	    $list = $Tags->field('id,name,sort')->where($map)->select();
	    
	    if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $this->renderSuccess('官方发布标签', $list);
	}
	
	//机构发布内容标签
	public function orgTags() {
	    $map['type'] = 'ORG_ADMIN';
	    $Tags = M('tags');
	    $list = $Tags->field('id,name,sort')->where($map)->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $this->renderSuccess('机构发布内容标签', $list);
	}
    
    //动态详情
    public function viewContent() {
        $uid = is_login();
        
        $work_id = I('id', '', 'intval');
        if(empty($work_id)) {
            $this->renderFailed('作品id为空');
        }
        if(!$this->checkWorkExists($work_id)) {
            $this->renderFailed('作品不存在');
        }
        
        $map['c.status'] = 1;
        $map['c.id'] = $work_id;
        $detail = M('Content')->alias('c')
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->find();
        
        if(count($detail) == 0) {
            $this->renderFailed('内容不存在');
        }
        
        $content_id = $detail['id'];
        $CM = M('Content_material');
        $result = $CM->field('type,value,cover_url')
        ->where(array('content_id'=>$content_id))
        ->select();
        
        $Api = new UserApi;
        $detail['is_like'] = 0;
        $detail['create_time'] = date('Y-m-d H:i', $detail['create_time']);
        if($uid) {
            $is_like = $Api->isLike($uid, $detail['id']);
            $detail['is_like'] = (!empty($is_like))?1:0;
        }
        foreach ($result as $key=>$content) {
            $detail['pic'][$key]['cover_url'] = $content['cover_url'];
            $detail['pic'][$key]['type'] = $content['type'];
            $detail['pic'][$key]['value'] = $content['value'];
        }
        $detail['avatar'] = !empty($detail['avatar'])?$detail['avatar']:C('USER_INFO_DEFAULT.avatar');
        
        $detail['is_mywork'] = 0;
        if($uid == $detail['uid']) {
            $detail['is_mywork'] = 1;
        } 
        
        $this->renderSuccess('详情', $detail);
    }
    
    //动态列表
    public function contentList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '10', 'intval');
        
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        $uid = is_login();
        
        $map = array();
        //官方用户组发的动态展示
        $uid_rs = M('auth_group_access')->field('uid')->where(array('group_id'=>3))->select();
        foreach ($uid_rs as $row) {
            $uid_arr[] = $row['uid'];
        }
        $where['c.uid'] = array('IN', $uid_arr);
        //用户登录后展示用户所有在班级发布的动态
        if($uid) {
            $cond['mg.uid'] = $uid;
            $org_id = I('org_id', '', 'intval');
            $mg = M('member_group');
            //机构班级
            if(!empty($org_id)) {
                $cond['g.org_id'] = $org_id;
                $group_rs = $mg->alias('mg')->field('g.id')
                ->join('__GROUP__ g on g.id = mg.group_id', 'left')
                ->where($cond)->select();
            } else {
                $group_rs = $mg->alias('mg')->field('group_id')->where($cond)->select();
            }
            
            //用户没有加入或创建任何班级不走这块
            if(!empty($group_rs[0]['group_id'])) {
                foreach ($group_rs as $row) {
                    $group_arr[] = $row['group_id'];
                }
                $where['c.group_id'] = array('IN', $group_arr);
                $where['_logic'] = 'or';
            }
        }
        $map['_complex'] = $where;
        $map['c.status'] = 1;
        $m = M('Content');
        $list = $m->alias('c')
        ->page($page, $rows)
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar,g.group_name')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->join('__GROUP__ g on g.id = c.group_id', 'left')
        ->where($map)
        ->order('c.is_top desc,c.id desc')
        ->select();
        
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $Api = new UserApi;
        $Content = M('Content_material');
        foreach ($list as &$row) {
            $row['is_like'] = 0;
            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
            $result = $Content->field('type,value,cover_url')
            ->where(array('content_id'=>$row['id'], 'cover_url'=>array('neq', '')))
            ->limit(3)->select();
            if($uid) {
                $is_like = $Api->isLike($uid, $row['id']);
                $row['is_like'] = (!empty($is_like))?1:0;
            }
            
            foreach ($result as $key=>$content) {
                $row['pic'][$key]['cover_url'] = $content['cover_url'];
                $row['pic'][$key]['type'] = $content['type'];
                $row['pic'][$key]['value'] = $content['value'];
            }
        }
        
        $Api = new UserApi();
        $list =  $Api->setDefaultAvatar($list);
        
        $this->renderSuccess('动态列表', $list);        
    }

    //获取作品ids,如：1,2,3,4,5
    private function getContentIds($list) {
        //获取所有id
        $ids = array();
        foreach ($list as $row) {
            $ids[] = $row['id'];
        }
        return implode(',', $ids);
    }
    
    /**
     * 班级动态列表
     */
    public function classContentList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '10', 'intval');
        
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        $uid = is_login();
        
        $group_id = I('group_id', '', 'intval');
        if(empty($group_id)) {
            $this->renderFailed('班级为空');
        }
        if(!$this->isGroupidExists($group_id)) {
            $this->renderFailed('班级不存在');
        }
        
        $map['c.status'] = 1;
        $map['c.group_id'] = $group_id;
        $list = M('Content')->alias('c')
        ->page($page, $rows)
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.is_top desc,c.id desc')
        ->select();
        
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        
        $Api = new UserApi;
        $Content = M('Content_material');
        foreach ($list as &$row) {
            $row['is_like'] = 0;
            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
            $result = $Content->field('type,value,cover_url')
            ->where(array('content_id'=>$row['id'], 'cover_url'=>array('neq', '')))
            ->limit(3)->select();
            if($uid) {
                $is_like = $Api->isLike($uid, $row['id']);
                $row['is_like'] = (!empty($is_like))?1:0;
            }
        
            foreach ($result as $key=>$content) {
                $row['pic'][$key]['cover_url'] = $content['cover_url'];
                $row['pic'][$key]['type'] = $content['type'];
                $row['pic'][$key]['value'] = $content['value'];
            }
        }
        
        $Api = new UserApi();
        $list =  $Api->setDefaultAvatar($list);
        
        $this->renderSuccess('班级动态列表', $list);
    }
    
    /**
     * 我的动态列表
     */
    public function myContentList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '10', 'intval');
    
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        $uid = is_login();
        if(!$uid) {
            $this->renderFailed('请先登录', -1);
        }
        
        $map['c.status'] = 1;
        $map['c.uid'] = $uid;
        $list = M('Content')->alias('c')
        ->page($page, $rows)
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc')
        ->select();
    
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
    
        $Api = new UserApi;
        $Content = M('Content_material');
        foreach ($list as &$row) {
            $row['is_like'] = 0;
            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
            $result = $Content->field('type,value,cover_url')
            ->where(array('content_id'=>$row['id'], 'cover_url'=>array('neq', '')))
            ->limit(3)->select();
            if($uid) {
                $is_like = $Api->isLike($uid, $row['id']);
                $row['is_like'] = (!empty($is_like))?1:0;
            }
    
            foreach ($result as $key=>$content) {
                $row['pic'][$key]['cover_url'] = $content['cover_url'];
                $row['pic'][$key]['type'] = $content['type'];
                $row['pic'][$key]['value'] = $content['value'];
            }
        }
    
        $Api = new UserApi();
        $list =  $Api->setDefaultAvatar($list);
    
        $this->renderSuccess('我的动态列表', $list);
    }
    
    public function editContent() {
        
    }
    
    //删除作品
    public function deleteContent() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录', -1);
            }
            $work_id = I('id', '', 'intval');
            if(empty($work_id)) {
                $this->renderFailed('作品id为空');
            }
            if(!$this->checkWorkExists($work_id)) {
                $this->renderFailed('作品不存在');
            }
            //判断用户是否管理员，是管理员则不用判断是否我的作品
            $group_id = M('Content')->where(array('id'=>$work_id))->getField('group_id');
            if(!$this->isGroupOwner($uid, $group_id)) {
                if(!$this->isMyWork($uid, $work_id)) {
                    $this->renderFailed('没有权限删除该作品');
                }
            }
            
            $map['id'] = $work_id;
            if(M('Content')->data(array('status'=>'-1'))->where($map)->save()) {
                $this->renderSuccess('删除成功');
            }
            $this->renderFailed('删除失败');
        }
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
    /**
     * 检查群组是否存在
     */
    private function isGroupidExists($group_id) {
        $Group = M('group');
        $map['id'] = $group_id;
        $map['is_delete'] = 0;
        return $Group->where($map)->find();
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
}
 