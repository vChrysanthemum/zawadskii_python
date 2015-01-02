#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import curses
dirs = dir(curses)
for d in dirs :
    if -1 == d.find('KEY_') :
        continue 

    tmp = eval('curses.'+d)
    if tmp == 10 :
        print '!!!!!!!!!{}:{}'.format(tmp, d)
