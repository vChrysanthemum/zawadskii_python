# -*- coding: UTF-8 -*- 

import g
import dev.v2ex
from sig import _util

dev_character_special_mapper = {
        'v2ex' : dev.v2ex
        }

def default(netnode) :
    '''
    argv[0] is cat
    argv[1] is nodepath
    '''
    if 'parsed' == netnode.sig_stage :

        if len(netnode.sig.argv) < 2 :
            g.evloop.add_reply('-error path', netnode)
            g.evloop.make_netnode_ready(netnode)
            return 0

        node = _util.get_node_by_path(netnode.sig.argv[1])

        if not node :
            g.evloop.add_reply('-file not found', netnode)
            g.evloop.make_netnode_ready(netnode)
            return 0


        if 'text' == node['ntype'] :
            text = g.db_conn.select(
                    {'node_id':node['node_id']}, 
                    'text_id,node_id,content',
                    'b_text').fetchone()
            g.evloop.add_reply_array([text['content']], netnode)


        elif 'character_special' == node['ntype'] :
            character_special = g.db_conn.select(
                    {'node_id':node['node_id']}, 
                    'character_special_id,node_id,name',
                    'b_character_special').fetchone();

            if not character_special :
                g.evloop.add_reply('-dev not found', netnode)
                g.evloop.make_netnode_ready(netnode)
                return 0

            ret = dev_character_special_mapper[character_special['name']].read()
            g.evloop.add_reply_array([ret], netnode)


        else :
            g.evloop.add_reply('-not readable', netnode)

        g.evloop.make_netnode_ready(netnode)
