<?php
/**
 * MacCMS 采集接口插件
 * 放在 Lib/Lib/Action/Plus/MacAction.class.php
 */
class MacAction extends HomeAction {

    // 判断IP是否合法
    public function _initialize() {
        if(C('collect_ips')) {
            if(!in_array(get_client_ip(), explode(',', C('collect_ips')))) {
                exit(json_encode(array('status'=>501, 'data'=>'IP未授权')));
            }
        }
    }

    // 视频 JSON 接口
    public function json() {
        $params = array();

        // 获取参数
        $params['cid']     = intval($_GET['cid']);
        $params['ids']     = htmlspecialchars($_GET['vodids']);
        $params['limit']   = !empty($_GET['limit']) ? intval($_GET['limit']) : 30;
        $params['page']    = !empty($_GET['p']) ? intval($_GET['p']) : 1;
        $params['play']    = isset($_GET['play']) ? htmlspecialchars($_GET['play']) : '';
        $params['wd']      = isset($_GET['wd']) ? htmlspecialchars(urldecode($_GET['wd'])) : '';
        $params['order']   = isset($_GET['order']) ? $_GET['order'] : 'vod_addtime';
        $params['sort']    = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
        $params['h']       = isset($_GET['h']) ? intval($_GET['h']) : 0;
        $params['action']  = isset($_GET['action']) ? $_GET['action'] : '';

        // 限制数量
        if($params['limit'] > 100) $params['limit'] = 100;

        // 构建查询条件
        $where = array('vod_status'=>1);

        // 分类
        if($params['cid']) {
            $where['vod_cid'] = array('in', ff_list_ids($params['cid']));
        }

        // 指定ID
        if($params['ids']) {
            $where['vod_id'] = array('in', $params['ids']);
        }

        // 关键词搜索
        if($params['wd']) {
            $where['vod_name'] = array('like','%'.$params['wd'].'%');
        }

        // 时间过滤
        if($params['h']) {
            if($params['h'] == 24) {
                $time = strtotime(date('Y-m-d 00:00:00')); // 当天
            } elseif($params['h'] == 98) {
                $time = strtotime('this week'); // 本周
            } else {
                $time = time() - $params['h']*3600;
            }
            $where['vod_addtime'] = array('gt', $time);
        }

        // 排序
        $order_sql = 'vod_addtime desc, vod_id desc';
        if($params['action'] == 'all') {
            $order_sql = 'vod_addtime asc, vod_id asc';
        }
        if($params['order'] && $params['sort']) {
            $order_sql = 'vod_'.$params['order'].' '.$params['sort'];
        }

        // 查询视频数据
        $field = 'vod_id,vod_cid,vod_name,vod_title,vod_type,vod_keywords,vod_actor,vod_director,vod_content,vod_pic,vod_area,vod_language,vod_year,vod_addtime,vod_play,vod_url,vod_inputer,vod_reurl,vod_length,vod_weekday,vod_copyright,vod_state,vod_version,vod_tv,vod_total,vod_continu,vod_status,vod_hits,vod_isend,vod_douban_id';
        $start = ($params['page'] - 1) * $params['limit'];
        $list  = M('Vod')->field($field)->where($where)->order($order_sql)->limit($start.','.$params['limit'])->select();

        // 处理播放源
        foreach($list as $k=>$v){
            $list[$k]['vod_pic'] = ff_url_img($v['vod_pic'],$v['vod_content']);
            $list[$k]['vod_addtime'] = date('Y-m-d H:i:s', $v['vod_addtime']);
            if($params['play']) {
                $play_arr = explode('$$$',$v['vod_play']);
                $url_arr  = explode('$$$',$v['vod_url']);
                $key = array_search(trim($params['play']), $play_arr);
                if($key!==false) {
                    $list[$k]['vod_url']  = $url_arr[$key];
                    $list[$k]['vod_play'] = trim($params['play']);
                }
            }
        }

        // 分页信息
        $total = M('Vod')->where($where)->count();
        $pagecount = ceil($total / $params['limit']);
        $pageindex = $params['page'];

        // 分类列表
        $list_data = ff_mysql_list(array(
            'limit'=>'0',
            'sid'=>'1',
            'field'=>'list_id,list_name',
            'order'=>'list_pid asc,list_oid',
            'sort'=>'asc',
            'cache_name'=>'default',
            'cache_time'=>'default'
        ));

        // 输出JSON
        echo json_encode(array(
            'status'    => 200,
            'page'      => array(
                'pageindex' => $pageindex,
                'pagecount' => $pagecount,
                'pagesize'  => $params['limit'],
                'recordcount'=> $total
            ),
            'list'      => $list_data,
            'data'      => $list
        ), JSON_UNESCAPED_UNICODE);
    }
}
?>
