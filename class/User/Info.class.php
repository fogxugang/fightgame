<?php 
 
class User_Info
{

    CONST TABLE_NAME = 'user_info';

    //根据用户ID获取用户基础信息
    public static function getUserInfoByUserId($userId)
    {
        $res = MySql::selectOne(self::TABLE_NAME, array('user_id' => $userId));
        return $res;
    }

    //创建用户基础信息
    public static function createUserInfo($userId, $data)
    {
        if(!$userId || !$data || !is_array($data))return FALSE;
        $info = MySql::selectOne(self::TABLE_NAME, array('user_id' => $userId));
        if($info)return FALSE;
        if(!isset($data['user_name']) || !isset($data['race_id']))return FALSE;
        $res = MySql::insert(self::TABLE_NAME, array(
	        'user_id' => $userId, 
	        'user_name' => $data['user_name'], 
	        'race_id' => $data['race_id'], 
	        'user_level' => 0, 
	        'experience' => 0, 
	        'money' => 0, 
	        'ingot' => 0, 
	        'pack_num' => 40, 
	        'friend_num' => Friend_Info::FRIEND_NUM //好友上限
        ));
        return $res;
    }
    
    //更新用户基础信息
    public static function updateUserInfo($userId, $data)
    {
        if(!$userId || !$data || !is_array($data))return FALSE;
        $info = MySql::selectOne(self::TABLE_NAME, array('user_id' => $userId));
        if($info)return FALSE;
        $updateArray = array();
        isset($data['user_level'])?$updateArray['user_level'] = (int)$data['user_level']:'';
        isset($data['experience'])?$updateArray['experience'] = (int)$data['experience']:'';
        isset($data['money'])?$updateArray['money'] = (int)$data['money']:'';
        isset($data['ingot'])?$updateArray['ingot'] = (int)$data['ingot']:'';
        $res = MySql::update(self::TABLE_NAME, $updateArray, array('user_id' => $userId));
        return $res;
    }
    
    /**
     * 用户信息单项更新
     *
     * @param int		 $userId	用户ID
     * @param string	 $key		变化的项
     * @param string	 $value		值
     * @param string	 $channel	+or-
     */
    public static function updateSingleInfo($userId, $key, $value, $change){
    	if(!$userId || !$key || !$value || !$change)return FALSE;
    	if($change == 1){
    		$change = '+';
    	}elseif($change == 2){
    		$change = '-';
    	}else{
    		return FALSE;
    	}
    	$sql = "UPDATE " . self::TABLE_NAME . " SET $key = " . "$key $change $value WHERE user_id = $userId";
    	$res = Mysql::query($sql);
    	return $res;
    }
    
    public static function testUserInfo($id){
    	
    	$res = MySql::selectOne(self::TABLE_NAME, array('id'=>$id));
    	return $res;
    }
    
    /**
     * 获取用户在战斗时的即时属性
     * 先计算数值,然后就算比率
     * 属性组成
     * 		基本属性
     * 		装备加成
     * 		
     *
     * @param int $user_id	用户ID
     */
    public static function getUserInfoFightAttribute($userId){
    	//先计算出所有数值
    	//然后再算比例
    	$numerical = array(
    		ConfigDefine::USER_ATTRIBUTE_BLOOD,
    		ConfigDefine::USER_ATTRIBUTE_DEFENSE,
    		ConfigDefine::USER_ATTRIBUTE_DODGE,
    		ConfigDefine::USER_ATTRIBUTE_ENDURANCE,
    		ConfigDefine::USER_ATTRIBUTE_HIT,
    		ConfigDefine::USER_ATTRIBUTE_HURT,
    		ConfigDefine::USER_ATTRIBUTE_LUCKY,
    		ConfigDefine::USER_ATTRIBUTE_MAGIC,
    		ConfigDefine::USER_ATTRIBUTE_MAGIC_POWER,
    		ConfigDefine::USER_ATTRIBUTE_PHYSIQUE,
    		ConfigDefine::USER_ATTRIBUTE_POWER,
    		ConfigDefine::USER_ATTRIBUTE_PSYCHIC,
    		ConfigDefine::USER_ATTRIBUTE_QUICK,
    		ConfigDefine::USER_ATTRIBUTE_SPEED    	
    	);//数值型属性
    	//$proportion = array();//比例型属性
    	
    	//根本ID取出所属种族和等级
    	//getUserInfoByUserId
    	$userInfo = self::testUserInfo($user_id);
    	
    	//根据种族和等级取出基础属性(裸属性),把基础属性的数值加入到$numerical里
    	//getInfoByRaceAndLevel
    	$userAttribute = User_Attributes::getInfoByRaceAndLevel($userInfo['race_id'], $userInfo['user_level']);
    	//此处为假数据,需要分割字符串,分割成 属性['数值']
    	$explodeAttribute = explode($userAttribute, ',');
    	foreach ($explodeAttribute as $i=>$key)
    	{
    		if(isset($i) && !empty($key) && is_array($numerical))
    		{
    			$numerical[$i] = $key;
    		}
    	}
    	
    	//根据ID取出所有装备
    	//假设为getEquipInfoByUserId
    	$equipInfo = Equip_Info::getEquipInfoByUserId($userId, TRUE);
    	
    	//循环装备信息,加值类相加,比例类相加,比例类=比例+100%
    	foreach ($equipInfo as $p)
    	{
	    	$equipAttribute = json_decode($p, TRUE);
	    	//基础属性
	    	foreach ($equipAttribute as $m=>$n)
	    	{
	    		if(isset($m) && !empty($n) && is_array($numerical))
	    		{
	    			$numerical[$m] += $n;
	    		}
	    	}
	    	//扩展属性,百分比,判断是百分比数据的,加入$proportion
    	}
    	
    	//判断是否有对手,如果有,属性相生还是相克,计算所得值
    	//因为每个用户都会计算属性,所以相克只在一个用户身上体现就OK了
    	/*if(isset($opponent_id))
    	{
    		$opponent_info = self::testUserInfo($opponent_id);
    		if(int($opponent_info['race_id'] - $user_info['race_id']) = 1 || int($opponent_info['race_id'] - $user_info['race_id']) = '-2')
    		{
    			foreach ($proportion as $a=>$b){
    				$proportion[$a] = ceil($b * 0.97);//减少3%
    			}
    		}
    	}*/
    	/*if(isset($opponent_id)){
    		$opponent_info = self::getUserInfoByUserId($opponent_id);
    		if(int($opponent_info['race_id'] - $user_info['race_id']) = 1 || int($opponent_info['race_id'] - $user_info['race_id']) = '-2')
	    	foreach ($numerical as $x=>$y){
	    		if(isset($x)){
	    			$numerical[$x] = ceil($y * 0.97);
	    		}
	    	}
    	}*/
    	//把每项属性数值型部分乘上比例值部分
    	/*foreach ($proportion as $x=>$y)
    	{
    		if(in_array($x, $numerical)){
    			$numerical[$x] = $numerical * $y;
    		}
    	}*/
    	
    	//得出结果,合成字符串,抛出
    	return $numerical;
    }
}
