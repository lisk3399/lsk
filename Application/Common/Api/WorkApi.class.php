<?php
namespace Common\Api;
class WorkApi {
    /**
     * 增加作品数
     */
    public static function setWorksInc($material_id, $uid) {
        //增加素材作品数
        if(!empty($material_id)) {
    	    $map['id'] = $material_id;
    	    M('document')->where($map)->setInc('works');
    	}
    	//增加用户作品数
    	$map['uid'] = $uid;
    	M('member')->where($map)->setInc('works');
    }
    
    /**
     * 减少作品数
     */
    public static function setWorkDec($material_id, $uid) {
        //减少素材作品数
        if(!empty($material_id)) {
    	    $map['id'] = $material_id;
    	    M('document')->where($map)->setDec('works');
    	}
    	//减少用户作品数
    	$map['uid'] = $uid;
    	M('member')->where($map)->setDec('works');
    }
}