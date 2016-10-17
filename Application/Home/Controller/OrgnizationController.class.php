<?php
/**
 * Class OrgnizationController
 * @name 机构管理
 */

namespace Home\Controller;

use User\Api\UserApi;
class OrgnizationController extends HomeController {
    
    const ADMIN_TYPE_ORG = 'ORG'; //管理员类型为机构
    const ADMIN_TYPE_GROUP = 'GROUP'; //管理员类型为班级
    
    //机构发布的信息列表
    public function orgContent() {
        if(IS_POST) {
            $org_id = I('org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $uid = is_login();
            
            $Content = M('content');
            $map['c.org_id'] = $org_id;
            $map['t.type'] = 'ORG_ADMIN';
            $map['c.status'] = 1;
            
            //指定标签id列表
            $tag_id = I('tag_id', '', 'intval');
            if(!empty($tag_id)) {
                $page = I('page', '1', 'intval');
                $rows = I('rows', '20', 'intval');
                 
                //限制单次最大读取数量
                if($rows > C('API_MAX_ROWS')) {
                    $rows = C('API_MAX_ROWS');
                }
                $map['t.id'] = $tag_id;
                $list = $Content->alias('c')
                ->field('c.id,c.title,c.description,c.comments,c.likes,c.create_time,t.id as tag_id,t.name')
                ->join('__TAGS__ t on t.id = c.tag_id', 'left')
                ->where($map)->order('c.is_top desc,c.id desc')->page($page, $rows)->select();
            } else {
                $subQuery = $Content->alias('c')
                ->field('c.id,c.title,c.description,c.comments,c.likes,c.create_time,t.id as tag_id,t.name')
                ->join('__TAGS__ t on t.id = c.tag_id', 'left')
                ->where($map)->order('t.sort desc,c.is_top desc,c.id desc')->buildSql();
                $sql = 'select * from '.$subQuery.' t group by t.tag_id';
                
                $list = $Content->query($sql);
            }
            
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
            
            $this->renderSuccess('机构发布信息列表', $list);
        }
    }
    
    //机构发布内容
    public function pubOrgContent() {
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
            //发布必须指定机构
            $org_id = I('org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('未指定机构');
            }
            if(!$this->checkOrgIdExists($org_id)) {
                $this->renderFailed('该机构不存在');
            }
            $description = I('description', '', 'trim');
            $content = I('content', '', 'trim');
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
            $data['org_id'] = $org_id;
    
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
    
    //删除机构发布内容
    public function delContent() {
        if(IS_POST) {
            $content_id = I('post.id', '', 'intval');
            if(empty($content_id)) {
                $this->renderFailed('未指定内容');
            }
            
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录', -1);
            }
            if(!$this->isOrgAdmin($uid, $org_id)) {
                $this->renderFailed('不是机构管理员');
            }
            
            $Content = M('content');
            $data['id'] = $content_id;
            $data['status']= -1;
            if($Content->save($data)) {
                $this->renderSuccess('删除成功');
            }
            $this->renderFailed('删除失败');
        }
    }
    
    //设置机构发布内容置顶
    public function setContentTop() {
        if(IS_POST) {
            $content_id = I('post.id', '', 'intval');
            if(empty($content_id)) {
                $this->renderFailed('未指定内容id');
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('未指定机构id');
            }
            $Content = M('content');
            $Content->where(array('org_id'=>$org_id))->save(array('is_top'=>0));
            
            $data['id'] = $content_id;
            $data['is_top']= 1;
            if($Content->save($data)) {
                $this->renderSuccess('设置成功');
            }
            $this->renderFailed('设置失败或已经设置置顶');
        }
    }
    
    //设置机构区块排序
    public function orgBlockSort() {
        if(IS_POST) {
            $block = I('post.block', '', 'trim');
            if(empty($block)) {
                $this->renderFailed('缺少区块信息');
            }
            if(ini_get('magic_quotes_gpc')) {
                $block = stripslashes($block);
            }
            if(!is_valid_json($block)) {
                $this->renderFailed('json格式不对');
            }
            
            $block_arr = json_decode($block, TRUE);
            if(is_array($block_arr) && count($block_arr) > 0) {
                $Tags = M('tags');
                foreach ($block_arr as $row) {
                    $data['id'] = $row['id'];
                    $data['sort']= $row['sort'];
                    $Tags->save($data);
                }
            }
            $this->renderSuccess('设置成功');
        }
    }
    
    //机构区块列表
    public function orgBlock() {
        $Tags = M('tags');
        $map['type'] = 'ORG_ADMIN';
        $map['id'] = array('gt', 1);
        $list = $Tags->field('id,name,sort')->where($map)->order('sort desc')->select();
        
        $this->renderSuccess('机构区块列表', $list);
    }
    
    //某个机构首页信息
    public function orgIndexInfo() {
        $uid = is_login();
        
        $org_id = I('org_id', '', 'intval');
        if(!empty($org_id)) {
            $info = M('orgnization')->where(array('id'=>$org_id))->find();
            if(!$info['id']) {
                $this->renderFailed('暂无信息');
            }
        
            $group_rs = M('group')->field('id')->where(array('org_id'=>$org_id))->select();
            //机构下有班级信息
            $info['content_num'] = '0';
            $group_member_num = 0;
            if(count($group_rs) != 0){
                foreach ($group_rs as $row) {
                    $group_ids[] = $row['id'];
                }
                $info['content_num'] = M('content')
                ->where(array('group_id'=>array('in', $group_ids), 'status'=>1))->count();
                $group_member_num = M('member_group')
                ->where(array('group_id'=>array('in', $group_ids)))->count();
            }
        
            $org_member_num = M('member_org')->where(array('org_id'=>$org_id))->count();
            $info['member_num'] = $org_member_num + $group_member_num;
        
            $back_arr = array(
                '/Public/static/app/group_cover_url1.jpg',
                '/Public/static/app/group_cover_url2.jpg',
                '/Public/static/app/group_cover_url3.jpg'
            );
            
            $i = rand(0,2);
            $info['background_url'] = !empty($info['background_url']) ? $info['background_url'] : C('WEBSITE_URL').$back_arr[$i];
        
            $info['type'] = 'USER';
            if(!empty($uid)) {
                //登录用户角色
                if($this->isOrgOwner($uid, $org_id)) {
                    $info['type'] = 'OWNER';
                } elseif ($this->isOrgAdmin($uid, $org_id)) {
                    $info['type'] = 'ADMIN';
                }
            }
        
            $info['admin_num'] = $this->getAdminNum($org_id);
            $info['star_num'] = $this->getStarMemberNum($org_id);
        
            $this->renderSuccess('机构信息', $info);
        }
        $this->renderFailed('暂无信息');
    }
    
    //获取管理员数
    public function getAdminNum($org_id) {
        $Admin = M('admin');
        $map['type'] = self::ADMIN_TYPE_ORG;
        $map['related_id'] = $org_id;
        return $Admin->where($map)->count();
    }
    
    //获取明星成员数
    public function getStarMemberNum($org_id) {
        $Star = M('org_star');
        return $Star->where(array('org_id'=>$org_id))->count();
    }
    
    //我创建的机构
    public function myOrg() {
        $uid = is_login();
        if(!empty($uid)) {
            $Org = M('orgnization');
            $map['uid'] = $uid;
            $map['is_delete'] = 0;
            $list = $Org->field('id,uid,name,cover_url')
            ->where($map)->order('id desc')->select();
             
            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            $this->renderSuccess('我创建的机构', $list);
        }
        //未登录不显示
        $this->renderFailed('没有更多了');
    }
    
    //我加入的机构列表
    public function orgJoined() {
        $uid = is_login();
        if(!empty($uid)) {
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $member_org = M('member_org');
            $map['uid'] = $uid;
            $mo_list = $member_org->field('org_id')
            ->where($map)->order('id desc')->select();
            
            if(count($mo_list) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            $org_ids = array();
            foreach ($mo_list as $row) {
                $org_ids[] = $row['org_id'];
            }
            
            $map['is_delete'] = 0;
            $map['uid'] = array('NEQ', $uid);
            $map['id'] = array('IN', $org_ids);
            
            $Org = M('orgnization');
            $list = $Org->field('id,uid,name,cover_url')
            ->page($page, $rows)
            ->where($map)
            ->order('id desc')
            ->select();
            
            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            //是否机构管理员
            foreach ($list as &$row) {
                $row['is_admin'] = ($this->isOrgAdmin($uid, $row['id']) === TRUE) ? 1 : 0;
            }
            
            $this->renderSuccess('我关注的机构', $list);
        }
        //未登录不显示
        $this->renderFailed('没有更多了');
    }
    
    //创建机构
    public function createOrg() {
        //只能创建一个机构
        if(IS_POST) {
            $uid = is_login();
            if(empty($uid)) {
                $this->renderFailed('需要登录', -1);
            }
            $name = I('post.name', '', 'trim');
            if(empty($name)) {
                $this->renderFailed('机构名为空');
            }
            $cover_url = I('post.cover_url', '', 'trim');
            if(empty($cover_url)) {
                $this->renderFailed('机构图片为空');
            }
            //创建机构字符限制
            $title_len = mb_strlen($name, 'utf-8');
            if($title_len>30 || $title_len<=2) {
                $this->renderFailed('机构名为2-30个字');
            }
            //群组名是否存在
            if($this->checkOrgExists($name)) {
                $this->renderFailed('已存在该机构');
            }
            //创建群组数量限制
            if($this->checkOrgNum($uid) >= 5) {
                $this->renderFailed('您最多只能创建5个机构');
            }
            $data['uid'] = $uid;
            $data['name'] = $name;
            $data['is_delete'] = 0;
            $data['cover_url'] = $cover_url;
            $data['create_time'] = NOW_TIME;
             
            $Group = M('orgnization');
            $group_id = $Group->add($data);
            
            if($group_id) {
                //创建机构后自己也关注
                $map['uid'] = $uid;
                $map['org_id'] = $group_id;
                $map['create_time'] = NOW_TIME;
                if(M('member_org')->add($map)) {
                    $this->renderSuccess('创建成功');
                }
            }
            $this->renderFailed('创建失败');
        }
    }
    
    //搜索机构
    public function searchOrg() {
        if(IS_POST) {
            $name = I('post.name', '', 'trim');
            if(empty($name)) {
                $this->renderFailed('机构名为空');
            }
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $map['name'] = array('like', "%$name%");
            $Org = M('orgnization');
            $list = $Org->where($map)->page($page, $rows)->order('id desc')->select();
            
            if(count($list) == 0) {
                $this->renderFailed('未找到该机构');
            }
            
            $this->renderSuccess('搜索结果', $list);
        }
    }
    
    //加入/关注机构
    public function joinOrg() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请登录', -1);
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            if(!$this->checkOrgIdExists($org_id)) {
                $this->renderFailed('机构不存在');
            }
            //是否已经加入
            if($this->isJoinOrg($uid, $org_id)) {
                $this->renderFailed('您已关注该机构');
            }
            $map['uid'] = $uid;
            $map['org_id'] = $org_id;
            $map['create_time'] = NOW_TIME;
            if(M('member_org')->add($map)) {
                $this->renderSuccess('加入成功');
            }
            $this->renderFailed('加入失败');
        }
    }
    
    //机构成员
    public function orgMember() {
        if(IS_POST) {
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            //@todo 设置头像问题
            //获取机构下班级id
            $map['org_id'] = $org_id;
            $Group = M('group');
            $group_rs = $Group->field('id')->where($map)->page($page, $rows)->order('id desc')->select();
            if(count($group_rs) == 0) {
                $this->renderFailed('没有更多成员');
            }
            
            foreach ($group_rs as $row) {
                $group_ids[] = $row['id'];
            }
            //获取班级成员id
            $member_list = $this->getMemberByGroupIds($group_ids);
            $api = new UserApi;
            $list = $api->setDefaultAvatar($member_list);
            
            if(count($list) == 0) {
                $this->renderFailed('没有更多成员');
            }
            $this->renderSuccess('机构成员列表', $list);
        }
    }
    
    //某机构下所有班级列表
    public function orgClasses() {
        if(IS_POST) {
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $Group = M('group');
            $list = $Group->field('id,group_name,cover_url,background_url')
            ->where(array('org_id'=>$org_id,'is_delete'=>0))
            ->page($page, $rows)
            ->select();
            
            if(count($list) == 0) {
                $this->renderFailed('没有更多数据');
            }
            $this->renderSuccess('机构班级列表', $list);
        }
    }
    
    //删除机构成员
    public function delOrgMember() {
        if(IS_POST) {
            $login_uid = is_login();
            if(!$login_uid) {
                $this->renderFailed('请先登录', -1);
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            $uid = I('post.uid', '', 'intval');
            if(empty($uid)) {
                $this->renderFailed('用户为空');
            }
            
            if(!$this->isOrgAdmin($login_uid, $org_id)) {
                $this->renderFailed('您不是管理员');
            }
            if($uid == $login_uid) {
                $this->renderFailed('不能删除自己');
            }
            
            $map['uid'] = $uid;
            $map['org_id'] = $org_id;
            
            $mo = M('member_org');
            if($mo->where($map)->delete()) {
                $this->renderSuccess('删除成功');
            }
            $this->renderFailed('删除失败');
        }
    }
    
    //活跃班级
    public function activeGroup() {
        if(IS_POST) {
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            //查找机构下班级id
            $Group = M('group')->alias('g');
            $list = $Group->field('g.id,g.group_name,g.cover_url')
            ->join('__CONTENT__ c on c.group_id = g.id', 'left')
            ->where(array('g.org_id'=>$org_id, 'g.is_delete'=>0))
            ->order('c.id desc')
            ->group('g.id')
            ->limit(4)
            ->select();
            
            //如果机构下班级没内容则默认按时间列出班级数据
            if(count($list) == 0) {
                $list = M('group')->field('id,group_name,cover_url')->where(array('org_id'=>$org_id))->limit(4)->select();
                if(count($list) == 0) {
                    $this->renderFailed('该机构下暂无班级');
                }
            }
            $this->renderSuccess('活跃班级列表', $list);
        }
    }
    
    //机构明星学员列表
    public function starMember() {
        if(IS_POST) {
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $org_star = M('org_star');
            //获取用户id
            $uid_arr = $org_star->field('uid')->where(array('org_id'=>$org_id))->order('id desc')->page($page, $rows)->select();
            if(count($uid_arr) > 0) {
                foreach ($uid_arr as $row) {
                    $uids[] = $row['uid'];
                }
                $uids = implode(',', $uids);
                
                $api = new UserApi;
                $list = $api->batchMemberInfo($uids);
                $this->renderSuccess('机构明星学员列表', $list);
            }
            $this->renderFailed('暂无明星学员');
        }
    }
    
    //添加机构明星学员
    public function addStarMember() {
        if(IS_POST) {
            $login_uid = is_login();
            if(!$login_uid) {
                $this->renderFailed('请先登录', -1);
            }
            
            $uid = I('post.uid', '', 'intval');
            if(empty($uid)) {
                $this->renderFailed('用户为空');
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $data['uid'] = $uid;
            $data['org_id'] = $org_id;
            $data['create_time'] = NOW_TIME;
            
            $org_star = M('org_star');
            $map['uid'] = $uid;
            $map['org_id'] = $org_id;
            if($org_star->where($map)->find()) {
                $this->renderFailed('该学员已经是明星学员');
            }
            //不能添加自己
            if($uid == $login_uid) {
                $this->renderFailed('不能添加自己');
            }
            //登录用户是否为机构管理员
            if(!$this->isOrgAdmin($login_uid, $org_id)) {
                $this->renderFailed('您没有权限添加明星学员');
            }
            
            if($org_star->add($data)) {
                $this->renderSuccess('添加成功');
            }
            $this->renderFailed('添加失败');
        }
    }
    
    //删除机构明星成员
    public function delStarMember() {
        if(IS_POST) {
            $login_uid = is_login();
            if(!$login_uid) {
                $this->renderFailed('请先登录', -1);
            }
        
            $uid = I('post.uid', '', 'intval');
            if(empty($uid)) {
                $this->renderFailed('用户为空');
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            //登录用户是否为机构管理员
            if(!$this->isOrgAdmin($login_uid, $org_id)) {
                $this->renderFailed('您没有权限删除明星学员');
            }
            if($uid == $login_uid) {
                $this->renderFailed('不能删除自己');
            }
            
            $map['uid'] = $uid;
            $map['org_id'] = $org_id;
            
            $org_star = M('org_star');
            if($org_star->where($map)->delete()) {
                $this->renderSuccess('删除成功');
            }
            $this->renderFailed('删除失败');
        }
    }
    
    //添加机构管理员
    public function addOrgAdmin() {
        if(IS_POST) {
            $login_uid = is_login();
            if(!$login_uid) {
                $this->renderFailed('请先登录', -1);
            }
            
            $uid = I('post.uid', '', 'intval');
            if(empty($uid)) {
                $this->renderFailed('用户为空');
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            //机构创建者有权创建管理员
            if(!$this->isOrgOwner($login_uid, $org_id)) {
                $this->renderFailed('机构创建者才有权限添加');
            }
            //不能添加自己
            if($uid == $login_uid) {
                $this->renderFailed('不能添加自己');
            }
            if($this->isOrgAdmin($uid, $org_id)) {
                $this->renderFailed('已经是该机构管理员');
            }
            $Admin = M('admin');
            $data['type'] = self::ADMIN_TYPE_ORG;            
            $data['uid'] = $uid;
            $data['related_id'] = $org_id;
            $data['create_time'] = NOW_TIME;
            
            if($Admin->add($data)) {
                $this->renderSuccess('添加成功');
            }
            $this->renderFailed('添加失败');
        }
    }
    
    //删除机构管理员
    public function delOrgAdmin() {
        if(IS_POST) {
            $login_uid = is_login();
            if(!$login_uid) {
                $this->renderFailed('请先登录', -1);
            }
            
            $uid = I('post.uid', '', 'intval');
            if(empty($uid)) {
                $this->renderFailed('用户为空');
            }
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            //机构创建者有权创建管理员
            if(!$this->isOrgOwner($login_uid, $org_id)) {
                $this->renderFailed('机构创建者才有权限删除');
            }
            if($uid == $login_uid) {
                $this->renderFailed('不能删除自己');
            }
            
            $Admin = M('admin');
            $map['uid'] = $uid;
            $map['related_id'] = $org_id;
            $map['type'] = self::ADMIN_TYPE_ORG;
            
            if($Admin->where($map)->delete()) {
                $this->renderSuccess('删除成功');
            }
            $this->renderFailed('删除失败');
        }
    }
    
    //搜索机构成员
    public function searchOrgMember() {
        $name = I('post.name', '', 'trim');
        if(empty($name)) {
            $this->renderFailed('昵称为空');
        }
        $org_id = I('post.org_id', '', 'intval');
        if(empty($org_id)) {
            $this->renderFailed('机构为空');
        }
        
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
         
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        
        //查找加入机构的用户id
        $Admin = M('member_org');
        $uid_arr = $Admin->field('uid')->where(array('org_id'=>$org_id))->page($page, $rows)->order('id desc')->select();

        //机构下班级id
        $Group = M('group');
        $group_arr = $Group->field('id')->where(array('org_id'=>$org_id))->select();
        if(count($group_arr) > 0) {
            foreach ($group_arr as $row) {
                $group_ids[] = $row['id'];
            }
            $mg = M('member_group');
            $map['group_id'] = array('in', $group_ids);
            $map['status'] = 1;
            $group_uids = $Group
            ->page($page, $rows)
            ->field('uid')
            ->order('id desc')
            ->where($map)->select();
        }
        
        $uid_count = count($uid_arr);
        $group_uid_count = count($group_uids);
        
        if($uid_count == 0 && $group_uid_count == 0) {
            $this->renderFailed('暂无成员信息');
        }
        
        //有机构成员显示机构成员
        if($uid_count > 0) {
            foreach ($uid_arr as $row) {
                $uids[] = $row['uid'];
            }
        }
        
        //有班级成员显示班级成员
        if($group_uid_count > 0) {
            foreach ($group_uids as $row) {
                $uids[] = $row['uid'];
            }
        } 
        $Member = M('member');
        $map['uid'] = array('IN', $uids);
        $map['nickname'] = array('LIKE', "%$name%");
        $list = $Member->field('uid,nickname')->where($map)->select();
        
        if(count($list) == 0) {
            $this->renderFailed('未找到用户');
        }
        $api = new UserApi;
        $list = $api->setDefaultAvatar($list);
        
        $this->renderSuccess('搜索结果', $list);
    }
    
    //机构管理员列表
    public function orgAdminList() {
        if(IS_POST) {
            $org_id = I('post.org_id', '', 'intval');
            if(empty($org_id)) {
                $this->renderFailed('机构为空');
            }
            
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $Admin = M('admin');
            $map['type'] = self::ADMIN_TYPE_ORG;
            $map['related_id'] = $org_id;
            
            $uid_arr = $Admin->field('uid')->where($map)->order('id desc')->page($page, $rows)->select();
            
            if(count($uid_arr) == 0) {
                $this->renderFailed('暂无管理员');
            }
            $uids = array();
            foreach ($uid_arr as $row) {
                $uids[] = $row['uid'];
            }
            $uids = implode(',', $uids);
            $api = new UserApi;
            $list = $api->batchMemberInfo($uids);
            
            $this->renderSuccess('管理员列表', $list);
        }
    }
    
    //是否机构管理员
    public function isOrgAdmin($uid, $org_id) {
        $Admin = M('admin');
        $map['uid'] = $uid;
        $map['related_id'] = $org_id;
        $map['type'] = self::ADMIN_TYPE_ORG;
        $info = $Admin->field('id')->where($map)->find();
        if(!empty($info['id']) || $this->isOrgOwner($uid, $org_id)) {
            return TRUE;
        }
        return FALSE;
    }
    
    //是否机构创建者
    public function isOrgOwner($uid, $org_id) {
        $Org = M('orgnization');
        $map['uid'] = $uid;
        $map['id'] = $org_id;
        $info = $Org->field('id')->where($map)->find();
        if(!empty($info['id'])) {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * 根据班级id获取用户列表
     * @param $group_ids 批量班级id，如:1,2,3,4
     */
    private function getMemberByGroupIds($group_ids, $page, $rows) {
        $Group = M('member_group');
        $map['mg.group_id'] = array("in", $group_ids);
        $map['mg.status'] = 1;
        $list = $Group->alias('mg')
        ->page($page, $rows)
        ->field('mg.uid,m.nickname,m.avatar')
        ->join('__MEMBER__ m on mg.uid = m.uid', 'left')
        ->order('mg.id desc')
        ->where($map)->select();
         
        return $list;
    }
    
    //是否已经加入机构
    public function isJoinOrg($uid, $org_id) {
        $mo = M('member_org');
        $map['uid'] = $uid;
        $map['org_id'] = $org_id;
        $res = $mo->where($map)->find();
        if($res['id']) {
            return TRUE;
        }
        return FALSE;
    }
    
    //检查机构id是否存在
    public function checkOrgIdExists($org_id) {
        $Org = M('orgnization');
        $map['id'] = $org_id;
        return $Org->where($map)->find();
    }
    
    /**
     * 检查机构名是否存在
     */
    private function checkOrgExists($name) {
        $Org = M('orgnization');
        $map['name'] = $name;
        return $Org->where($map)->find();
    }
    
    /**
     * 检查用户创建机构数量
     */
    private function checkOrgNum($uid) {
        $Org = M('orgnization');
        $map['uid'] = $uid;
        $map['is_delete'] = 0;
        return $Org->where($map)->count();
    }
}	
