#!/usr/bin/python
# -*- coding: utf-8 -*-

from optparse import OptionParser 
from termcolor import colored

import sys
import os 
import time
import getopt
import thread
import logging
import logging.handlers
import ConfigParser
import readline

import ev
import tev
import sig
import g
import util
import db 

def people_yo_me(netnode) :
    if 'status' == netnode.sig.stype :
        g.evloop.free_netnode(netnode)
        return

reload(sys) 
sys.setdefaultencoding('utf8')

def access_command() :
    while True :
        cmd = raw_input('>>> ')
        cmd = cmd.split(' ')

        if 'quit' == cmd[0] :
            g.evloop.set_quit()
            #print colored('byby', 'blue')
            sys.exit(0)

        elif 'status' == cmd[0] :
            status = g.evloop.get_status()
            ret = (
                    "server_count: {}\n"
                    "client_count: {}\n"
                    "start_at:     {}"
                    ).format(
                            status['server_count'],
                            status['client_count'],
                            time.strftime('%Y-%m-%d %H:%M:%S', time.gmtime(status['start_at'])))
            print colored(ret, 'green')

        elif 'log' == cmd[0] :
            print colored(''.join(g.server_log.lines), 'green')

        elif 'yo' == cmd[0] :
            if len(cmd) < 3 :
                print colored("you should input remote machine's host and port", 'red')
            _netnode = g.evloop.connect_netnode(cmd[1], int(cmd[2]))
            if not _netnode :
                print colored("connect error", 'red')
            g.evloop.add_reply_array(['yo'], _netnode)
            _netnode.sig.exc_func = people_yo_me
            print colored('send yo', 'green')

        else :
            print colored('Command','yellow'), colored(cmd, 'green'), colored('not found','yellow')


def main() :

    #init logger
    logger = logging.getLogger('daemon_server')
    logger.setLevel(logging.DEBUG)
    formatter = logging.Formatter(                                                                                                                            
    "[%(asctime)s.%(msecs)d][%(levelname)s]%(filename)s:%(lineno)s "
    "%(message)s",
    "%Y%m%d-%H:%M:%S")
    
    g.server_log = util.LogHandler()
    console_handler = logging.StreamHandler(g.server_log)
    #console_handler = logging.StreamHandler()
    #console_handler = logging.NullHandler()
    console_handler.setLevel(logging.DEBUG)
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)


    #init config
    g.basedir = os.path.split(os.path.realpath(__file__))[0]

    parser = OptionParser(
            usage="chrysanthemum"
            )

    parser.add_option(
            "-c",
            "--config",
            dest="config_path",
            default='{0}/default.conf'.format(g.basedir),
            help="config_path")

    parser.add_option(
            "-p",
            "--port",
            dest="port",
            default=None,
            help="listen port")
    (options, args) = parser.parse_args() 

    _config = ConfigParser.ConfigParser()
    _config.read(options.config_path)


    g.config['server'] = {}
    if options.port is None :
        listen_port = _config.getint('server', 'listen_port')
    else :
        listen_port= int(options.port)
    g.config['server']['listen_port']       = listen_port
    g.config['server']['max_clients']       = _config.getint('server', 'max_clients')
    
    g.evloop = ev.EVLoop.instance()

    sig.init()

    g.db_conn = db.Conn()
    g.db_conn.host      = _config.get('mysql', 'host')
    g.db_conn.user      = _config.get('mysql', 'user')
    g.db_conn.passwd    = _config.get('mysql', 'passwd')
    g.db_conn.database  = _config.get('mysql', 'database')
    g.db_conn.port      = _config.getint('mysql', 'port')
    g.db_conn.charset   = _config.get('mysql', 'charset')
    g.db_conn.connect()
    logger.info('connect mysql success')
    
    #init time event
    '''
    timeev           = g.TimeEv()
    timeev.interval  = 1
    timeev.limits    = 5
    timeev.exc_func  = tev.test.test
    g.time_events.append(timeev) 
    '''

    #start listen
    thread.start_new_thread(ev.main, ())
    
    access_command()
    #thread.start_new_thread(access_command, ())


if __name__ == "__main__":
    main()
