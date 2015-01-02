#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import MySQLdb

class Conn(object) :
    def __init__(self) :
        self.host       = None
        self.user       = None
        self.passwd     = None
        self.port       = None
        self.database   = None
        self.charset    = None
        
        self.db         = None
        self.cur        = None 

        self.lastrowid  = 0

    def connect(self) :
        self.db = MySQLdb.connect(
                host    = self.host,
                user    = self.user,
                port    = self.port, 
                passwd  = self.passwd,
                db      = self.database,
                charset = self.charset) 

        self.db.autocommit(True)

        self.cur = self.db.cursor(MySQLdb.cursors.DictCursor)
        return self.cur



    def execute(self, sql) :
        try:
            self.db.ping()
        except Exception, e:
            self.connect()

        self.cur.execute(sql)
        return self.cur

    def create(self, data, tablename) :
        key_str  = []
        value_str= []
        for key in data :
            key_str.append( "`{__key}`".format( __key=key) )
            value_str.append( "{__value}".format( __value=self.db.escape(str(data[key]))) )

        sql = '''
        INSERT INTO `{__tablename}` 
        ({__key_str}) VALUES ({__value_str});
        '''.format(
                __tablename = tablename,
                __key_str   = ','.join(key_str),
                __value_str = ','.join(value_str)
                )
        return self.execute(sql).lastrowid

    def select(self, where, return_filed, tablename, limit=1, offset=0, orderby=None) :
        where_str = []
        for key in where :
            _value = ''
            if isinstance(where[key], list) :
                where_str.append( "`{__key}` IN ({__value})".format(__key=key, __value=self.db.escape(','.join(where[key]))) )
            else :
                where_str.append( "`{__key}` = {__value}".format(__key=key, __value=self.db.escape(str(where[key]))) )

        if len(where_str) > 0 :
            where_str = "WHERE {__where}".format( __where=' AND '.join(where_str) )
        else :
            where_str = ""

        limit_str = "LIMIT {} ".format(limit) if limit else ""
        offset_str= "OFFSET {} ".format(offset) if offset else ""


        sql = '''
        SELECT {__return_filed} FROM `{__tablename}` 
        {__where_str}
        {__orderby}
        {__limit} {__offset};
        '''.format(
                __return_filed    = return_filed,
                __tablename        = tablename,
                __where_str        = where_str,
                __limit            = limit_str,
                __offset        = offset_str,
                __orderby        = "ORDER BY {}".format(orderby) if orderby is not None else ""
                )

        return self.execute(sql)

    def update(self, where, data, tablename) :
        where_str = []
        for key in where :
            where_str.append( "`{__key}` = {__value}".format(__key=key, __value=self.db.escape(str(where[key]))) )

        if len(where_str) > 0 :
            where_str = "WHERE {__where}".format( __where=' AND '.join(where_str) )
        else :
            where_str = ""

        data_str = []
        for key in data :
            data_str.append( "`{__key}` = {__value}".format(__key=key, __value=self.db.escape(str(data[key]))) )

        if len(data_str) > 0 :
            sql = '''
            UPDATE {__tablename} 
            SET {__data_str} 
            {__where_str};
            '''.format(
                    __tablename = tablename,
                    __data_str    = ','.join(data_str),
                    __where_str = where_str
                    )
        return self.execute(sql)


    def delete(self, where, tablename) :
        where_str = []
        for key in where :
            where_str.append( "`{__key}` = {__value}".format(__key=key, __value=self.db.escape(str(where[key]))) )

        if len(where_str) > 0 :
            where_str = "WHERE {__where}".format( __where=' AND '.join(where_str) )
        else :
            where_str = ""


        sql = '''
        DELETE FROM `{__tablename}` 
        {__where_str};
        '''.format(
                __tablename        = tablename,
                __where_str        = where_str
                )

        return self.execute(sql)


    def select_count(self, where, column, tablename) :
        where_str = []
        for key in where :
            _value = ''
            if isinstance(where[key], list) :
                where_str.append( "`{__key}` IN ({__value})".format(__key=key, __value=self.db.escape(','.join(where[key]))) )
            else :
                where_str.append( "`{__key}` = {__value}".format(__key=key, __value=self.db.escape(str(where[key]))) )

        if len(where_str) > 0 :
            where_str = "WHERE {__where}".format( __where=' AND '.join(where_str) )
        else :
            where_str = ""

        limit_str = "LIMIT {} ".format(limit) if limit else ""
        offset_str= "OFFSET {} ".format(offset) if offset else ""


        sql = '''
        SELECT count({__column}) AS count FROM `{__tablename}` 
        {__where_str}
        '''.format(
                __column    = column,
                __tablename = tablename,
                __where_str    = where_str
                )
        ret = self.execute(sql).fetchone()
        return ret['count']
