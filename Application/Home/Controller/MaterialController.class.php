<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

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
	    $pid = I('pid', '', 'intval');
	    //单次最大读取100
	    if($rows > 100) {
	        $rows = 100;
	    }
	    //读取分类id
	    if(!empty($cateid)) {
	        $list = M('Category')->page($page, $rows)->field('id,name,title')->where(array('id'=>$cateid))->select();
	    } elseif (!empty($pid)) { //读取父分类id
	        $list = M('Category')->page($page, $rows)->field('id,name,title')->where(array('pid'=>$pid))->select();
	    } else { //读取素材父分类
	        $list = M('Category')->page($page, $rows)->field('id,name,title')->where(array('pid'=>1))->select();	        
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
	    
	    if(empty($cateid)) {
	        $this->renderFailed('分类id为空');
	    }
	    
	    $list = M('Document')->alias('d')
	    ->page($page, $rows)
	    ->field('d.id,d.title,d.description,d.cover_id,m.*')
	    ->join('__DOCUMENT_MATERIAL__ m on d.id = m.id', 'left')
	    ->where(array('category_id'=>$cateid))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
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
	        $Material = M('document_material');
	        $res = $Material->where(array('id'=>$material_id))->field('id')->find();
	        if(!$res['id']) {
                $this->renderFailed('该素材不存在');
	        }
	        
	        //是否收藏过
	        $Fav = M('material_fav');
	        $res = $Fav->where(array('mid'=>$material_id))->field('id')->find();
	        if($res['id']) {
	            $this->renderFailed('您已经收藏过了');
	        }
	        
	        //添加收藏
	        $data['uid'] = $uid;
	        $data['mid'] = $material_id;
	        $data['create_time'] = NOW_TIME;
	        if($Fav->add($data)) {
	            $this->renderSuccess('收藏成功');
	        }
	        else {
	            $this->renderSuccess('收藏失败');
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
	    
	    $list = M('Document')->alias('d')
	    ->page($page, $rows)
	    ->field('d.id,d.title,d.description,d.cover_id,m.*')
	    ->join('__DOCUMENT_MATERIAL__ m on d.id = m.id', 'left')
	    ->join('__MATERIAL_FAV__ f on f.mid = m.id', 'left')
	    ->where(array('f.uid'=>$uid))
	    ->select();
	    
	    if(count($list) == 0) {
	        $this->renderFailed('没有更多了');
	    }
	    
	    foreach ($list as &$row) {
	        $cover_img = get_cover($row['cover_id'], 'path');
	        $row['cover_img'] = C('WEBSITE_URL').$cover_img;
	    }
	    
	    $this->renderSuccess('', $list);
	}

	/**
	 * 素材详情
	 */
	public function materialDetail() {
	    
	}
}
