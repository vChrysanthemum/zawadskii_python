# -*- coding: UTF-8 -*- 

import g
import ev
import logging
logger = logging.getLogger('daemon_server')

runCount = 0

def login(netnode) :
    global runCount
    runCount += 1

    logger.debug('sig user.login')
    if 'parsed' == netnode.sig_stage :
        g.evloop.add_reply(
                "+[%d]::%s\r\n" % (runCount, netnode.sig.argv[ len(netnode.sig.argv) - 1 ]),
                netnode)

    logger.info('user.login')
    g.evloop.make_netnode_ready(netnode)
    return 0

def info(netnode) :
    logger.debug('sig user.info')
    g.evloop.make_netnode_ready(netnode)
    return 0
