<?php
namespace Home\Controller;
use Think\Controller;
use User\Api\UserApi;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class MobileController extends Controller {
    public function index() {
    }
    
    //H5分享功能
    public function share() {
        $work_id = I('id', '', 'intval');
        if(empty($work_id)) {
            echo '作品不存在';die;
        }
        
	    $Work = M('work');
	    $detail = $Work->alias('w')
	    ->field('w.id,m.uid,m.nickname,m.avatar,d.title,d.cover_id,w.material_id,w.cover_url,w.video_url,w.description,w.create_time,w.likes,w.views,w.comments')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__DOCUMENT_MATERIAL__ dm on dm.id = d.id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.id='.$work_id)
	    ->find();
	    
	    $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);
        $detail['avatar'] = !empty($detail['avatar'])?$detail['avatar']:C('USER_INFO_DEFAULT.avatar');
        
        $this->assign('info', $detail);
        $this->display();
    }
    
    //H5活动页面
    public function activity() {
        $activity_id = I('id', '', 'intval');
        if(empty($activity_id)) {
            echo '活动不存在';die;
        }
        
        //获取活动信息
        $info = $this->getActivityInfo($activity_id);
        if(!empty($info['id'])) {
            $this->assign('info', $info);
        }
        
        //获取最赞列表
        $list = $this->getActivityWork($activity_id);
        if(!empty($list)) {
            $this->assign('list', $list);
        }
        
        $this->display();
    }
    
    /**
     * 获取单个活动信息
     * @param int $activity_id 活动id
     */
    private function getActivityInfo($activity_id) {
        $Activity = M('activity');
        $map['id'] = $activity_id;
        $data = $Activity->where($map)->find();
        
        if($data['id']) {
            $data['cover_url'] = !empty($data['picture_id'])?C('WEBSITE_URL').get_cover($data['picture_id'], 'path'):'';
            return $data;
        }
        return false;
    }
    
    /**
     * 获取某个活动作品
     * @param int $activity_id
     * @return mixed array|boolean
     */
    private function getActivityWork($activity_id) {
        $map['is_delete'] = 0;
        $map['activity_id'] = $activity_id;
        $Activity = M('activity');
        $list = $Activity->alias('a')->limit(10)
        ->field('w.id,w.uid,w.activity_id,w.material_id,w.cover_url,w.video_url,w.views,w.likes,w.comments,w.type,d.title,d.cover_id,m.avatar,m.nickname')
        ->join('__WORK__ w on w.activity_id = a.id', 'left')
        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
        ->where($map)
        ->order('w.likes desc')
        ->select();
        
        if(!empty($list)) {
            //设置默认头像
            $Api = new UserApi;
            $list = $Api->setDefaultAvatar($list);
            return $list;
        }
        return false;
    }
}
