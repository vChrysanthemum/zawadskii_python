#!/usr/bin/env python
# -*- coding: UTF-8 -*- 

import g
import ev
import logging
logger = logging.getLogger('daemon_server')

def check(netnode) :
    if 'parsed' == netnode.sig_stage :
        g.evloop.add_reply('+', netnode)

        g.evloop.make_socket_instance_ready(netnode)

    return 0
