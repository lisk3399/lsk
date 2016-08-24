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
    
    public function contentList() {
        
    }
    
    public function editContent() {
        
    }
    
    public function deleteContent() {
        
    }
    
    public function addToDrafts() {
        
    }
}
 