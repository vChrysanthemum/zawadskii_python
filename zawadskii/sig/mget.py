# -*- coding: UTF-8 -*- 

import g
from sig import _util
from sig import cat
from sig import ls

def default(netnode) :
    if 'parsed' == netnode.sig_stage :
        del netnode.sig.argv[0]
        if 'cat' == netnode.sig.argv[0] :
            cat.default(netnode)
        elif 'ls' == netnode.sig.argv[0] :
            ls.default(netnode)
        else :
            g.evloop.add_reply('-command not found', netnode)

        g.evloop.make_netnode_ready(netnode)
        return 0



