<?php
/* @name MacCMS兼容Api插件
 * @支持IP授权，支持缓存，支持播放器拆分
 * 用法示例：
 * http://www.xxx.com/index.php?s=plus-mac-json-vodids-12,13,14-cid-8-limit-20-wd-刘德华-h-24-p-22-play-qvod-inputer-admin
 */
class MacAction extends HomeAction {

    // 判断IP是否合法
    public function _initialize(){
        if(C('collect_ips')){
            if(!in_array(get_client_ip(), explode(',', C('collect_ips')))){
                exit(json_encode(array('status'=>501,'data'=>'IP未授权')));
            }
        }
    }

    // JSON接口
    public function json(){
        $params = array();
        $params['cid'] = ff_list_ids(intval($_GET['cid']));
        $params['ids'] = htmlspecialchars($_GET['vodids']);
        $params['limit'] = !empty($_GET['limit'])?intval($_GET['limit']):30;
        $params['wd'] = htmlspecialchars(urldecode($_GET['wd']));
        $params['play'] = htmlspecialchars($_GET['play']);
        $params['inputer'] = htmlspecialchars(urldecode($_GET['inputer']));
        $params['page_p'] = !empty($_GET['p'])?intval($_GET['p']):1;

        // 额外参数
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

        $params['field'] = 'vod_id,vod_cid,vod_name,vod_title,vod_actor,vod_director,vod_content,vod_pic,vod_area,vod_language,vod_year,vod_addtime,vod_server,vod_play,vod_url,vod_inputer,vod_reurl,vod_length,vod_weekday,vod_copyright,vod_status,vod_douban_id';
        $params['status'] = 1;
        $params['cache_name'] = 'default';
        $params['cache_time'] = 'default';
        $params['page_is'] = true;
        $params['page_id'] = 'ffapi';

        // 限制API类型排序
        if($_GET['action']=='all'){
            $params['order'] = 'vod_addtime asc,vod_id asc';
        } else {
            $params['order'] = 'vod_addtime desc,vod_id desc';
        }

        // 限制数量
        if($params['limit']>100){
            $params['limit'] = 100;
        }

        // 限制时间
        if($_GET['h']>0){
            if($_GET['h']==24){
                $params['addtime'] = ff_linux_time(1);
            } elseif($_GET['h']==98){
                $params['addtime'] = ff_linux_time(7);
            } else {
                $params['addtime'] = time()-intval($_GET['h'])*3600;
            }
        }

        // 自定义排序
        if($_GET['order'] && $_GET['sort']){
            $params['order'] = 'vod_'.ff_order_by($_GET['order']);
            $params['sort'] = ($_GET['sort']=='asc')?'asc':'desc';
        }

        // 查询数据库
        $array_data = ff_mysql_vod($params);

        // 播放器拆分处理
        foreach($array_data as $key=>$val){
            $array_data[$key]['vod_pic'] = ff_url_img($val['vod_pic'], $val['vod_content']);
            $array_data[$key]['vod_addtime'] = date('Y-m-d H:i:s', $val['vod_addtime']);
            if($params['play']){
                $array_data[$key]['vod_url'] = $this->json_url($val['vod_play'], $val['vod_url'], $params['play']);
                $array_data[$key]['vod_play'] = trim($params['play']);
            } else {
                // 拆分所有播放源
                $play_list = explode('$$$',$val['vod_play']);
                $url_list = explode('$$$',$val['vod_url']);
                $array_data[$key]['vod_play_list'] = array_combine($play_list,$url_list);
            }
        }

        // 分页
        $page = $_GET['ff_page_ffapi'];
        $array_page = array(
            'pageindex'=>$page['currentpage'],
            'pagecount'=>$page['totalpages'],
            'pagesize'=>$params['limit'],
            'recordcount'=>$page['records']
        );

        // 分类列表
        $array_list = D("List")->ff_select_page(array(
            'field'=>'list_id,list_name',
            'limit'=>false,
            'order'=>'list_id asc,list_oid',
            'cache_name'=>C('cache_foreach_prefix').'_ffapi_list',
            'cache_time'=>intval(C('cache_foreach'))
        ),'list_sid=1 and list_status=1');

        echo json_encode(array('status'=>200,'page'=>$array_page,'list'=>$array_list,'data'=>$array_data));
    }

    // 播放器URL匹配
    private function json_url($vod_play,$vod_url,$url_play){
        $play_arr = explode('$$$',$vod_play);
        $url_arr = explode('$$$',$vod_url);
        $key = array_search(trim($url_play),$play_arr);
        return isset($url_arr[$key])?$url_arr[$key]:'';
    }

}
?>
