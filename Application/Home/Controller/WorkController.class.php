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
	 * 作品点赞
	 */
	public function like() {
	    
	}
	
	/**
	 * 我喜欢的作品
	 */
	public function myLike() {
	    
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
