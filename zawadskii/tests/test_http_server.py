#!/usr/bin/python
# -*- coding: utf-8 -*-

import socket
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.bind(('0.0.0.0', 80))
s.listen(100)
fd, addr = s.accept()
print fd.recv(1024)
fd.send('HTTP/1.1 200 OK')
