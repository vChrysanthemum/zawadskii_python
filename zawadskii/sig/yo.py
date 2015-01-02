# -*- coding: UTF-8 -*- 

import g
import logging

logger = logging.getLogger('daemon_server')

def default(netnode) :
    if 'parsed' == netnode.sig_stage :
        logger.info('%s:%d send yo to me' % (netnode.addr[0], netnode.addr[1]))
        g.evloop.add_reply('+yo', netnode)
        g.evloop.make_netnode_ready(netnode)
