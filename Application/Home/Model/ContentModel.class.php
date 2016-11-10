<?php

namespace Home\Model;
use Think\Model;

/**
 * 内容模型
 */
class ContentModel extends Model{
    
    /**
     * 添加内容素材
     * @param int $content_id
     * @param string $content_json
     */
    public function addMaterial($content_id, $content_json) {
        $ContentMaterial = M("Content_material");
        $data['content_id'] = $content_id;
        $data['create_time'] = NOW_TIME;
        $data['content_json'] = $content_json;

        return $ContentMaterial->add($data);
    }
    
    //获取素材列表
    public function getMaterial() {
        
    }
}
