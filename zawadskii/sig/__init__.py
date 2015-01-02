#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import g
import user
import status
import foperate
import ls
import cat
import mget
import yo

def init() :
    g.evloop.append_sig_mapper('user.login', user.login)
    g.evloop.append_sig_mapper('status.check', status.check)
    g.evloop.append_sig_mapper('foperate.process', foperate.upload)
    g.evloop.append_sig_mapper('ls', ls.default)
    g.evloop.append_sig_mapper('cat', cat.default)
    g.evloop.append_sig_mapper('MGET', mget.default)#for php-redis
    g.evloop.append_sig_mapper('yo', yo.default)
