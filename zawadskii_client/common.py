#!/usr/bin/python
# -*- coding: utf-8 -*-

import socket
import logging
import logging.handlers

logger = logging.getLogger('')
logger.setLevel(logging.DEBUG)
formatter = logging.Formatter(                                                                                                                            
"[%(asctime)s.%(msecs)d][%(levelname)s]%(filename)s:%(lineno)s "
"%(message)s",
"%Y%m%d-%H:%M:%S")

console_handler = logging.StreamHandler()
console_handler.setLevel(logging.DEBUG)
console_handler.setFormatter(formatter)
logger.addHandler(console_handler)


class Conn(object) :
    def __init__(self) :
        self.conn = None
        self.address = ('127.0.0.1', 10000)
        self.read_buf = ''
        self.read_argv = []
        self.read_args = 0
        self.readed_args = 0
        self.read_pos = 0
        self.read_stat = 'prepare'


    def connect(self) :
        self.conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.conn.connect(self.address)


    def send_array(self, datas) :
        content = '*{}\r\n'.format(len(datas))
        for v in datas :
            v = str(v)
            content = '{0}${1}\r\n{2}\r\n'.format(content, len(v), v)
        content_len = len(content)
        writed = 0
        while True :
            writed += self.conn.send(content)
            if writed >= content_len :
                break

        return True


    def parse_num(self, arg) :
        try :
            if len(arg) > 12 :
                return None
            return int(arg)
        except ValueError :
            return None



    def parse_read_buf_to_array(self) :
        self.read_buf = '{}{}'.format(self.read_buf, self.conn.recv(2048))

        if '+' == self.read_buf[0] or '-' == self.read_buf[0] :
            self.read_type = 'status'
            self.read_args = 2
            self.read_argv.append(self.read_buf[0])
            self.read_argv.append(self.read_buf[1:])
            self.read_buf = ''
            return True
        else :
            self.read_type = 'array'

        while True :
            try :
                self.read_buf = '{}{}'.format(self.read_buf, self.conn.recv(2048))

                if '+' == self.read_buf[0] or '-' == self.read_buf[0] :
                    self.read_type = 'status'
                    return True

            except socket.error, msg :
                if msg.errno == errno.EAGAIN :
                    continue


            if 'prepare' == self.read_stat:
                if '*' != self.read_buf[0] :
                    logger.info(self.read_buf)
                    raise ValueError
                self.read_stat = 'parse'

                #skip *
                self.read_pos += 1

                indexj = self.read_buf.index('\r\n')
                self.read_args = self.parse_num( self.read_buf[ self.read_pos:indexj ] )

                if not self.read_args :
                    logger.info(self.read_buf)
                    raise ValueError

                #skip \r\n
                self.read_pos = indexj + 2



            if 'parse' == self.read_stat :
                while True :

                    try :
                        #+1 because skip $
                        indexj = self.read_buf.index('\r\n', self.read_pos+1)
                    except :
                        return False

                    argj = self.parse_num( self.read_buf[ self.read_pos+1:indexj ] )
                    #parse success
                    #skip $
                    self.read_pos += 1

                    #skip \r\n
                    self.read_pos = indexj + 2

                    self.read_argv.append( self.read_buf[ self.read_pos : self.read_pos+argj ] )

                    #skip argv \r\n
                    self.read_pos += argj + 2


                    self.readed_args += 1

                    if self.readed_args == self.read_args :
                        return True




    def read(self) :
        self.read_buf = ''
        self.read_argv = []
        self.read_args = 0
        self.readed_args = 0
        self.read_pos = 0
        self.read_stat = 'prepare'
        self.read_type = ''#status/array


        while True :
            ret = self.parse_read_buf_to_array()
            if ret :
                break
        return True
