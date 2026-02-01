<?php
/**
 * MacCMS10 兼容接口 for FeiFeiCMS
 * 放在 Lib/Lib/Action/Plus/MacAction.class.php
 * 输出 JSON 格式与 MacCMS10 自带接口一致
 */
class MacAction extends HomeAction {

    // 判断IP是否合法
    public function _initialize(){
        if(C('collect_ips')){
            if(!in_array(get_client_ip(), explode(',', C('collect_ips')))){
                exit(json_encode(array('code'=>0,'msg'=>'IP未授权')));
            }
        }
    }

    // json接口
    public function json(){
        $params = array();

        // 参数获取
        $params['ids']   = isset($_GET['ids']) ? htmlspecialchars($_GET['ids']) : '';
        $params['cid']   = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        $params['wd']    = isset($_GET['wd']) ? htmlspecialchars(urldecode($_GET['wd'])) : '';
        $params['limit'] = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
        $params['h']     = isset($_GET['h']) ? intval($_GET['h']) : 0;
        $params['page']  = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $params['action']= isset($_GET['action']) ? $_GET['action'] : '';

        if($params['limit'] > 100) $params['limit'] = 100;

        // 查询条件
        $where = array();
        $where['vod_status'] = array('gt',-1);

        // 分类
        if($params['cid']){
            $where['vod_cid'] = array('in', ff_list_ids($params['cid']));
        }

        // 影片ids
        if($params['ids']){
            $where['vod_id'] = array('in',$params['ids']);
        }

        // 时间限制
        if($params['h']){
            if($params['h'] == 24){
                $time = ff_linux_time(1);
            } elseif($params['h'] == 98){
                $time = ff_linux_time(7);
            } else {
                $time = time() - $params['h']*3600;
            }
            $where['vod_time_add'] = array('gt',$time);
        }

        // 搜索
        if($params['wd']){
            $where['vod_name'] = array('like','%'.$params['wd'].'%');
        }

        // 排序
        if($params['action'] == 'all'){
            $order = 'vod_time_add asc,vod_id asc';
        } else {
            $order = 'vod_time_add desc,vod_id desc';
        }

        // 字段映射 MacCMS10 风格
        $field = 'vod_id,vod_name,vod_ename,vod_cid,vod_actor,vod_director,vod_content,vod_pic,vod_area,vod_language,vod_year,vod_time_add,vod_play,vod_url,vod_hits,vod_status,vod_isend,vod_total,vod_douban_id';

        // 查询数据
        $limit_start = ($params['page']-1)*$params['limit'];
        $list = M('Vod')->field($field)->where($where)->order($order)->limit($limit_start,$params['limit'])->select();

        // 数据处理
        foreach($list as $k=>$v){
            $list[$k]['vod_pic'] = ff_url_img($v['vod_pic'],$v['vod_content']);
            $list[$k]['vod_time_add'] = date('Y-m-d H:i:s',$v['vod_time_add']);
        }

        // 总记录数
        $count = M('Vod')->where($where)->count();

        // 分页数据
        $pages = ceil($count / $params['limit']);

        // 输出JSON MacCMS10风格
        $data = array(
            'code'      => 1,
            'msg'       => 'success',
            'page'      => $params['page'],
            'pagecount' => $pages,
            'limit'     => $params['limit'],
            'total'     => $count,
            'list'      => $list
        );

        echo json_encode($data);
        exit;
    }
}
?>
