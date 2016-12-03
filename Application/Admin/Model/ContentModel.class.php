<?php
namespace Admin\Model;
use Think\Model;

class ContentModel extends Model{

        public $_validate = array(
         array('uid', '/^[\d]+$/', 'ID只能填正整数', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
         array('uid', 'require', 'ID不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
         array('title', 'require', '标题不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
         array('title', '1,80', '标题长度不能超过80个字符', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
         array('description', '1,400', '描述长度不能超过400个字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
         array('create_time', 'require', '时间不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH), 
    );

    public function update($data = null){
        $data = $this->create($_POST);
        $this->create_time = strtotime($_POST['create_time']);
        if(empty($data)){
            return false;
        }
        /* 添加或新增行为 */
        if(empty($data['id'])){ //新增数据

            $id = $this->add(); //添加行为
            $GLOBALS['id']=$id;
            if(!$id){
                $this->error = '新增行为出错！';
                return false;
            }
        } else { //更新数据
            $status = $this->save(); //更新基础内容
            if(false === $status){
                $this->error = '更新行为出错！';
                return false;
            }
        }
        //删除缓存
        
        S('action_list', null);

        //内容添加或更新完成
        return $data;
    }
    public function update1($data = null){
        $_POST[]= $GLOBALS['sqlid'];
        
        $data = $this->create($_POST );
        
        $this->create_time = strtotime($_POST['create_time']);
   
        if(empty($data)){
            return false;
        }
        /* 添加或新增行为 */
        if(empty($data['id'])){ //新增数据

            $id = $this->add(); //添加行为
            $GLOBALS['id']=$id;
            if(!$id){
                $this->error = '新增行为出错！';
                return false;
            }
        } else { //更新数据
            $data = $this->save(); //更新基础内容
            if(false === $status){
                $this->error = '更新行为出错！';
                return false;
            }
        }
        //删除缓存
        
        S('action_list', null);

        //内容添加或更新完成
        return $data;
    }
}