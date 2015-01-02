# -*- coding: UTF-8 -*- 

import g
from sig import _util

def default(netnode) :
    '''
    argv[0] is ls
    argv[1] is nodepath
    '''
    if 'parsed' == netnode.sig_stage :
        if len(netnode.sig.argv) < 2 :
            g.evloop.add_reply('-error path', netnode)
            g.evloop.make_netnode_ready(netnode)
            return 0

        datas = []
        if '/' == netnode.sig.argv[1] :
            children_nodes = g.db_conn.execute(
                    'select '
                    'node_id,name,parent_id,ntype '
                    'from b_node where parent_id=0').fetchall()
            for v in children_nodes :
                datas.append(v['name'])

        else :
            node = _util.get_node_by_path(netnode.sig.argv[1])

            if node :
                children_nodes = g.db_conn.execute(
                        'select '
                        'node_id,name,parent_id,ntype '
                        'from b_node where parent_id={}'.format(node['node_id'])).fetchall()
                for v in children_nodes :
                    datas.append(v['name'])

        g.evloop.add_reply_array(datas, netnode)

        g.evloop.make_netnode_ready(netnode)
        return 0
