#!/usr/bin/env python
# -*- coding: UTF-8 -*-
'''process net & time event'''

import sig 
import time 
import g
import logging
import socket 
import select, errno

logger = logging.getLogger('daemon_server')



class NetNode(object) :
    def __init__(self) :
        self.socket_fd     = None
        self.addr          = None
        self.fileno        = 0
        self.write_buf     = ''
        self.read_buf      = ''
        self.read_argv_pos = -1        # position of parsing  Argv，-1 mean finished
        self.read_argv_len = -1        #length of Argv

        self.sig_stage    = 'prepare'  #prepare/parse/parsed 
        self.sig          = None       #Sig

        self.flag         = {}         #key : True  close_after_write


class Sig(object) :
    def __init__(self) :
        self.exc_func    = None
        self.args        = 0
        self.argv        = []
        self.stype       = 'array'      #array or status
        self.parsed_args = 0


class TimeEv(object):
    def __init__(self) :
        self.interval    = 1            #exec time event each {$self.interval}
        self.wait_time   = 0            #wait how many times

        self.exc_count   = 0
        self.exc_func    = None
        self.limits      = 10 


class _EPoll(object):
    """An epoll-based event loop using our C module for Python 2.5 systems"""
    _EPOLL_CTL_ADD = 1
    _EPOLL_CTL_DEL = 2
    _EPOLL_CTL_MOD = 3

    def __init__(self):
        self._epoll_fd = epoll.epoll_create()

    def fileno(self):
        return self._epoll_fd

    def register(self, fd, events):
        epoll.epoll_ctl(self._epoll_fd, self._EPOLL_CTL_ADD, fd, events)

    def modify(self, fd, events):
        epoll.epoll_ctl(self._epoll_fd, self._EPOLL_CTL_MOD, fd, events)

    def unregister(self, fd):
        epoll.epoll_ctl(self._epoll_fd, self._EPOLL_CTL_DEL, fd, 0)

    def poll(self, timeout):
        return epoll.epoll_wait(self._epoll_fd, int(timeout * 1000))


class _KQueue(object):
    """A kqueue-based event loop for BSD/Mac systems."""
    def __init__(self):
        self._kqueue = select.kqueue()
        self._active = {}

    def fileno(self):
        return self._kqueue.fileno()

    def register(self, fd, events):
        self._control(fd, events, select.KQ_EV_ADD)
        self._active[fd] = events

    def modify(self, fd, events):
        self.unregister(fd)
        self.register(fd, events)

    def unregister(self, fd):
        events = self._active.pop(fd)
        self._control(fd, events, select.KQ_EV_DELETE)

    def _control(self, fd, events, flags):
        kevents = []
        if events & EVLoop.WRITE:
            kevents.append(select.kevent(
                    fd, filter=select.KQ_FILTER_WRITE, flags=flags))
        if events & EVLoop.READ or not kevents:
            # Always read when there is not a write
            kevents.append(select.kevent(
                    fd, filter=select.KQ_FILTER_READ, flags=flags))
        # Even though control() takes a list, it seems to return EINVAL
        # on Mac OS X (10.6) when there is more than one event in the list.
        for kevent in kevents:
            self._kqueue.control([kevent], 0)

    def poll(self, timeout):
        kevents = self._kqueue.control(None, 1000, timeout)
        events = {}
        for kevent in kevents:
            fd = kevent.ident
            if kevent.filter == select.KQ_FILTER_READ:
                events[fd] = events.get(fd, 0) | EVLoop.READ
            if kevent.filter == select.KQ_FILTER_WRITE:
                if kevent.flags & select.KQ_EV_EOF:
                    # If an asynchronous connection is refused, kqueue
                    # returns a write event with the EOF flag set.
                    # Turn this into an error for consistency with the
                    # other EVLoop implementations.
                    # Note that for read events, EOF may be returned before
                    # all data has been consumed from the socket buffer,
                    # so we only check for EOF on write events.
                    events[fd] = EVLoop.ERROR
                else:
                    events[fd] = events.get(fd, 0) | EVLoop.WRITE
            if kevent.flags & select.KQ_EV_ERROR:
                events[fd] = events.get(fd, 0) | EVLoop.ERROR
        return events.items()


class _Select(object):
    """A simple, select()-based EVLoop implementation for non-Linux systems"""
    def __init__(self):
        self.read_fds = set()
        self.write_fds = set()
        self.error_fds = set()
        self.fd_sets = (self.read_fds, self.write_fds, self.error_fds)

    def register(self, fd, events):
        if events & EVLoop.READ: self.read_fds.add(fd)
        if events & EVLoop.WRITE: self.write_fds.add(fd)
        if events & EVLoop.ERROR:
            self.error_fds.add(fd)
            # Closed connections are reported as errors by epoll and kqueue,
            # but as zero-byte reads by select, so when errors are requested
            # we need to listen for both read and error.
            self.read_fds.add(fd)

    def modify(self, fd, events):
        self.unregister(fd)
        self.register(fd, events)

    def unregister(self, fd):
        self.read_fds.discard(fd)
        self.write_fds.discard(fd)
        self.error_fds.discard(fd)

    def poll(self, timeout):
        readable, writeable, errors = select.select(
            self.read_fds, self.write_fds, self.error_fds, timeout)
        events = {}
        for fd in readable:
            events[fd] = events.get(fd, 0) | EVLoop.READ
        for fd in writeable:
            events[fd] = events.get(fd, 0) | EVLoop.WRITE
        for fd in errors:
            events[fd] = events.get(fd, 0) | EVLoop.ERROR
        return events.items()


class EVLoop(object) :
    # Constants from the epoll module
    _EPOLLIN = 0x001
    _EPOLLPRI = 0x002
    _EPOLLOUT = 0x004
    _EPOLLERR = 0x008
    _EPOLLHUP = 0x010
    _EPOLLRDHUP = 0x2000
    _EPOLLONESHOT = (1 << 30) 
    _EPOLLET = (1 << 31) 

    # Our events map exactly to the epoll events
    NONE = 0 
    READ = _EPOLLIN
    WRITE = _EPOLLOUT
    ERROR = _EPOLLERR | _EPOLLHUP | _EPOLLRDHUP

    def __init__(self) :
        self._poll = None
        self.netnodes_socket_fd = []
        self.netnodes_poll_fd = None
        self.netnodes    = {}
        self.sig_mapper  = {}
        self.time_events = []
        self.should_quit = False
        self.start_at    = time.time()
        self.regist_new_fileno = self._normal_regist_new_fileno

        if hasattr(select, "epoll"):
            # Python 2.6+ on Linux
            self._poll = select.epoll
        elif hasattr(select, "kqueue"):
            # Python 2.6+ on BSD or Mac
            self._poll = _KQueue 
            self.regist_new_fileno = self._kqueue_regist_new_fileno
        else:
            try:
                # Linux systems with our C module installed
                import epoll
                self._poll = _EPoll
            except:
                # All other systems
                import sys
                if "linux" in sys.platform:
                    logging.warning("epoll module not found; using select()")
                self._poll = _Select

        self.netnodes_poll_fd = self._poll()

    def _kqueue_regist_new_fileno(self, socket_fd) :
        self.register(socket_fd.fileno(), EVLoop._EPOLLET)

    def _normal_regist_new_fileno(self, socket_fd) :
        self.register(socket_fd.fileno(), EVLoop.READ | EVLoop.WRITE | EVLoop._EPOLLET)


    @classmethod
    def instance(cls) :
        if not hasattr(cls, '_instance') :
            cls._instance = cls()
        return cls._instance


    def exec_sig(self, netnode) :
        try :
            return netnode.sig.exc_func(netnode)
        except Exception as e :
            logger.info(e)
        return 0



    def get_status(self) :
        return {
                'netnodes_count' : len(self.netnodes),
                'start_at'       : self.start_at
                }

    def set_quit(self) :
        self.should_quit = True

    def append_time_event(self, timeev) :
        self.time_events.append(timeev)

    def append_sig_mapper(self, name, func) :
        self.sig_mapper[ name ] = func

    def register(self, fileno, etype) :
        self.netnodes_poll_fd.register(fileno, etype)

    def unregister(self, fileno) :
        self.netnodes_poll_fd.unregister(fileno)

    def bind_netnode(self, netnode) :
        self.netnodes[ netnode.fileno ] = netnode

    def unbind_netnode(self, netnode) :
        del self.netnodes[ netnode.fileno ]

    def create_netnode(self, socket_fd, addr) :
        """create a netnode,and append to netnodes[ socket_fd.fileno() ]"""
        netnode = NetNode()
    
        socket_fd.setblocking(0)
    
        self.regist_new_fileno(socket_fd)
        netnode.fileno    = socket_fd.fileno()
        netnode.socket_fd = socket_fd
        netnode.addr      = addr  
    
        self.bind_netnode(netnode)
    
        logger.info("create netnode %s, %d, fd = %d" % (addr[0], addr[1], socket_fd.fileno()))
        return netnode
    
    
    def connect_netnode(self, host, port) :
        """connect a netnode"""
        netnode = NetNode()
        netnode.addr = [host, port]
        netnode.socket_fd = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        netnode.socket_fd.connect( (netnode.addr[0], netnode.addr[1]) )
        netnode.fileno = netnode.socket_fd.fileno()
        netnode.socket_fd.setblocking(0)

        netnode.sig = Sig()
    
        logger.info("connect to %s, %d" % (netnode.addr[0], netnode.addr[1]))
    
        self.regist_new_fileno(netnode.socket_fd)
    
        self.bind_netnode(netnode)
    
        return netnode


    def free_netnode(self, netnode) :
        """close and free the netnode"""
        self.unregister(netnode.fileno)
        netnode.socket_fd.close()
        self.unbind_netnode(netnode)
        logger.info("free netnode %s, %d, fd = %d" % (netnode.addr[0], netnode.addr[1], netnode.fileno))
        return True 
   

    def make_netnode_ready(self, netnode) :
        """make netnode ready to receive new sig"""
        netnode.read_buf  = ''
        netnode.read_argv_pos = -1
        netnode.read_argv_len = -1
    
        netnode.sig_stage = 'prepare'
    
        if netnode.sig is not None :
            netnode.sig.exc_func = None
            del netnode.sig.argv 
            del netnode.sig 
            netnode.sig = None
        return 0
     
    def add_reply_array(self, datas, netnode) :
        content = '*{0}\r\n'.format(len(datas)) 
        for v in datas :
            v = str(v)
            content = '{0}${1}\r\n{2}\r\n'.format(content, len(v), v)
        return self.add_reply(content, netnode)
    
    def add_reply(self, datas, netnode) :
        """just add data to write_buf"""
        if datas[0] in ['-', '+'] :
            datas = '%s\r\n' % datas
        netnode.write_buf += datas
        self.netnodes_poll_fd.modify(netnode.fileno, EVLoop._EPOLLET | EVLoop.WRITE)
        return 0
    
    
    def parse_sig_num(self, arg) :
        """parse the string to number"""
        try :
    
            if len(arg) > 12 :
                return None
            return int(arg)
        except ValueError :
            return None
    
    
    
    def parse_array(self, netnode) :
        """
        parse the array by sig string
         parsing process:
                  parse_array                    /parse_array                      /parse_array
                  argv[x](start)    ------------ /argv[x](unfinished) ------------ /argv[x](finished)
                  read_argv_pos : -1             /read_argv_pos : y > 0            /read_argv_pos : -1
        
         params instance    netnode                
                            netnode.read_buf    need to parse
                                                        Format: $arg1 \r\n argv1 \r\n $arg2 \r\n argv2 \r\n (ignore white space)
                            netnode.args        count of sig
         return int         ret                         retrun 0 while true
        """
        argvJPos    = 0
    
        #if there are sig parsing
        #getting argv 
        if netnode.read_argv_pos >= 0 :
            read_bufLen = len(netnode.read_buf)
    
            #not finish yet
            if read_bufLen + netnode.read_argv_pos < netnode.read_argv_len :
                netnode.read_argv_pos += read_bufLen
                netnode.sig.argv[ len(netnode.sig.argv)-1 ] += netnode.read_buf
                netnode.read_buf = ''
                return 0
            else :
            #finish 
                netnode.sig.argv[ len(netnode.sig.argv)-1 ] += netnode.read_buf[: (netnode.read_argv_len - netnode.read_argv_pos)]
                #skip \r\n
                netnode.read_buf = netnode.read_buf[(netnode.read_argv_len - netnode.read_argv_pos + 2) :]
                netnode.read_argv_pos = -1
                netnode.sig.parsed_args += 1
    
    
        argvJ        = len(netnode.sig.argv)
    
        try :
            while argvJ < netnode.sig.args:
                #start parse a sig
                argvJPos    = 0
    
                #Step1: get the count of sig , if this step error then throw error
                #skip $
                argvJPos    += netnode.read_buf[argvJPos:].index('$') + 1
                #if there are no  \r\n  them throw error
                argcLen     = netnode.read_buf[argvJPos:].index('\r\n')
                argc        = self.parse_sig_num( netnode.read_buf[argvJPos : argvJPos + argcLen] )
                if None == argc :
                    return 0
                #skip \r\n
                argvJPos += (argcLen + 2)
    
                #argv length
                netnode.read_argv_len = argc
                netnode.read_argv_pos = 0
                #Step1: finished
    
    
                #Step2:  parse argv
                argvLen = len(netnode.read_buf[argvJPos:])
    
                #if the argv not long enough then stop parsing , and return
                if argvLen < argc :
                    netnode.read_argv_pos += argvLen
                    netnode.sig.argv.append( netnode.read_buf[argvJPos :] )
                    netnode.read_buf = ''
                    return 0
    
                netnode.sig.argv.append( netnode.read_buf[argvJPos : argvJPos + argc] )
                #skip \r\n
                argvJPos += argc + 2
    
                netnode.read_argv_pos = -1
    
                #Step3: finished
                netnode.sig.parsed_args += 1
                netnode.read_buf = netnode.read_buf[argvJPos:]
                argvJ += 1
    
        except :
            return 0
    
        #finished
        netnode.sig_stage = 'parsed'
    
        return 0
     
    
    
    def parse_read_buf_to_sig(self, netnode) :
        """
         netnode.sig_stage
        
            ----------------------------------------------
           |                                              |
         prepare --------------- parse --------------- parsed --------------- prepare
        
         protocol  *args \r\n $arg1 \r\n argv1 \r\n ... (ignore white space)
                   args     count of array
                   arg1     first sig's length
                   argv1    first sig
                    .....
        """
        #initial state
        if 'prepare' == netnode.sig_stage :
            netnode.sig_stage = 'parse'
    
            argvPos = 0
    
            if '+' == netnode.read_buf[argvPos] or '-' == netnode.read_buf[argvPos] :
                if not netnode.sig or not netnode.sig.exc_func :
                    self.add_reply('-unknown protocol', netnode)
                    netnode.flag['close_after_write'] = True 
                    return 0
                else :
                    netnode.sig.stype = 'status'
                    self.exec_sig(netnode)
                    return 0
    
    
    
            if '*' != netnode.read_buf[argvPos]:
                self.add_reply('-unknown protocol', netnode)
                netnode.flag['close_after_write'] = True 
                return 0
    
            try : 
                argsIndex    = netnode.read_buf.index('\r\n') 
                args        = int(netnode.read_buf[ argvPos+1 : argsIndex ])
                #skip \r\n
                argvPos        += argsIndex + 2
            except :
                self.add_reply('-args error', netnode)
                netnode.flag['close_after_write'] = True 
                return 0
    
            netnode.read_buf = netnode.read_buf[argvPos:]
    
            if not netnode.sig :
                netnode.sig = Sig()
                netnode.sig.stype = 'array'
            netnode.sig.args = args 

            if 0 == self.parse_array(netnode) and \
                len(netnode.sig.argv) > 0 and \
                self.sig_mapper.has_key( netnode.sig.argv[0] ):
    
                if not netnode.sig.exc_func :
                    netnode.sig.exc_func = self.sig_mapper[ netnode.sig.argv[0] ]

                errno = self.exec_sig(netnode)
            else :
                self.add_reply('-invalid sig', netnode)
                self.make_netnode_ready(netnode)
                return 0
            
        #parsing data
        elif 'parse' == netnode.sig_stage :
            logger.info(netnode.sig.argv[0])
            self.parse_array(netnode)
            self.exec_sig(netnode)
        
    
        #finished 
        elif 'parsed' == netnode.sig_stage :
            logger.info(netnode.sig.argv[0])
            self.exec_sig(netnode)
            
    
        return 0
     
    
    def process_timeev(self, timeev) :
        """time event"""
        timeev.wait_time    += 1
    
        if timeev.limits > 0 and timeev.exc_count >= timeev.limits :
            return 0
    
        if timeev.wait_time < timeev.interval :
            return 0
    
        if timeev.wait_time >= timeev.interval :
            timeev.wait_time = 0
    
        timeev.exc_func()
        timeev.exc_count        += 1
        return 0
    
    
    def process_file_readev(self, netnode) :
        """read event"""
        read_buf = ''
        tmp_readbuf = None
    
        while True :
            try:
                tmp_readbuf = netnode.socket_fd.recv(1024)
    
                if not tmp_readbuf or len(tmp_readbuf) == 0 :
                    logger.info('read buf error ip:{} port:{} fd:{}'.format(netnode.addr[0], netnode.addr[1], netnode.fileno))
                    self.free_netnode(netnode)
                    return 0
                read_buf += tmp_readbuf
    
    
            except socket.error, msg :
                if msg.errno == errno.EAGAIN :
                    break
                else :
                    logger.info('read buf error {}'.format(msg))
                    self.free_netnode(netnode)
                    return 0
    
    
        if len(read_buf) > 0 :
            netnode.read_buf += read_buf
            self.parse_read_buf_to_sig(netnode)
    
        return 0
    
    
    
    def process_file_writeev(self, netnode) :
        """write event"""
        if 0 == len(netnode.write_buf) :
            return 0
    
        #write all
        try :
            write_buflen = len(netnode.write_buf)
            while True :
                writelen = netnode.socket_fd.send(netnode.write_buf)
                write_buflen -= writelen
    
                netnode.write_buf = netnode.write_buf[ writelen: ]
    
                if 0 == write_buflen or 0 == writelen :
                    break
    
    
            if 0 == write_buflen :
                self.netnodes_poll_fd.modify(netnode.fileno, EVLoop._EPOLLET | EVLoop.READ)
    
                if netnode.flag.has_key('close_after_write') :
                    self.free_netnode(netnode)
                    return 0
    
    
        except socket.error, msg :
            if msg.errno == errno.EAGAIN :
                pass
            else :
                logger.info('write buf error {}'.format(msg))
                self.free_netnode(netnode)
                return 0
    
        return 0

    def start_listen(self) :
        """init no block server"""
        try :
            self.netnodes_socket_fd = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        except socket.error, msg :
            logger.info("create socket failed")
            quit()
        
        try:
            self.netnodes_socket_fd.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        except socket.error, msg :
            logger.info("set socket failed")
            quit()
        
        try:
            self.netnodes_socket_fd.bind(('', g.config['server']['listen_port']))
        except socket.error, msg :
            logger.info("bind port failed")
            quit()
        
        try:
            self.netnodes_socket_fd.listen(g.config['server']['max_clients'])
            self.netnodes_socket_fd.setblocking(0)
        except socket.error, msg :
            logger.info(msg)
            quit()
    
        try:
            self.regist_new_fileno(self.netnodes_socket_fd)
        except select.error, msg :
            logger.info(msg)
            quit()

        logger.info('listen at {}'.format(g.config['server']['listen_port']))
        return 0
    
    
    def main_loop(self) :

        while True :
            if self.should_quit :
                quit()
    
            #time events
            for v in self.time_events :
                self.process_timeev(v)
        
            epoll_list = self.netnodes_poll_fd.poll(1)
            for fd, events in epoll_list :
                #active
                if fd == self.netnodes_socket_fd.fileno() :
                    socket_fd, addr = self.netnodes_socket_fd.accept()
                    socket_fd.setblocking(0)
                    netnode = self.create_netnode(socket_fd, addr)
        
                #readable
                elif EVLoop.READ & events :
                    self.process_file_readev(self.netnodes[fd])
        
                #writeable
                elif EVLoop.WRITE & events :
                    self.process_file_writeev(self.netnodes[fd])
        
                #HUP event
                elif EVLoop.ERROR & events :
                    logger.debug('hup event')
                    self.free_netnode(self.netnodes[fd])

def main() :
    g.evloop.start_listen()

    g.evloop.main_loop() 

