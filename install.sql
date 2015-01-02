drop table if exists b_node;
create table b_node (
    node_id int,
    name varchar(64),
    parent_id int not null default 0,
    ntype enum('directory', 'text', 'character_special', 'bin'),
    primary key(node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert into b_node(node_id, name, parent_id, ntype) values(1,'blog',0,'directory');
insert into b_node(node_id, name, parent_id, ntype) values(2,'o',1,'directory');
insert into b_node(node_id, name, parent_id, ntype) values(3,'about',2,'text');
insert into b_node(node_id, name, parent_id, ntype) values(4,'help',2,'text');
insert into b_node(node_id, name, parent_id, ntype) values(5,'dev',0,'directory');
insert into b_node(node_id, name, parent_id, ntype) values(6,'v2ex',5,'character_special');

drop table if exists b_text;
create table b_text (
    text_id int,
    node_id int,
    content varchar(5096),
    primary key(text_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert into b_text(text_id, node_id, content) values(1,3,
    '
    终端游戏 
    使用命令行来访问一些网站，或使用一些互联网服务； 
    还在设计、开发中
    命令行输入框在底部，输入 cat /blog/o/help 试试看？ 
    或者输入 cat /dev/v2ex 试试看？ ');
insert into b_text(text_id, node_id, content) values(2,4,
    '
    帮助文档

    暂时只支持两个命令
        cat:查看文件内容
        ls:列出目录下文件

    有三种文件类型
        directory:目录结构
        text:普通文本
        character_special:参考，字符设备文件，还在开发中...，试试 cat /dev/v2ex');


drop table if exists b_character_special;
create table b_character_special (
    character_special_id int,
    node_id int,
    name varchar(64),
    primary key(character_special_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert into b_character_special(character_special_id, name, node_id) values(1,'v2ex', 6);
