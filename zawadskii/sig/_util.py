# -*- coding: UTF-8 -*- 

import db
import g


def _match_node(need_match_path, parent_id, current_level=0) :
    node = g.db_conn.select(
            {'name':need_match_path[current_level], 'parent_id':parent_id},
            'node_id,name,parent_id,ntype',
            'b_node', None).fetchone()

    if not node :
        return None

    if  current_level+1 >= len(need_match_path):
        return node

    return _match_node(need_match_path, node['node_id'], current_level+1)


def get_node_by_path(path) :
    if path[0] != '/' :
        path = '/%s' % path

    path = path.split('/')
    real_path = []
    for v in path :
        if v == '' :
            continue
        real_path.append(v)
    
    return _match_node(real_path, 0)
