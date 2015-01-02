#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import g
import sig.user
import logging

logger = logging.getLogger('daemon_server')

is_connect = False
server = None
def test() :
    global server, is_connect

    if not is_connect :
        is_connect =True
        server = ev.connect_server('127.0.0.1', 10000)

    server.sig = g.Sig()
    server.sig.exc_func = sig.user.info
    g.evloop.add_reply_array(['login'], server)
    logger.debug('tev test')

    #ev.free_server(server)
