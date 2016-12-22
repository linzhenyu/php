<?php

namespace common\models\service;

use Yii;
use yii\base\Controller;
use common\models\service\BaseService;
use yii\mongodb\Query;
use common\models\odm\PcGame;
use yii\data\Pagination;
use yii\widgets\LinkPager;

/**
 * mongodb 数据库操作底层服务
 * @time      2016-07-14
 * @author    WQ   <577729657@qq.com>
 */
class MongoService extends BaseService{

    /**
     * 初始化，每个Service都必须执行此方法
     * @param    string $className
     * @return   MongoService //必须添加这行注释，用于代码提示
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


	/**
	 * 生成 page 分页代码，返回查询的数据
	 * @param     string     $database     数据库表名
	 * @param     integer    $pagesize     每页数据条数
	 * @param     array      $where        查询 where 条件
	 * @param     string     $order        查询数据排序条件
	 * @return    array                    查询出的数据和分页代码
	 */
	public function getAllPage($database,$pagesize=1,$where=null,$order='',$like='',$or=null){

		$count = $this->getCount($database,$where,$like,$or);   //获取查询数据的总数
		$pag = $this->createPage($pagesize,$count);   //生成分页代码
		$data['page'] = $pag['page'];
		$data['count'] = $count;
		$data['pagenum'] = ceil($count / self::PAGE_PER_NUMBER);
		$data['data'] = $this->selectAll($database,$where,$order,$filed=null,$pag['pagination']->offset,$pag['pagination']->limit,$like,$or);
		return $data;
	}

    /**
     * 生成 page 分页代码，返回查询的数据
     * @param     string     $database     数据库表名
     * @param     integer    $pagesize     每页数据条数
     * @param     array      $where        查询 where 条件
     * @param     string     $order        查询数据排序条件
     * @return    array                    查询出的数据和分页代码
     */
    public function getAllPageFromTwoTables($database1,$database2, $pagesize=1,$where=null,$order='',$like='',$or=null){

        $count1 = $this->getCount($database1,$where,$like,$or);
        $count2 = $this->getCount($database2,$where,$like,$or);
        $count = $count1 + $count2;  //获取查询数据的总数

        $pag = $this->createPage($pagesize,$count);   //生成分页代码
        $data['page'] = $pag['page'];
        $data['count'] = $count;
        $data['pagenum'] = floor($count / self::PAGE_PER_NUMBER) + 1;
        $data['data'] = $this->selectAll($database1,$where,$order,$filed=null,$pag['pagination']->offset,$pag['pagination']->limit,$like,$or);
        if(count($data['data']) < $pagesize) {
            $data2 = $this->selectAll($database2,$where,$order,$filed=null,$pag['pagination']->offset,$pag['pagination']->limit - count($data['data']),$like,$or);
//            var_dump($data['data']); var_dump($data2);exit();
            $data['data'] = array_merge($data['data'], $data2);
        }
        return $data;
    }

	/**
	 * 数据库插入函数
	 * @param        string     $type      数据库表名
	 * @param        array      $data      需要插入的数据
	 * @return       mix                   成功返回插入数据生成的ID,失败返回false
	 */
	public function insert($database,$data){
		$mongo = Yii::$app->mongodb->getCollection($database);
		if($res = $mongo->insert($data)){
			return $res;
		}
		return false;
	}

	/**
	 * 查询所有数据
	 * @param      string     $database      数据库表名
	 * @param      array      $where         查询 where 条件
	 * @param      string     $order         查询数据排序条件
	 * @param      array      $filed         查询字段
	 * @param      integer    $offset        查询开始数
	 * @param      integer    $limit         查询数据条数
	 * @return     array                     查询出的数据
	 */
	public function countAll($database,$where=null){
		$query = new Query();
		if(!empty($filed)){
			$query = $query->select($filed);
		}
		if(!empty($where)){
			$query = $query->where($where);
		}
		if(!empty($like)){
			$query = $query->andWhere($like);
		}
		if(!empty($and)){
			$query = $query->andWhere($and);
		}
		if(!empty($and)){
			$query = $query->andWhere($and);
		}
		if(!empty($or)){
			$query = $query->orWhere($or);
		}
		$count = $query->from($database)->count();
		return $count;
	}

	/**
	 * 查询所有数据
	 * @param      string     $database      数据库表名
	 * @param      array      $where         查询 where 条件
	 * @param      string     $order         查询数据排序条件
	 * @param      array      $filed         查询字段
	 * @param      integer    $offset        查询开始数
	 * @param      integer    $limit         查询数据条数
	 * @return     array                     查询出的数据
	 */
	public function selectAll($database,$where=null,$order=null,$filed=null,$offset=null,$limit=null,$like=null,$or=null){
		$query = new Query();
		if(!empty($filed)){
			$query = $query->select($filed);
		}
		if(!empty($where)){
			$query = $query->where($where);
		}
		if(!empty($like)){
			$query = $query->andWhere($like);
		}
		if(!empty($or)){
			$query = $query->orWhere($or);
		}
		if(!($offset===null)){
			$query = $query->offset($offset);
		}
		if(!empty($limit)){
			$query = $query->limit($limit);
		}
		if(!empty($order)){
			$query = $query->orderby($order);
		}
		$data = $query->from($database)->all();
		foreach ($data as $k => $v) {
			$data[$k]['_id'] = (string)$v['_id'];
		}
		return $data;
	}

	/**
	 * 查询一条数据
	 * @param      string    $database     数据库表名
	 * @param      array     $where        查询 where 条件
	 * @param      array     $filed        查询字段
	 * @return     mix                     查询出的数据，没有数据返回 false
	 */
	public function selectOne($database,$where,$filed=null){
		$query = new Query();
		if(!empty($filed)){
			$query = $query->select($filed);
		}
		if(!empty($where)){
			$query = $query->where($where);
		}
		if($data = $query->from($database)->one()){
			$data['_id'] = (string)$data['_id'];
			return $data;
		}
		return false;
	}

	/**
	 * 更新数据
	 * @param      string     $database      数据库表名
	 * @param      array      $where         查询 where 条件
	 * @param      array      $data          需要更新的数据
	 * @return     boolean                   成功 true , 失败 false
	 */
	public function update($database,$where,$data){
		$mongo = Yii::$app->mongodb->getCollection($database);
		if($mongo->update($where,$data)){
			return true;
		}
		return false;
	}

	/**
	 * 删除数据
	 * @param       string     $database      数据库表名
	 * @param       array      $where         查询 where 条件
	 * @return      boolean                   成功 true , 失败 false
	 */
	public function delete($database,$where){
		$mongo = Yii::$app->mongodb->getCollection($database);
		if($mongo->remove($where)){
			return true;
		}
		return false;
	}
	/*=========================================================================
	||===========================  工 具 函 数  =============================||
	=========================================================================*/

	/**
	 * 获取查询数据总数
	 * @param       string     $database      数据库表名
	 * @param       array      $where         查询 where 条件
	 * @return      integer                   查询数据的总数
	 */
	public function getCount($database,$where,$like,$or){
		$query = new Query();
		if(!empty($where)){
			if(!empty($like)){
				if(!empty($or)){
					return $query->where($where)->andWhere($like)->orWhere($or)->from($database)->count();
				}
				return $query->where($where)->andWhere($like)->from($database)->count();
			}
			return $query->where($where)->from($database)->count();
		}
		return $query->from($database)->count();
	}

	/**
	 * 将字符串拆分为数组
	 * @param       string     $data      需要拆分的字符串
	 * @return      array                 拆分后的数组
	 */
	public function explodeData($data){
		return explode(',',preg_replace("/(，)|(,)/",',',$data));
	}

	/**
	 * 生成分页代码
	 * @param       integer    $pagesize      每页数据条数
	 * @param       integer    $count         数据总数
	 * @return      array                     生成的分页代码
	 */
	public function createPage($pagesize,$count){
		$data['pagination'] = new Pagination([
	        'defaultPageSize' => $pagesize,
	        'totalCount' => intval($count),
	    ]);
	    $data['page'] = LinkPager::widget([
			'pagination'=>$data['pagination'],
			'firstPageLabel'=>"首页",
		    'prevPageLabel'=>'上一页',
		    'nextPageLabel'=>'下一页',
		    'lastPageLabel'=>'尾页',
	    ]);
	    return $data;
	}
}