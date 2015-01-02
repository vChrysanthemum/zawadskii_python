#!/usr/bin/python
# -*- coding: utf-8 -*-

import common
import logging
logger = logging.getLogger("")
import os


task_id = 12
conn = common.Conn()
conn.connect()
send_array = ['user.login', 'hi']

conn.send_array(send_array)
logging.debug('send success')
conn.read()

print conn.read_argv
