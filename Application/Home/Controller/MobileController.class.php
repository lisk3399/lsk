<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class MobileController extends Controller {
    public function index() {
    }
    
    public function share() {
        $work_id = I('id', '', 'intval');
        if(empty($work_id)) {
            echo 'sorry,该作品不存在';die;
        }
        
	    $Work = M('work');
	    $detail = $Work->alias('w')
	    ->field('w.id,m.uid,m.nickname,m.avatar,d.title,d.cover_id,w.material_id,w.cover_url,w.video_url,w.description,w.create_time,w.likes,w.views,w.comments')
	    ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
	    ->join('__DOCUMENT_MATERIAL__ dm on dm.id = d.id', 'left')
	    ->join('__MEMBER__ m on m.uid = w.uid', 'left')
	    ->where('w.id='.$work_id)
	    ->find();
	    
	    $detail['create_time'] = date('Y-m-d', $detail['create_time']);
        $detail['avatar'] = !empty($detail['avatar'])?$detail['avatar']:C('USER_INFO_DEFAULT.avatar');
        
        $this->assign('info', $detail);
        $this->display();
    }
}
