#!/usr/bin/python
# -*- coding: utf-8 -*-

import socket
host = 'baidu.com'
host = 'v2ex.com'

messge_lines = [
"GET /index.xml HTTP/1.1",
"Host: %s" % host,
"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36",
"Accept-Language: zh-CN,zh;q=0.8",
"\r\n"
]
messge = '\r\n'.join(messge_lines)
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.connect((host, 80))
s.send(messge)
while True :
    ret = s.recv(1024)
    print ret
    if not ret :
        break

    quit()
