<?php
/**
 * Class MaterialController
 * @name 素材内容管理
 */

namespace Home\Controller;

use User\Api\UserApi;
use Think\Model;
use Common\Api\ModelApi;

/**
 * 素材控制器
 */
class MaterialController extends HomeController {

	public function index(){
	}
	
    /**
     * 素材分类列表
     */
	public function category() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    $cateid = I('cateid', '', 'intval');
	    $pid = I('pid', '1', 'intval');
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    //读取分类id
	    if(!empty($cateid)) {
	        $list = M('Category')
	        ->page($page, $rows)
	        ->field('id,name,title')
	        ->where(array('id'=>$cateid))
	        ->select();
	    }
	    //读取父分类id下所有分类，默认读取顶级分类
	    else {
	        $list = M('Category')
	        ->page($page, $rows)
	        ->field('id,name,title')
	        ->where(array('pid'=>$pid))
	        ->select();
	    }
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 素材列表
	 */
	public function getMaterial() {
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    $cateid = I('cateid', '', 'intval');
	    $pid = I('pid', '', 'intval');
	    
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $Document = M('Document');
	    $Document = $Document->alias('d')
	    ->page($page, $rows)
	    ->field('d.id,d.title,d.description,d.cover_id,m.*')
	    ->join('__DOCUMENT_MATERIAL__ m on d.id = m.id', 'left')
	    ->join('__CATEGORY__ c on c.id = d.category_id', 'left');
	    
	    //传分类id
	    if(!empty($cateid)) {
	        $map = array('d.category_id'=>$cateid);
	    }
	    //传父类id
	    elseif (!empty($pid)) {
	        $map = array('c.pid'=>$pid);
	        $Document->join('__CATEGORY__ c on c.id = d.category_id', 'left');
	    }
	    else {
	        $map = '1 = 1';
	    }
	    
	    $list = $Document->where($map)
	    ->order('d.id desc')
	    ->select();

	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了', -1);
	    }
	    
	    $uid = is_login();
	    //附件id转附件链接
	    $Api = new UserApi;
	    foreach ($list as &$row) {
	        $row['cover_url'] = !empty($row['cover_id']) ? C('WEBSITE_URL').get_cover($row['cover_id'], 'path') :'';
	        $row['audio'] = !empty($row['audio']) ? $Api->getFileUrl($row['audio']) :'';
	        $row['lyrics'] = !empty($row['lyrics']) ? $Api->getFileUrl($row['lyrics']) :'';
	        $row['video'] = !empty($row['video']) ? $Api->getFileUrl($row['video']) :'';
	        $row['is_fav'] = 0;
	        if($uid) {
	            $row['is_fav'] = $Api->isFav($uid, $row['id']);
	        }
	        if($row['pid'] == 2) {
	            $row['type'] = 'LIPSYNC';//对口型素材
	        } else {
	            $row['type'] = 'DUBBING';//配音秀素材
	        }
	    }
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 收藏素材
	 */
	public function addFavMaterial() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	        
	        $material_id = I('mid', '', 'intval');
	        if(empty($material_id)) {
	            $this->renderFailed('请选择要收藏的对象');
	        }
	        //素材是否存在
	        if(!$this->checkMaterialExists($material_id)) {
	            $this->renderFailed('素材不存在');
	        }
	        
	        //是否收藏过
	        $Fav = M('material_fav');
	        $res = $Fav->where(array('mid'=>$material_id,'uid'=>$uid))->field('id')->find();
	        if($res['id']) {
	            $this->renderFailed('您已经收藏过了');
	        }
	        
	        //添加收藏
	        $data['uid'] = $uid;
	        $data['mid'] = $material_id;
	        $data['create_time'] = NOW_TIME;
	        if($Fav->add($data)) {
	            //更新收藏数
	            $map['id'] = $material_id;
	            M('document')->where($map)->setInc('favourite');
	            $this->renderSuccess('收藏成功');
	        }
	        else {
	            $this->renderSuccess('收藏失败');
	        }
	    }
	}
	
	/**
	 * 取消收藏素材
	 */
	public function removeFavMaterial() {
	    if(IS_POST) {
	        $uid = is_login();
	        if(!$uid) {
	            $this->renderFailed('请先登录');
	        }
	         
	        $material_id = I('mid', '', 'intval');
	        if(empty($material_id)) {
	            $this->renderFailed('请选择要收藏的对象');
	        }
	        //素材是否存在
	        if(!$this->checkMaterialExists($material_id)) {
	            $this->renderFailed('素材不存在');
	        }
	         
	        //是否收藏过
	        $Fav = M('material_fav');
	        $res = $Fav->where(array('mid'=>$material_id,'uid'=>$uid))->field('id')->find();
	        if(!$res['id']) {
	            $this->renderFailed('您没有收藏该素材');
	        }
	         
	        //取消收藏
	        $data['uid'] = $uid;
	        $data['mid'] = $material_id;
	        
	        if($Fav->where($data)->delete()) {
	            //更新收藏数
	            $map['id'] = $material_id;
	            M('document')->where($map)->setDec('favourite');
	            $this->renderSuccess('取消收藏成功');
	        }
	        else {
	            $this->renderSuccess('取消收藏失败');
	        }
	    }
	}
	
	/**
	 * 我的素材收藏列表
	 */
	public function myFavMaterial() {
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
	    
	    $list = M('Document')->alias('d')
	    ->page($page, $rows)
	    ->field('d.id,d.title,d.description,d.cover_id,c.pid,m.*')
	    ->join('__DOCUMENT_MATERIAL__ m on d.id = m.id', 'left')
	    ->join('__MATERIAL_FAV__ f on f.mid = m.id', 'left')
	    ->join('__CATEGORY__ c on c.id = d.category_id', 'left')
	    ->where(array('f.uid'=>$uid))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    //获取封面图片
	    $Api = new UserApi;
	    foreach ($list as &$row) {
	        $cover_img = get_cover($row['cover_id'], 'path');
	        $row['cover_url'] = C('WEBSITE_URL').$cover_img;
	        $row['audio'] = !empty($row['audio']) ? $Api->getFileUrl($row['audio']) :'';
	        $row['lyrics'] = !empty($row['lyrics']) ? $Api->getFileUrl($row['lyrics']) :'';
	        $row['video'] = !empty($row['video']) ? $Api->getFileUrl($row['video']) :'';
	        $row['is_fav'] = 0;
	        if($uid) {
	            $row['is_fav'] = $Api->isFav($uid, $row['id']);
	        }
	        if($row['pid'] == 2) {
	            $row['type'] = 'LIPSYNC';//对口型素材
	        } else {
	            $row['type'] = 'DUBBING';//配音秀素材
	        }
	    }
	    
	    $this->renderSuccess('', $list);
	}

	/**
	 * 素材详情
	 */
	public function materialDetail() {
	    if(IS_POST) {
    	    $material_id = I('mid', '', 'intval');
    	    if(empty($material_id)) {
    	        $this->renderFailed('素材id为空');
    	    }
    	    //素材是否存在
    	    if(!$this->checkMaterialExists($material_id)) {
    	        $this->renderFailed('素材不存在');
    	    }
    	    
    	    //查找素材
    	    $detail = M('Document')->alias('d')
    	    ->field('d.id,d.title,d.description,d.cover_id,m.*')
    	    ->join('__DOCUMENT_MATERIAL__ m on d.id = m.id', 'left')
    	    ->join('__MATERIAL_FAV__ f on f.mid = m.id', 'left')
    	    ->where(array('m.id'=>$material_id))
    	    ->find();
    	    
    	    //获取素材封面
    	    $cover_img = get_cover($detail['cover_id'], 'path');
    	    $detail['cover_url'] = C('WEBSITE_URL').$cover_img;
    	    //获取素材附件
    	    $Api = new UserApi;
    	    $detail['audio'] = !empty($detail['audio']) ? $Api->getFileUrl($detail['audio']) :'';
    	    $detail['lyrics'] = !empty($detail['lyrics']) ? $Api->getFileUrl($detail['lyrics']) :'';
    	    $detail['video'] = !empty($detail['video']) ? $Api->getFileUrl($detail['video']) :'';
    	    $detail['is_fav'] = 0;
    	    $uid = is_login();
    	    if($uid) {
    	       $detail['is_fav'] = $Api->isFav($uid, $material_id);
    	    }
    	    $this->renderSuccess('', $detail);
	    }
	}
	
	/**
	 * 某个素材下的作品
	 */
	public function materialWork() {
	    $material_id = I('mid', '', 'intval');
	    if(empty($material_id)) {
	        $this->renderFailed('素材id为空');
	    }
	    //素材是否存在
	    if(!$this->checkMaterialExists($material_id)) {
	        $this->renderFailed('素材不存在');
	    }
	    
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
	    $list = M('work')
	    ->page($page, $rows)
	    ->field('id,cover_url,likes,create_time')
	    ->where(array('material_id'=>$material_id,'is_delete'=>0))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    $this->renderSuccess('', $list);
	}
	
	/**
	 * 某个素材下的用户列表
	 */
	public function materialUser() {
	    $material_id = I('mid', '', 'intval');
	    if(empty($material_id)) {
	        $this->renderFailed('素材id为空');
	    }
	    //素材是否存在
	    if(!$this->checkMaterialExists($material_id)) {
	        $this->renderFailed('素材不存在');
	    }
	     
	    $page = I('page', '1', 'intval');
	    $rows = I('rows', '20', 'intval');
	    //限制单次最大读取数量
	    if($rows > C('API_MAX_ROWS')) {
	        $rows = C('API_MAX_ROWS');
	    }
	    
        $Api = new UserApi;
	    $uids = $Api->getMaterialUser($material_id, $page, $rows);
	    
	    if(!empty($uids)) {
	        //批量获取用户信息
	        $list = $Api->batchMemberInfo($uids);
	        if(count($list) == 0) {
	            $this->renderFailed('没有更多了');
	        }
	        $this->renderSuccess('', $list);
	    }
	    $this->renderFailed('没有更多了');
	}	

	/**
	 * 检查素材是否存在
	 * @param int $material_id 素材id
	 */
	private function checkMaterialExists($material_id) {
	    $Api = new UserApi();
	    if($Api->checkMaterialExists($material_id)) {
	        return true;
	    }
        return false;
	}
}
