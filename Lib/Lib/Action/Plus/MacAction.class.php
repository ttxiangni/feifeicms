<?php
/**
 * MacCMS接口适配器 for FeiFeiCMS
 * 放在 Lib/Lib/Action/Plus/MacAction.class.php
 * 2026.02.01
 */
class MacAction extends HomeAction {

    // 初始化 IP 授权
    public function _initialize() {
        if(C('collect_ips')){
            if(!in_array(get_client_ip(), explode(',', C('collect_ips')))){
                exit(json_encode(array('code'=>0,'msg'=>'IP未授权')));
            }
        }
    }

    // JSON接口（影片）
    public function json() {
        // 复用 FeiFei 的参数解析
        $params = array();
        $params['cid']       = ff_list_ids(intval($_GET['cid']));
        $params['ids']       = htmlspecialchars($_GET['vodids']);
        $params['limit']     = !empty($_GET['limit'])?intval($_GET['limit']):30;
        $params['wd']        = htmlspecialchars(urldecode($_GET['wd']));
        $params['play']      = htmlspecialchars($_GET['play']);
        $params['inputer']   = htmlspecialchars(urldecode($_GET['inputer']));
        $params['page_p']    = !empty($_GET['p'])?intval($_GET['p']):1;
        $params['area']      = htmlspecialchars(urldecode($_GET['area']));
        $params['year']      = implode(',', str_split(htmlspecialchars($_GET['year']),4));
        $params['language']  = htmlspecialchars(urldecode($_GET['language']));
        $params['actor']     = htmlspecialchars(urldecode($_GET['actor']));
        $params['director']  = htmlspecialchars(urldecode($_GET['director']));
        $params['field']     = 'list_name,vod_id,vod_cid,vod_name,vod_title,vod_type,vod_keywords,vod_actor,vod_director,vod_content,vod_pic,vod_area,vod_language,vod_year,vod_addtime,vod_server,vod_play,vod_url,vod_inputer,vod_reurl,vod_length,vod_weekday,vod_copyright,vod_state,vod_version,vod_tv,vod_total,vod_continu,vod_status,vod_stars,vod_hits,vod_isend,vod_douban_id,vod_series';
        $params['status']    = 1;
        $params['page_is']   = true;
        $params['page_id']   = 'ffapi';

        // 时间限制
        if($_GET['h'] > 0){
            if($_GET['h']==24){
                $params['addtime'] = ff_linux_time(1);
            }elseif($_GET['h']==98){
                $params['addtime'] = ff_linux_time(7);
            }else{
                $params['addtime'] = time()-intval($_GET['h'])*3600;
            }
        }

        // 排序
        if($_GET['action']=='all'){
            $params['order'] = 'vod_addtime asc,vod_id asc';
        }else{
            $params['order'] = 'vod_addtime desc,vod_id desc';
        }

        if($_GET['order'] && $_GET['sort']){
            $params['order'] = 'vod_'.ff_order_by($_GET['order']);
            $params['sort']  = $_GET['sort']=='asc'?'asc':'desc';
        }

        // 调用 FeiFei 原生取数
        $array_data = ff_mysql_vod($params);

        foreach($array_data as $key=>$val){
            $array_data[$key]['vod_pic'] = ff_url_img($val['vod_pic'],$val['vod_content']);
            $array_data[$key]['vod_addtime'] = date('Y-m-d H:i:s',$val['vod_addtime']);

            // 播放器处理
            if($params['play']){
                $array_data[$key]['vod_url'] = $this->json_url($val['vod_play'],$val['vod_url'],$params['play']);
                $array_data[$key]['vod_play_from'] = trim($params['play']);
            } else {
                // 默认第一个播放器
                $play_arr = explode('$$$',$val['vod_play']);
                $url_arr  = explode('$$$',$val['vod_url']);
                $array_data[$key]['vod_play_from'] = trim($play_arr[0]);
                $array_data[$key]['vod_url'] = $url_arr[0];
            }
        }

        // 分页
        $page = $_GET['ff_page_ffapi'];
        $array_page = array(
            'page'      => $page['currentpage'],
            'pagecount' => $page['totalpages'],
            'limit'     => $params['limit'],
            'total'     => $page['records']
        );

        // 输出 MacCMS JSON 格式
        echo json_encode(array(
            'code'  => 1,
            'msg'   => 'success',
            'page'  => $array_page['page'],
            'pagecount'=> $array_page['pagecount'],
            'limit' => $array_page['limit'],
            'total' => $array_page['total'],
            'list'  => $array_data
        ));
    }

    // 播放器 URL 解析
    private function json_url($vod_play,$vod_url,$url_play){
        $play_arr = explode('$$$',$vod_play);
        $url_arr  = explode('$$$',$vod_url);
        $key = array_search(trim($url_play),$play_arr);
        return isset($url_arr[$key]) ? $url_arr[$key] : $url_arr[0];
    }
}
