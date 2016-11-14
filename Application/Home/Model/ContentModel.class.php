<?php

namespace Home\Model;
use Think\Model;
use User\Api\UserApi;

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
    
    /**
     * 获取内容素材列表
     * @param int $uid 登录用户id
     * @param array $list 内容数组
     */
    public function getMaterialList($uid, $list) {
        $Api = new UserApi;
        $Content = M('Content_material');
        foreach ($list as $key=>&$row) {
            $row['is_like'] = 0;
            $row['create_time'] = date('Y-m-d H:i', $row['create_time']);
            
            $result = $Content->field('content_json')
            ->where(array('content_id'=>$row['id']))->find();
            
            if(!empty($result['content_json'])) {
                $arr = json_decode($result['content_json'], TRUE);
                $counter = 0;
                foreach ($arr as $json_key=>$json_row) {
                    if(empty($json_row['cover_url'])) {
                        unset($json_row);
                        continue;
                    }
                    $row['pic'][$counter]['cover_url'] = $json_row['cover_url'];
                    $row['pic'][$counter]['type'] = $json_row['type'];
                    $row['pic'][$counter]['value'] = $json_row['value'];
                    $counter++;
                }
            }
            else {
                $result = $Content->field('type,value,cover_url')
                ->where(array('content_id'=>$row['id'], 'cover_url'=>array('neq', '')))
                ->limit(3)->select();
                foreach ($result as $key=>$content) {
                    $row['pic'][$key]['cover_url'] = $content['cover_url'];
                    $row['pic'][$key]['type'] = $content['type'];
                    $row['pic'][$key]['value'] = $content['value'];
                }
            }

            if($uid) {
                $is_like = $Api->isLike($uid, $row['id']);
                $row['is_like'] = (!empty($is_like))?1:0;
            }
        }
        $list =  $Api->setDefaultAvatar($list);
        
        return $list;
    }
    
    //更新素材
    public function updateMaterial($content_id, $content_json) {
        $Content = M('Content_material');
        $data['content_json'] = $content_json;
        $ret = $Content->where(array('content_id'=>$content_id))->save($data);
        if(!$ret) {
            return FALSE;
        }
        return TRUE;
    }
}
