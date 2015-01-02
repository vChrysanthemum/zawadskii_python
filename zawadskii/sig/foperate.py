#!/usr/bin/env python
# -*- coding: UTF-8 -*- 

import sys
import g
import ev
import logging
import os
import time
logger = logging.getLogger('daemon_server')

upload_stage		= 'preupload'	#preupload uploading uploadfinish 
upload_chunknum		= 0				#chunks need load
upload_readchunknum = 0				#chunks loaded
upload_tmpfile		= None

# upload file
# netnode.cmd.argv : f.upload $filename $fileBlockNum $file1 ... $file2 ...
def upload(netnode) :
    global upload_stage, upload_chunknum, upload_readchunknum, upload_tmpfile

    if 'preupload' == upload_stage :
        if netnode.cmd.parsed_args >= 3 :
            upload_tmpfile = open(('tmpdata/{0}_{1}_{2}'.format(netnode.fileno, time.time(), netnode.cmd.argv[1])), 'w')

            upload_stage	= 'uploading'
            upload_chunknum = netnode.cmd.argv[2]
        g.evloop.add_reply('+', netnode)

    elif 'uploading' == upload_stage and (netnode.cmd.parsed_args - 3) > upload_readchunknum :
        """if there enough data ( (netnode.cmd.parsed_args - 3) > upload_readchunknum )"""
        chunk = netnode.cmd.argv[3 + upload_readchunknum]
        upload_tmpfile.write(chunk)
        netnode.cmd.argv[3 + upload_readchunknum] = None

        upload_readchunknum += 1
        g.evloop.add_reply('+', netnode)


    elif 'parsed' == netnode.cmd_stage :
        chunk = netnode.cmd.argv[3 + upload_readchunknum]
        upload_tmpfile.write(chunk)
        g.evloop.add_reply('+', netnode)

        g.evloop.make_netnode_ready(netnode)


    return 0

