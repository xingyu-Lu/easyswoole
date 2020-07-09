-- 2020-07-09 添加客服用户对话表 --
create table if not exists `blog_cs_message` (
    `id` int(11) unsigned not null AUTO_INCREMENT,
    `msg` text not null comment '对话内容',
    `type` tinyint(1) unsigned not null default 0 comment '0:用户；1:客服',
    `user_id` int(11) unsigned not null default 0 comment '用户ID(type为0时是用户ID(暂用用户fd代替)，type为1时是客服ID)',
    `create_time` int(11) unsigned not null default 0,
    primary key (`id`)
) engine=innodb auto_increment=1;