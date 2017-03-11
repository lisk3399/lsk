<?php
namespace Admin\Model;
use Think\Model;

/**
* 
*/
class OrgnizationModel extends Model
{
    public function update(){
        /* 获取数据对象 */

        $data = $this->create($_POST);
        if(empty($data)){
            return false;
        }

        /* 添加或新增行为 */
        if(empty($data['id'])){ //新增数据
            $id = $this->add(); //添加行为
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

    
}

