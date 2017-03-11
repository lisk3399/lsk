<?php
namespace Adminxb\Model;
use Think\Model;

/**
* 
*/
class OrgnizationModel extends Model
{
   protected $_validate = array(
   	 array('uid','require','校区ID不能为空'),
     array('uid','/^[0-9]*[1-9][0-9]*$/','校区ID只能为正整数',self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH), //默认情况下用正则进行验证
     array('group_name','require','班级名称不能为空！'), // 在新增的时候验证name字段是否唯一
     array('schooling','/^[0-9]*[1-9][0-9]*$/','额度必须为正整数'),
     array('number_passenger','/^[0-9]*[1-9][0-9]*$/','额度必须为正整数',self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),

   );

    
}

