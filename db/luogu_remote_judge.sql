ALTER TABLE `problems` ADD `type` varchar(20) NOT NULL DEFAULT 'local';
ALTER TABLE `problems` ADD KEY `type` (`type`);
insert into judger_info (judger_name, password) values ('luogu_remote_judger', '_judger_password_');
