<?php

namespace Home\Controller;
use User\Api\UserApi;

class ContentController extends HomeController {
    //发现首页滚动切换
    public function topSlider() {
        if(IS_POST) {
            $position = I('post.position', 0, 'intval');
            
            $Slider = M('document')->alias('d');
            $map['d.model_id'] = 8;
            $map['ds.position'] = $position;
            $list = $Slider->join('__DOCUMENT_SLIDER__ ds on d.id = ds.id', 'left')
            ->field('d.title,d.cover_id,ds.position,ds.jump_type,ds.outlink')
            ->where($map)
            ->limit(5)->select();
            
            if(count($list) == 0) {
                $this->renderFailed('暂无内容');
            }
            
            foreach ($list as &$row) {
                $row['cover_url'] = C('WEBSITE_URL').get_cover($row['cover_id'], 'path');
                unset($row['position']);
            }
            
            $this->renderSuccess('首页slider', $list);
        }
    }
    
	/**
	 * 用户发布内容
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
	        $content_json = I('content', '', 'trim');
	        if(empty($description) && empty($content_json)) {
	            $this->renderFailed('请填写描述或内容');
	        }
	        //是否有附件内容
	        $is_hav_content = 0;
	        if(!empty($content_json)) {
	            if(ini_get('magic_quotes_gpc')) {
	                $content_json = stripslashes($content_json);
	            }
	            if(!is_valid_json($content_json)) {
	                $this->renderFailed('json格式不对');
	            }
	            //json数组为空判断
	            $content_arr = json_decode($content_json, TRUE);
	            if(empty($description) && count($content_arr) == 0) {
	                $this->renderFailed('描述和详细内容不能都为空');
	            }
	            $is_hav_content = 1;
	        }
	        
	        //发布必须指定班级
	        $group_id = I('group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('未指定要发到的班级');
	        }
	        if(!$this->isGroupidExists($group_id)) {
	            $this->renderFailed('该班级id不存在');
	        }
	        $Group = new GroupController();
	        if(!$Group->checkJoin($uid, $group_id)) {
	            $this->renderFailed('您未加入该班级');
	        }
	        
	        //创建内容content表插入数据，返回content_id
	        $Content = M("Content");
	        $data['uid'] = $uid;
	        $data['title'] = $title;
	        $data['description'] = $description;
	        $data['create_time'] = NOW_TIME;
	        $data['group_id'] = $group_id;
	        
	        //用户完成作业需要传task_id
	        $task_id = I('task_id', '', 'intval');
	        if(!empty($task_id)) {
	            $data['task_id'] = $task_id;
	            //检查任务是否已经删除
	            $status = $Content->field('status')->where(array('task_id'=>$task_id, 'is_admin'=>1))->find();
	            if((int)$status['status'] <= 0) {
	                $this->renderFailed('抱歉，该任务已被管理员删除');
	            }
	        }
	        
	        $content_id = $Content->data($data)->add();
	        //如果有素材
	        if($content_id && $is_hav_content) {
	            $ContentModel = new \Home\Model\ContentModel();
	            if($ContentModel->addMaterial($content_id, $content_json)) {
	                $this->renderSuccess('添加成功');
	            }
	            $this->renderFailed('素材添加失败');
	        }
	        //只有主题
	        elseif($content_id) {
	            $this->renderSuccess('添加成功');
	        }
	        $this->renderFailed('添加失败');
	    }
	}
	
	//官方全局发布内容
	public function officialPubContent() {
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
	        $content_json = I('content', '', 'trim');
	        if(empty($description) && empty($content_json)) {
	            $this->renderFailed('请填写描述或内容');
	        }
	        //是否有附件内容
	        $is_hav_content = 0;
	        if(!empty($content_json)) {
	            if(ini_get('magic_quotes_gpc')) {
	                $content_json = stripslashes($content_json);
	            }
	            if(!is_valid_json($content_json)) {
	                $this->renderFailed('json格式不对');
	            }
	            //json数组为空判断
	            $content_arr = json_decode($content_json, TRUE);
	            if(empty($description) && count($content_arr) == 0) {
	                $this->renderFailed('描述和详细内容不能都为空');
	            }
	            $is_hav_content = 1;
	        }
	        
	        $tag_id = I('tag_id', '', 'intval');
	        if(empty($tag_id)) {
	            $this->renderFailed('未选择标签');
	        }
	        
	        //创建内容content表插入数据，返回content_id
	        $Content = M("Content");
	        $data = array();
	        $data['uid'] = $uid;
	        $data['title'] = $title;
	        $data['description'] = $description;
	        $data['tag_id'] = $tag_id;
	        $data['create_time'] = NOW_TIME;
	         
	        $content_id = $Content->data($data)->add();
	        //如果有素材
	        if($content_id && $is_hav_content) {
	            $ContentModel = new \Home\Model\ContentModel();
	            if($ContentModel->addMaterial($content_id, $content_json)) {
	                $this->renderSuccess('添加成功');
	            }
	            $this->renderFailed('素材添加失败');
	        }
	        //只有主题
	        elseif($content_id) {
	            $this->renderSuccess('添加成功');
	        }
	        $this->renderFailed('添加失败');
	    }
	}

	/**
	 * 管理员发布作业和任务
	 */
	public function pubTask() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	        $title = I('title', '', 'trim');
	        if(empty($title)) {
	            $this->renderFailed('任务标题不能为空');
	        }
	        $title_len = mb_strlen($title, 'utf-8');
	        if($title_len>30 || $title_len<4) {
	            $this->renderFailed('标题字数在4-30个字');
	        }
	        $description = I('description', '', 'trim');
	        //任务说明不能为空
	        if(empty($description)) {
	            $this->renderFailed('任务说明不能为空');
	        }
	        $content_json = I('content', '', 'trim');
	        //是否有附件内容
	        $is_hav_content = 0;
	        if(!empty($content_json)) {
	            if(ini_get('magic_quotes_gpc')) {
	                $content_json = stripslashes($content_json);
	            }
	            if(!is_valid_json($content_json)) {
	                $this->renderFailed('json格式不对');
	            }
	            //json数组为空判断
	            $content_arr = json_decode($content_json, TRUE);
	            if(count($content_arr) > 0) {
	                $is_hav_content = 1;
	            }
	        }
	        
	        //发布必须指定班级
	        $group_id = I('group_id', '', 'intval');
	        if(empty($group_id)) {
	            $this->renderFailed('未指定要发到的班级');
	        }
	        if(!$this->isGroupidExists($group_id)) {
	            $this->renderFailed('该班级id不存在');
	        }
	        //创建任务
	        $Task = M('task');
	        $deadline = I('deadline', '', 'intval'); 
	        $data['deadline'] = $deadline;
	        $create_time = NOW_TIME;
	        //任务标签
	    	$tag_id = I('tag_id', '', 'intval');
	        if(!empty($tag_id)) {
	            $data['tag_id'] = $tag_id;
	        }
	        if(empty($deadline)) {
	            $data['deadline'] = $create_time + 86400*5; //截至时间，默认5天过期
	        }
	        $data['create_time'] = $create_time;
	        $task_id = $Task->data($data)->add();
	        
	        if(empty($task_id)) {
	            $this->renderFailed('任务添加失败');
	        }
	        
	        //创建内容content表插入数据，返回content_id
	        $Content = M("Content");
	        $data = array();
	        $data['uid'] = $uid;
	        $data['title'] = $title;
	        $data['description'] = $description;
	        $data['group_id'] = $group_id;
	        $data['task_id'] = $task_id;
	        $data['is_admin'] = 1;
	        $data['create_time'] = $create_time;

	        $content_id = $Content->data($data)->add();
	        //如果有素材
	        if($content_id && $is_hav_content) {
	            $ContentModel = new \Home\Model\ContentModel();
	            if($ContentModel->addMaterial($content_id, $content_json)) {
	                $this->renderSuccess('添加成功');
	            }
	            $this->renderFailed('素材添加失败');
	        }
	        //只有主题
	        elseif($content_id) {
	            $this->renderSuccess('添加成功');
	        }
	        $this->renderFailed('添加失败');
	    }
	}

	//获取发布任务标签
	public function getPubTaskTag() {
	    $Tag = M('tags');
	    $list = $Tag->field('id,name,sort')->where(array('type'=>'TASK'))->select();
	     
	    if(count($list) == 0) {
	        $this->renderFailed('没有标签了');
	    }
	     
	    $this->renderSuccess('发布任务标签列表', $list);
	}
	
	//班级任务列表
	public function groupTaskList() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '10', 'intval');
	    
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $group_id = I('group_id', '', 'intval');
	    if(empty($group_id)) {
	        $this->renderFailed('班级为空');
	    }
	    
	    $uid = is_login();
	    //官方用户未加入班级也可以看所有内容
	    $official_uid = M('auth_group_access')->field('uid')->where(array('uid'=>$uid, 'group_id'=>3))->find();
        if(!$official_uid['uid']) {
            $GroupController = new GroupController();
            if(!$GroupController->checkJoin($uid, $group_id)) {
                $this->renderFailed('您未加入该班级，暂时无法看到班级内容');
            }
        }
	    
	    //获取发布任务列表
	    $map['group_id'] = $group_id;
	    $map['is_admin'] = 1;
	    $map['status'] = 1;
	    
	    $Content = M('content')->alias('c');
	    $list = $Content->field('c.id,c.uid,c.title,c.description,t.id as task_id,t.deadline,g.group_name,ifnull(tg.name,"其他") as tag_name')->where($map)
	    ->join('__GROUP__ g on g.id = c.group_id', 'left')
	    ->join('__TASK__ t on t.id = c.task_id', 'left')
	    ->join('__TAGS__ tg on tg.id = t.tag_id', 'left')
	    ->order('t.id desc')
	    ->page($page, $rows)
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $uid = is_login();
	    $cm = M('Content_material');
	    foreach ($list as $key => &$row) {
	        $cm_map['content_id'] = $row['id'];
	        $result = $cm->field('content_json')
	        ->where($cm_map)->find();
	        if(!empty($result['content_json'])) {
	            //todo 需要把content_json中的图片等元素解析出来
	            $row['cover_url'] = $result['cover_url'];
	        }
	        else {
	            $cm_map['type'] = array('in', array('PIC','VIDEO','AUDIO'));
	            $result = $cm->field('cover_url,type')
	            ->where($cm_map) //获取任务封面图
	            ->find();
	            $row['cover_url'] = !empty($result['cover_url']) ? $result['cover_url'] : '';
	            $row['pic_type'] = !empty($result['type']) ? $result['type'] : '';
	        }
	        
	        //截至时间
	        $row['is_end'] = 0;
	        if(strtotime(date("Y-m-d", $row['deadline']))+86400 <= NOW_TIME) {
	            $row['is_end'] = 1;
	        }
	        //是否已经参与
	        $row['is_done_task'] = 0;
	        if($row['is_end'] == 1) {
	            $row['is_done_task'] = 1;
	        }
	        $row['deadline'] = date('Y-m-d', $row['deadline']);
	        
	        //多少人完成作业(仅第一条)
	        if($key == 0) {
    	        $cond['group_id'] = $group_id;
    	        $cond['task_id'] = $row['task_id'];
    	        $cond['is_admin'] = 0;
    	        $cond['status'] = 1;
    	        $row['complete_num'] = (int)M('content')->where($cond)->count();
	        }
	    }

	    $this->renderSuccess('班级任务列表', $list);
	}
	
	//用户完成任务列表：最新/最赞
	public function memberTaskList() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '10', 'intval');
	    
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $group_id = I('group_id', '', 'intval');
	    if(empty($group_id)) {
	        $this->renderFailed('班级为空');
	    }
	    $task_id = I('task_id', '', 'intval');
	    if(empty($task_id)) {
	        $this->renderFailed('任务为空');
	    }
	    
	    $Content = M('task')->alias('t');
	    $map['group_id'] = $group_id;
	    $map['is_admin'] = 0;
	    $map['c.status'] = 1;
	    $map['t.id'] = $task_id;
	    
	    //排序方式
	    $order = (I('order', '', 'trim') == 'likes') ? 'c.likes desc' : 'c.id desc';
	    
	    $list = $Content->field('c.id,c.title,c.create_time,c.likes,c.comments,m.nickname,m.avatar')->where($map)
	    ->join('__CONTENT__ c on t.id = c.task_id', 'left')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->page($page, $rows)
	    ->order($order)
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $uid = is_login();
	    //列表页面获取内容素材
	    $ContentModel = new \Home\Model\ContentModel();
	    $list = $ContentModel->getMaterialList($uid, $list);
	    
	    $this->renderSuccess('用户作业列表', $list);
	}
	
	//任务详情
	public function taskDetail() {
	    $task_id = I('task_id', '', 'intval');
	    if(empty($task_id)) {
	        $this->renderFailed('任务id为空');
	    }
	    
	    $Content = M('task')->alias('t');
	    $map['task_id'] = $task_id;
	    $detail = $Content->field('c.id,c.task_id,c.uid,c.title,c.description,t.deadline,m.nickname,ifnull(tg.name,"其他") as tag_name')
	    ->join('__CONTENT__ c on t.id = c.task_id', 'left')
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    ->join('__TAGS__ tg on tg.id = t.tag_id', 'left')
	    ->where($map)->find();
	    
	    if(count($detail) == 0) {
	        $this->renderFailed('任务不存在');
	    }
	    //列表页面获取内容素材
	    $ContentModel = new \Home\Model\ContentModel();
	    $detail = $ContentModel->getDetailMaterial($detail);
	    
	    $this->renderSuccess('', $detail);
	}
	
	//删除作业
	public function deleteTask() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	        
	        $id = I('id', '', 'intval');
	        if(empty($id)) {
	            $this->renderFailed('作品id为空');
	        }
            
	        $map['id'] = $id;
	        $Content = M('content');
	        $result = $Content->field('uid')->where($map)->find();
	        if(empty($result['uid'])) {
	            $this->renderFailed('任务不存在');
	        }
            if($uid != $result['uid']) {
                $this->renderFailed('您没有权限删除哦');
            }
	        
	        $data['status'] = -1;
	        if($Content->where($map)->save($data)) {
	            $this->renderSuccess('删除成功');
	        }
	        
	        $this->renderFailed('删除失败');
	    }
	}
	
	//批阅作业
	public function readTask() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
                $this->renderFailed('请先登录');
	        }
	        $content_id = I('content_id', '', 'intval');
	        if(empty($content_id)) {
	            $this->renderFailed('作品id为空');
	        }
	        $task_id = I('task_id', '', 'intval');
	        if(empty($task_id)) {
	            $this->renderFailed('任务id为空');
	        }
	        
	        $map['id'] = $content_id;
	        $map['task_id'] = $task_id;
	        $data['is_read'] = 1;
	        if(M('content')->where($map)->save($data)) {
	            $this->renderSuccess('批阅成功');
	        }
	        
	        $this->renderFailed('批阅失败');
	    }
	}
	
	//查看批阅过的任务
	public function viewReadTask() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '10', 'intval');
	    
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $uid = is_login();
	    if(!$uid) {
	        $this->renderFailed('请先登录');
	    }
	    
	    $group_id = I('group_id', '', 'intval');
	    if(empty($group_id)) {
	        $this->renderFailed('班级为空');
	    }
	     
	    $Content = M('content')->alias('c');
	    $map['group_id'] = $group_id;
	    $map['is_admin'] = 0;
	    $map['c.is_read'] = 1; //是否批阅
	    $map['c.status'] = 1;
	    $map['c.uid'] = $uid;
	     
	    $list = $Content->field('c.id,c.task_id,c.title,c.create_time,m.nickname,m.avatar')->where($map)
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    //->join('__TASK__ t on t.id = c.task_id', 'left')
	    ->page($page, $rows)
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
	        
	        if(count($result) > 0) {
    	        foreach ($result as $key=>$content) {
    	            $row['pic'][$key]['cover_url'] = $content['cover_url'];
    	            $row['pic'][$key]['type'] = $content['type'];
    	            $row['pic'][$key]['value'] = $content['value'];
    	        }
	        }
	        
	        $map = array();
	        $map['work_id'] = $row['id'];
	        $map['task_id'] = $row['task_id'];
	        
	        $Comment = M('comment')->alias('c');
	        $comment_list = $Comment->field('c.id,c.content,m.nickname')
	        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	        ->where($map)
	        ->find();
	        
	        if(!empty($comment_list['id'])) {
	            $row['comment'] = rawurldecode($comment_list['content']);
	            $row['comment_username'] = $comment_list['nickname'];
	        }
	        
	        $is_like = $Api->isLike($uid, $row['id']);
	        $row['is_like'] = (!empty($is_like))?1:0;
	        
	    }
	    $list = $Api->setDefaultAvatar($list);
	     
	    $this->renderSuccess('查看批阅过的任务列表', $list);
	}
	
	//批阅作业列表(已批阅/未批阅)
	public function readTaskList() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '10', 'intval');
	     
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $uid = is_login();
	    if(!$uid) {
	        $this->renderFailed('需要登录');
	    }
 	    $group_id = I('group_id', '', 'intval');
	    if(empty($group_id)) {
	        $this->renderFailed('班级为空');
	    }
	    $is_read = I('is_read', 0, 'intval');
	    
	    
	    //如果登录用户不是机构或班级管理员
	    $Org = new OrgnizationController();
	    $Group = new GroupController();
	    $org_id = $Group->getOrgIdByGroupId($group_id);
	    if(!$Org->isOrgAdmin($uid, $org_id) && !$this->isGroupOwner($uid, $group_id)) {
	         $map['c.uid'] = $uid;
	    }
	    
	    $Content = M('content')->alias('c');
	    $map['is_admin'] = 0;
	    $map['group_id'] = $group_id;
	    $map['c.is_read'] = $is_read; //是否批阅
	    $map['c.status'] = 1;
	    $map['c.task_id'] = array("gt", 0);
	    
	    $list = $Content->field('c.id,c.title,c.task_id,c.create_time,m.nickname,m.avatar')
	    ->where($map)
	    ->join('__MEMBER__ m on m.uid = c.uid', 'left')
	    //->join('__TASK__ t on t.id = c.task_id', 'left')
	    ->page($page, $rows)
	    ->order('c.id desc')
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $api = new UserApi();
	    $list = $api->setDefaultAvatar($list);
	    foreach ($list as &$row) {
	        $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
	    }
	    
	    $this->renderSuccess('批阅作业列表', $list);
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
        $rows = I('rows', '20', 'intval');
        
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
            $group_rs = M('member_group')->field('group_id')->where(array('uid'=>$uid, 'status'=>1))->select();
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
        $map['c.org_id']= 0;
        $map['c.task_id'] = 0;
        $m = M('Content');
        $list = $m->alias('c')
        ->page($page, $rows)
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar,ifnull(t.name, "") as tag_name,g.group_name')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->join('__TAGS__ t on t.id = c.tag_id', 'left')
        ->join('__GROUP__ g on g.id = c.group_id', 'left')
        ->where($map)
        ->order('c.is_top desc,c.id desc')
        ->select();
        
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        //列表页面获取内容素材
        $ContentModel = new \Home\Model\ContentModel();
        $list = $ContentModel->getMaterialList($uid, $list);
        
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
        $rows = I('rows', '20', 'intval');
        
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
        
    	$uid = is_login();
	    //官方用户未加入班级也可以看所有内容
	    $official_uid = M('auth_group_access')->field('uid')->where(array('uid'=>$uid, 'group_id'=>3))->find();
        if(!$official_uid['uid']) {
            $GroupController = new GroupController();
            if(!$GroupController->checkJoin($uid, $group_id)) {
                $this->renderFailed('您未加入该班级，暂时无法看到班级内容');
            }
        }
        
        $map['c.status'] = 1;
        $map['c.group_id'] = $group_id;
        $map['c.task_id'] = 0;
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
        //列表页面获取内容素材
        $ContentModel = new \Home\Model\ContentModel();
        $list = $ContentModel->getMaterialList($uid, $list);
        
        $this->renderSuccess('班级动态列表', $list);
    }
    
    /**
     * 机构动态列表
     */
    public function orgContentList() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
    
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        $uid = is_login();

        $org_id = I('org_id', '', 'intval');
        if(empty($org_id)) {
            $this->renderFailed('机构为空');
        }
        
        $map['c.status'] = 1;
        $map['c.task_id'] = 0;
        $map['g.org_id'] = $org_id;
        $Content = M('Content');
        $list = $Content->alias('c')
        ->page($page, $rows)
        ->field('c.id,c.uid,c.title,c.description,c.comments,c.likes,c.create_time,m.nickname,m.avatar,g.group_name')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->join('__GROUP__ g on g.id = c.group_id')
        ->where($map)
        ->order('c.is_top desc,c.id desc')
        ->select();
    
        if(count($list) == 0) {
            $this->renderFailed('没有更多了');
        }
        //列表页面获取内容素材
        $ContentModel = new \Home\Model\ContentModel();
        $list = $ContentModel->getMaterialList($uid, $list);
    
        $this->renderSuccess('机构动态列表', $list);
    }
    
    /**
     * 我的动态列表
     */
    public function myContentList() {
        if(IS_POST) {
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
        
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录', -1);
            }
            $group_id = I('post.group_id', '', 'intval');
            if(empty($group_id)) {
                $this->renderFailed('班级id不存在');
            }
            
            $map['c.status'] = 1;
            $map['c.uid'] = $uid;
            $map['c.group_id'] = $group_id;
            $map['c.task_id'] = 0;
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
            //列表页面获取内容素材
            $ContentModel = new \Home\Model\ContentModel();
            $list = $ContentModel->getMaterialList($uid, $list);
        
            $this->renderSuccess('我的动态列表', $list);
        }
    }
    
    //编辑发布的内容
    public function editContent() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录', -1);
            }
            
            $content_id = I('id', '', 'intval');
            if(empty($content_id)) {
                $this->renderFailed('动态为空');
            }
            if(!$this->checkWorkExists($content_id)) {
                $this->renderFailed('该动态不存在');
            }
            if(!$this->isMyWork($uid, $content_id)) {
                $this->renderFailed('没有权限修改');
            }
            
            $title = I('title', '', 'trim');
            if(!empty($title)) {
                $title_len = mb_strlen($title, 'utf-8');
                if($title_len>30 || $title_len<4) {
                    $this->renderFailed('标题字数在4-30个字');
                }
                $data['title'] = $title;
            }
            $description = I('description', '', 'trim');
            if(!empty($description)) {
                $data['description'] = $description;
            }
            
            $content_json = I('content', '', 'trim');
            //是否有附件内容
            $is_hav_material = 0;
            if(!empty($content_json)) {
                if(ini_get('magic_quotes_gpc')) {
                    $content_json = stripslashes($content_json);
                }
                //json数组为空判断
                $content_arr = json_decode($content_json, TRUE);
                if(!is_array($content_arr)) {
                    $this->renderFailed('json格式不对');
                }
                if(count($content_arr) == 0) {
                    $this->renderFailed('详细内容不能为空');
                }
                $is_hav_material = 1;
            }
            
            //修改内容
            $Content = M("Content");
            $Content->where(array('id'=>$content_id))->save($data);
            
            //更新素材
            if($is_hav_material) {
                $ContentModel = new \Home\Model\ContentModel();
                $list = $ContentModel->updateMaterial($content_id, $content_json);
            }
            $this->renderSuccess('更新成功');
        }
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
    
    //教师批阅列表
    public function readComment() {
        $work_id = I('work_id', '', 'intval');
        if(empty($work_id)) {
            $this->renderFailed('作品id为空');
        }
        if(!$this->checkWorkExists($work_id)) {
            $this->renderFailed('作品不存在');
        }
        $task_id = I('task_id', '', 'intval');
        if(empty($task_id)) {
            $this->renderFailed('任务id为空');
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
        ->order('c.task_id desc,c.id desc')
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
    
            $task_id = I('task_id', '', 'intval');
            if(!empty($task_id)) {
                $data['task_id'] = $task_id;
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
                //不是批阅时，更新评论数
                if(empty($task_id)) {
                    $Content = M('Content');
                    $map = array('id' => $work_id);
                    $Content->where($map)->setInc('comments');
                }
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
    
    //是否已经做了作业
    private function isDoneTask($uid, $task_id) {
        $Content = M('content');
        $map['is_admin'] = 0;
        $map['task_id'] = $task_id;
        $map['uid'] = $uid;
        $map['status'] = 1; 

        return $Content->field('id')->where($map)->find();
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
 