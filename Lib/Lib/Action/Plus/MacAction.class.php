<?php
class MacAction extends HomeAction {

    public function _initialize(){
        // IP权限验证，可启用
        // if(C('collect_ips')){
        //     if( !in_array(get_client_ip(), explode(',', C('collect_ips'))) ){
        //         exit(json_encode(array('status'=>501, 'data'=>'IP未授权')));
        //     }
        // }
    }

    public function json(){
        $mac_params = array();
        $mac_params['ac'] = $_GET['ac'];

        $params = array();
        $params['cid'] = ff_list_ids(intval($_GET['t']));
        $params['ids'] = htmlspecialchars($_GET['ids']);
        $params['limit'] = !empty($_GET['limit'])?intval($_GET['limit']):30;
        $params['wd'] = htmlspecialchars(urldecode($_GET['wd']));
        $params['play'] = htmlspecialchars($_GET['play']);
        $params['inputer'] = htmlspecialchars(urldecode($_GET['inputer']));
        $params['page_p'] = !empty($_GET['pg'])?intval($_GET['pg']):1;

        $params['area'] = htmlspecialchars(urldecode($_GET['area']));
        $params['year'] = implode(',',str_split(htmlspecialchars($_GET['year']),4));
        $params['language'] = htmlspecialchars(urldecode($_GET['language']));
        $params['actor'] = htmlspecialchars(urldecode($_GET['actor']));
        $params['director'] = htmlspecialchars(urldecode($_GET['director']));
        $params['writer'] = htmlspecialchars(urldecode(trim($_GET['writer'])));
        $params['letter'] = htmlspecialchars($_GET['letter']);
        $params['state'] = htmlspecialchars(urldecode(trim($_GET['state'])));
        $params['ename'] = htmlspecialchars(trim($_GET['ename']));
        $params['name'] = htmlspecialchars(urldecode(trim($_GET['ename'])));

        $params['field'] = 'list_name,vod_id,vod_cid,vod_name,vod_title,vod_type,vod_keywords,vod_actor,vod_director,vod_content,vod_pic,vod_area,vod_language,vod_year,vod_addtime,vod_filmtime,vod_server,vod_play,vod_url,vod_inputer,vod_reurl,vod_length,vod_weekday,vod_copyright,vod_state,vod_version,vod_tv,vod_total,vod_continu,vod_status,vod_stars,vod_hits,vod_isend,vod_douban_id,vod_series';
        if($mac_params['ac']=="list"){
            $params['field'] = 'list_name,vod_id,vod_cid,vod_name,vod_ename,vod_addtime,vod_play,vod_total,vod_continu';
        }

        $params['status'] = 1;
        $params['cache_name'] = 'default';
        $params['cache_time'] = 'default';
        $params['page_is']= true;
        $params['page_id']= 'ffapi';

        if($_GET['action'] == 'all'){
            $params['order'] = 'vod_addtime asc,vod_id asc';
        } else {
            $params['order'] = 'vod_addtime desc,vod_id desc';
        }

        if($params['limit'] > 100) $params['limit'] = 100;

        if($_GET['h'] > 0){
            if($_GET['h'] == 24){
                $params['addtime'] = ff_linux_time(1);
            } elseif($_GET['h'] == 98){
                $params['addtime'] = ff_linux_time(7);
            } else {
                $params['addtime'] = time()-intval($_GET['h'])*3600;
            }
        }

        if($_GET['order'] && $_GET['sort']){
            $params['order'] = 'vod_'.ff_order_by($_GET['order']);
            $params['sort'] = $_GET['sort']=='asc'?'asc':'desc';
        }

        $array_data = ff_mysql_vod($params);

        // 过滤只保留 youku、qq、iqiyi
        $allowed_sources = ['youku','qq','iqiyi'];
        $filtered_data = [];

        foreach($array_data as $key=>$val){
            if(!in_array(strtolower($val['vod_play']), $allowed_sources)){
                continue; // 不包含允许的源就跳过
            }

            $val['type_id'] = $val['vod_cid'];
            $val['type_name'] = $val['list_name'];
            $val['vod_class'] = $val['vod_type'];
            $val['vod_sub'] = $val['vod_title'];
            $val['vod_pubdate'] = intval($val['vod_filmtime'])>0 ? date('Y-m-d',$val['vod_filmtime']) : $val['vod_year'];
            $val['vod_tag'] = $val['vod_keywords'];
            $val['vod_level'] = $val['vod_stars'];
            $val['vod_author'] = $val['vod_inputer'];
            $val['vod_score_all'] = $val['vod_gold'];
            $val['vod_score_num'] = $val['vod_golder'];
            $val['vod_pic_slide'] = $val['vod_pic_bg'];
            $val['vod_duration'] = $val['vod_length'];
            $val['vod_en'] = $val['vod_ename'];
            $val['vod_time'] = date('Y-m-d H:i:s', $val['vod_addtime']);
            $val['vod_remarks'] = $val['vod_continu'];
            $val['vod_serial'] = $this->isHaveKC($val['vod_continu']);
            $val['vod_lang'] = $val['vod_language'];
            $val['vod_play_from'] = $val['vod_play'];
            $val['vod_play_url'] = $this->json_url($val['vod_play'], $val['vod_url'], $params['play']);
            $val['vod_pic'] = ff_url_img($val['vod_pic'], $val['vod_content']);
            $val['vod_time_add'] = $val['vod_addtime'];

            $unset_keys = ['vod_cid','list_name','vod_type','vod_title','vod_filmtime','vod_keywords','vod_stars','vod_inputer','vod_gold','vod_golder','vod_pic_bg','vod_length','vod_ename','vod_continu','vod_language','vod_play','vod_url','vod_addtime'];
            foreach($unset_keys as $uk) unset($val[$uk]);

            $filtered_data[] = $val;
        }

        $page = $_GET['ff_page_ffapi'];
        $array_page = [
            'pageindex' => $page['currentpage'],
            'pagecount' => $page['totalpages'],
            'pagesize' => $params['limit'],
            'recordcount' => count($filtered_data)
        ];

        $array_list = [];
        if($mac_params['ac']=="list"){
            $list_params = [
                'field' => 'list_id,list_name',
                'limit' => false,
                'order' => 'list_id asc,list_oid',
                'cache_name' => C('cache_foreach_prefix').'_ffapi_list',
                'cache_time'=> intval(C('cache_foreach'))
            ];
            $array_list = D("List")->ff_select_page($list_params,'list_sid=1 and list_status=1');
            foreach($array_list as $k=>$v){
                $array_list[$k]['type_id'] = $v['list_id'];
                $array_list[$k]['type_name'] = $v['list_name'];
                unset($array_list[$k]['list_id'],$array_list[$k]['list_name'],$array_list[$k]['list_extend']);
            }
        }

        echo json_encode([
            'code'=>1,
            'msg'=>'success',
            'page'=>$array_page['pageindex'],
            'pagecount'=>$array_page['pagecount'],
            'limit'=>$array_page['pagesize'],
            'total'=>$array_page['recordcount'],
            'class'=>$array_list,
            'list'=>$filtered_data
        ]);
    }

    public function json_url($vod_play, $vod_url, $url_play){
        $array_play = explode('$$$',$vod_play);
        $array_url = explode('$$$',$vod_url);
        $key = array_search(trim($url_play),$array_play);
        if($key===false) $key = 0;
        return isset($array_url[$key]) ? $array_url[$key] : '';
    }

    public function isHaveKC($str){
        preg_match_all('/\d+/',$str,$arr);
        $arr = isset($arr[0])?$arr[0]:[];
        return intval(isset($arr[0])?$arr[0]:0);
    }

}
?>
