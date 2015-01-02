# -*- coding: UTF-8 -*- 
import g
import dev.v2ex.util

def return_contents(path, params={}) :
    ret_msg = ''
    r = dev.v2ex.util.V2exAPI.get(path)

    limit = int(params.get('limit', params.get('l', 10)))
    offset = int(params.get('offset', params.get('o', 0)))

    def return_v(v) :
        msg = ''
        msg += '%s\n' % v['id']
        msg += '%s\n' % v['title']
        msg += ''
        return msg

    offset_j = 0
    count = 0
    for v in r :
        if offset and offset_j < offset :
            offset_j += 1
            continue

        count += 1
        offset_j += 1

        ret_msg += return_v(v)

        if limit and count >= limit :
            break
    return ret_msg


def read(read_len=None) :
    msg = 'dev/v2ex 简单封装对v2ex.com的访问\n'
    msg += '最热的帖子\n'
    msg += return_contents('topics/hot.json')
    return msg
