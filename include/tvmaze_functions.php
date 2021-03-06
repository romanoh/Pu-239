<?php
/**
 * @param $tvmaze_data
 * @param $tvmaze_type
 *
 * @return string
 */
function tvmaze_format($tvmaze_data, $tvmaze_type)
{
    $tvmaze_display['show'] = [
        'name'           => '<b>%s</b>',
        'url'            => '%s',
        'premiered'      => 'Started: %s',
        'origin_country' => 'Country: %s',
        'status'         => 'Status: %s',
        'type'           => 'Classification: %s',
        'summary'        => 'Summary:<br> %s',
        'runtime'        => 'Runtime %s min',
        'genres2'        => 'Genres: %s',
    ];
    foreach ($tvmaze_display[ $tvmaze_type ] as $key => $value) {
        if (isset($tvmaze_data[ $key ])) {
            $tvmaze_display[ $tvmaze_type ][ $key ] = sprintf($value, $tvmaze_data[ $key ]);
        } else {
            $tvmaze_display[ $tvmaze_type ][ $key ] = sprintf($value, 'None Found');
        }
    }

    return join('<br><br>', $tvmaze_display[ $tvmaze_type ]);
}

/**
 * @param $torrents
 *
 * @return string
 */
function tvmaze(&$torrents)
{
    global $cache;
    $tvmaze_data = '';
    $row_update = [];
    if (preg_match("/^(.*)S\d+(E\d+)?/i", $torrents['name'], $tmp)) {
        $tvmaze = [
            'name' => str_replace(['.', '_'], ' ', $tmp[1]),
        ];
    } else {
        $tvmaze = [
            'name' => str_replace(['.', '_'], ' ', $torrents['name']),
        ];
    }
    $memkey = 'tvmaze::' . strtolower($tvmaze['name']);
    $tvmaze_id = $cache->get($memkey);
    if ($tvmaze_id === false || is_null($tvmaze_id)) {
        //get tvrage id
        $tvmaze_link = sprintf('http://api.tvmaze.com/singlesearch/shows?q=%s', urlencode($tvmaze['name']));
        $tvmaze_array = json_decode(file_get_contents($tvmaze_link), true);
        if ($tvmaze_array) {
            $tvmaze_id = $tvmaze_array['id'];
            $cache->set($memkey, $tvmaze_id, 0);
        } else {
            return false;
        }
    }
    $force_update = false;
    if (empty($torrents['newgenre']) || empty($torrents['poster'])) {
        $force_update = true;
    }
    $memkey = 'tvrage::' . $tvmaze_id;
    if ($force_update || ($tvmaze_showinfo = $cache->get($memkey)) === false) {
        //var_dump('Show from tvrage'); //debug
        //get tvrage show info
        $tvmaze['name'] = preg_replace('/\d{4}.$/', '', $tvmaze['name']);
        $tvmaze_link = sprintf('http://api.tvmaze.com/shows/%d', $tvmaze_id);
        $tvmaze_array = json_decode(file_get_contents($tvmaze_link), true);
        $tvmaze_array['origin_country'] = $tvmaze_array['network']['country']['name'];
        if (count($tvmaze_array['genres']) > 0) {
            $tvmaze_array['genres2'] = implode(', ', array_map('strtolower', $tvmaze_array['genres']));
        }
        if (empty($torrents['newgenre'])) {
            $row_update[] = 'newgenre = ' . sqlesc(ucwords($tvmaze_showinfo['genres2']));
        }
        //==The torrent cache
        $cache->update_row('torrent_details_' . $torrents['id'], [
            'newgenre' => ucwords($tvmaze_array['genres2']),
        ], 0);
        if (empty($torrents['poster'])) {
            $row_update[] = 'poster = ' . sqlesc($tvmaze_array['image']['original']);
        }
        //==The torrent cache
        $cache->update_row('torrent_details_' . $torrents['id'], [
            'poster' => $tvmaze_array['image']['original'],
        ], 0);
        if (count($row_update)) {
            sql_query('UPDATE torrents SET ' . join(', ', $row_update) . ' WHERE id = ' . $torrents['id']) or sqlerr(__FILE__, __LINE__);
        }
        $tvmaze_showinfo = tvmaze_format($tvmaze_array, 'show') . '<br>';
        $cache->set($memkey, $tvmaze_showinfo, 0);
        $tvmaze_data .= $tvmaze_showinfo;
    } else {
        //var_dump('Show from mem'); //debug
        $tvmaze_data .= $tvmaze_showinfo;
    }

    return $tvmaze_data;
}
