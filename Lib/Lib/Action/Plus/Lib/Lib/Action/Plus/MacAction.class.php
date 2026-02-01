<?php
/**
 * FeiFeiCMS 4.3 → MacCMS v10 JSON API
 */

class ApiAction extends HomeAction
{
    public function json()
    {
        $ac = $_GET['ac'] ?: 'videolist';
        if ($ac == 'detail') {
            $this->detail();
        } else {
            $this->videolist();
        }
    }

    /**
     * 视频列表
     */
    private function videolist()
    {
        $params = [];
        $params['status']  = 1;
        $params['limit']   = min(intval($_GET['limit'] ?: 20), 100);
        $params['page_p']  = intval($_GET['page'] ?: 1);
        $params['page_is'] = true;
        $params['page_id'] = 'ffapi';

        $params['field'] = '
            vod_id,vod_cid,vod_name,vod_actor,vod_director,
            vod_content,vod_pic,vod_area,vod_language,vod_year,
            vod_play,vod_url,vod_addtime
        ';

        if (!empty($_GET['t'])) {
            $params['cid'] = ff_list_ids(intval($_GET['t']));
        }

        if (!empty($_GET['ids'])) {
            $params['ids'] = htmlspecialchars($_GET['ids']);
        }

        if (!empty($_GET['wd'])) {
            $params['wd'] = htmlspecialchars(urldecode($_GET['wd']));
        }

        if (!empty($_GET['h'])) {
            $params['addtime'] = time() - intval($_GET['h']) * 3600;
        }

        $params['order'] = 'vod_addtime desc,vod_id desc';

        $list = ff_mysql_vod($params);
        $data = [];

        foreach ($list as $v) {
            // 拆播放器
            list($from, $url) = $this->splitPlayer(
                $v['vod_play'],
                $v['vod_url']
            );

            $data[] = [
                'vod_id'        => $v['vod_id'],
                'type_id'       => $v['vod_cid'],
                'vod_name'      => $v['vod_name'],
                'vod_actor'     => $v['vod_actor'],
                'vod_director'  => $v['vod_director'],
                'vod_content'   => $v['vod_content'],
                'vod_pic'       => ff_url_img($v['vod_pic'], $v['vod_content']),
                'vod_area'      => $v['vod_area'],
                'vod_lang'      => $v['vod_language'],
                'vod_year'      => $v['vod_year'],
                'vod_play_from' => $from,
                'vod_play_url'  => $url,
                'vod_time'      => date('Y-m-d H:i:s', $v['vod_addtime'])
            ];
        }

        $page = $_GET['ff_page_ffapi'];

        echo json_encode([
            'code'      => 1,
            'msg'       => 'ok',
            'page'      => intval($page['currentpage']),
            'pagecount' => intval($page['totalpages']),
            'limit'     => $params['limit'],
            'total'     => intval($page['records']),
            'list'      => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 视频详情
     */
    private function detail()
    {
        if (empty($_GET['ids'])) {
            exit(json_encode(['code'=>0,'msg'=>'ids empty']));
        }

        $params['ids']   = htmlspecialchars($_GET['ids']);
        $params['field'] = '
            vod_id,vod_cid,vod_name,vod_actor,vod_director,
            vod_content,vod_pic,vod_area,vod_language,vod_year,
            vod_play,vod_url,vod_addtime
        ';

        $list = ff_mysql_vod($params);
        $data = [];

        foreach ($list as $v) {
            list($from, $url) = $this->splitPlayer(
                $v['vod_play'],
                $v['vod_url']
            );

            $data[] = [
                'vod_id'        => $v['vod_id'],
                'type_id'       => $v['vod_cid'],
                'vod_name'      => $v['vod_name'],
                'vod_actor'     => $v['vod_actor'],
                'vod_director'  => $v['vod_director'],
                'vod_content'   => $v['vod_content'],
                'vod_pic'       => ff_url_img($v['vod_pic'], $v['vod_content']),
                'vod_area'      => $v['vod_area'],
                'vod_lang'      => $v['vod_language'],
                'vod_year'      => $v['vod_year'],
                'vod_play_from' => $from,
                'vod_play_url'  => $url,
                'vod_time'      => date('Y-m-d H:i:s', $v['vod_addtime'])
            ];
        }

        echo json_encode([
            'code' => 1,
            'msg'  => 'ok',
            'list' => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 播放器拆分核心函数
     */
    private function splitPlayer($play, $url)
    {
        if (!$play || !$url) {
            return ['', ''];
        }

        $playArr = explode('$$$', $play);
        $urlArr  = explode('$$$', $url);

        $fromArr = [];
        $urlOut  = [];

        foreach ($playArr as $k => $player) {
            if (empty($urlArr[$k])) continue;

            // 播放器名称映射（可按你资源站习惯改）
            $player = strtolower($player);
            $map = [
                'm3u8' => 'm3u8',
                'qvod' => 'qvod',
                'yun'  => 'yun'
            ];
            $fromArr[] = $map[$player] ?? $player;
            $urlOut[]  = $urlArr[$k];
        }

        return [
            implode('$$$', $fromArr),
            implode('$$$', $urlOut)
        ];
    }
}
