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
            $row['create_timestamp'] = $row['create_time'];
            $row['create_time'] = get_short_time($row['create_time']);
            //$row['create_time'] = date('Y-m-d H:i', $row['create_time']);
            
            $result = $Content->field('content_json,update_time')
            ->where(array('content_id'=>$row['id']))->find();
            
            if(!empty($result['content_json'])) {
                $row['update_timestamp'] = $result['update_time'];
                $arr = json_decode($result['content_json'], TRUE);
                $counter = 0;
                if(is_array($arr)) {
                    foreach ($arr as $json_key=>$json_row) {
                        if(empty($json_row['cover_url'])) {
                            unset($json_row);
                            continue;
                        }
                        $row['pic'][$counter]['cover_url'] = $json_row['cover_url'];
                        $row['pic'][$counter]['type'] = strtoupper($json_row['type']);
                        if($row['pic'][$counter]['type'] == 'LIVE') {
                            $row['pic'][$counter]['status'] = $json_row['status'];
                        }
                        if(!empty($json_row['room_id'])) {
                            $row['pic'][$counter]['room_id'] = $json_row['room_id'];
                        }
                        $row['pic'][$counter]['value'] = $json_row['value'];
                        $counter++;
                    }
                }
            }//历史数据展示
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
    
    //获取详情页素材
    public function getDetailMaterial($detail) {
        $map['content_id'] = $detail['id'];
        $cm = M('Content_material');
        $result = $cm->field('content_json')
        ->where($map)->find();
        
        if(!empty($result['content_json'])) {
            $material_arr = json_decode($result['content_json'], true);
            foreach ($material_arr as $key=>$row) {
                $detail['pic'][$key]['cover_url'] = $row['cover_url'];
                $detail['pic'][$key]['type'] = strtoupper($row['type']);
                if($detail['pic'][$key]['type'] == 'LIVE') {
                    $detail['pic'][$key]['status'] = $row['status'];
                }
                $detail['pic'][$key]['value'] = $row['value'];
            }
        }
        else {
            $result = $cm->field('type,value,cover_url')
            ->where($map)
            ->select();
            
            foreach ($result as $key=>$content) {
                $detail['pic'][$key]['cover_url'] = $content['cover_url'];
                $detail['pic'][$key]['type'] = $content['type'];
                $detail['pic'][$key]['value'] = $content['value'];
            }
        }
        //是否已经参与
        $detail['is_done_task'] = 0;
        if(!empty($detail['deadline'])) {
            if(strtotime(date("Y-m-d", $detail['deadline']))+86400 <= NOW_TIME) {
                $detail['is_done_task'] = 1;
            }
            $detail['deadline'] = date('Y-m-d', $detail['deadline']);
        }
        
        $detail['create_time'] = date('Y-m-d H:i', $detail['create_time']);
        
        return $detail;
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
