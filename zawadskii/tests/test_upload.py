#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import socket  
import time 
import os


filepath = './'
filename = 'hei.jpg'
file_object = open(filepath + filename, 'rb')
chunkSize = 500 
chunkNum = int(os.path.getsize(filename) / chunkSize)
if os.path.getsize(filename) % chunkSize != 0 :
    chunkNum += 1




address = ('127.0.0.1', 8888)  
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  
s.connect(address)  


cmdName = 'f.upload'
# f.upload $filename $fileBlockNum $file1 ... $file2 ...
datas = "*%d\r\n$%d\r\n%s\r\n$%d\r\n%s\r\n$%d\r\n%d\r\n" % (
        chunkNum + 3,
        len(cmdName), cmdName,
        len(filename), filename,
        len(str(chunkNum)), chunkNum
        )
print datas
s.send(datas)

ret = s.recv(10)
if '+' != ret :
    print 'upload error'
    quit()

print 'start upload'

try:
    while True:
        chunk = file_object.read(chunkSize)

        if not chunk :
            break

        while True:
            datas = "$%d\r\n%s\r\n" % (len(chunk), chunk)
            s.send(datas)
            ret = s.recv(10)
            if '+' == ret :
                break

        if not chunk:
            break
finally:
    file_object.close()

print 'upload success'


s.close()  
