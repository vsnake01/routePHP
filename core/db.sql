delimiter $$

CREATE FUNCTION `uuid1`() RETURNS varchar(36) CHARSET utf8
return concat
    (
        substr(md5(uuid()),1,8), '-',
        substr(md5(uuid()),9,4), '-',
        substr(md5(uuid()),13,4), '-',
        substr(md5(uuid()),17,4), '-',
        substr(md5(uuid()),21,12)
    )$$

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL COMMENT 'Full name',
  `email` varchar(128) NOT NULL,
  `brand` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL COMMENT 'MD5 hash',
  `password_secure` varchar(32) DEFAULT NULL COMMENT 'Password for secure transactions',
  `phone` varchar(16) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL COMMENT 'ISO-3166-1 alpha-2 code',
  `lang` varchar(2) DEFAULT 'en',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(16) NOT NULL DEFAULT 'secure' COMMENT 'namespace for route',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`,`brand`),
  UNIQUE KEY `uid_UNIQUE` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8$$

CREATE
TRIGGER `users_uid`
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF (isnull(NEW.uid)) THEN
        SET NEW.uid = uuid1();
    END IF;
  END
$$

CREATE TABLE `users_info` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `key` varchar(128) NOT NULL,
  `val` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `KEY_UNIQ` (`user_id`,`key`),
  KEY `fk_users_info_1` (`user_id`),
  CONSTRAINT `fk_users_info_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All user related information'$$

CREATE TABLE `queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `task` varchar(32) NOT NULL COMMENT 'Task name',
  `params` text NOT NULL COMMENT 'Serialized params for task',
  `status` int(11) DEFAULT NULL COMMENT 'Status of task operations',
  `out_params` text COMMENT 'Result of task operations for some reasons',
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `skipped` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT NULL,
  `brand` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `TaskName` (`task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Handle all queues'$$

CREATE
TRIGGER `queue_update`
BEFORE UPDATE ON `queue`
FOR EACH ROW
begin
  set NEW.modified = now();
end
$$

CREATE TABLE `tokens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lang` varchar(2) NOT NULL DEFAULT 'en',
  `token_hash` varchar(32) NOT NULL COMMENT 'Token hash',
  `token_value` mediumtext COMMENT 'Token value.',
  `revision` bigint(20) DEFAULT NULL,
  `author` varchar(128) DEFAULT 'system',
  PRIMARY KEY (`id`),
  UNIQUE KEY `TOKEN_LANG_UQ` (`lang`,`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All translations'$$

CREATE
TRIGGER `token_insert`
AFTER INSERT ON `tokens`
FOR EACH ROW
BEGIN

  insert into tokens_revisions
    (tokens_id, token_value, author)
  values
    (NEW.id, NEW.token_value, NEW.author);

  insert into queue
    (task, params)
  values
    ('tokens', concat('update:', NEW.lang));

END
$$

CREATE
TRIGGER `token_update`
AFTER UPDATE ON `tokens`
FOR EACH ROW
BEGIN

  insert into tokens_revisions
    (tokens_id, token_value, author)
  values
    (NEW.id, NEW.token_value, NEW.author);

  insert into queue
    (task, params)
  values
    ('tokens', concat('update:', NEW.lang));
END
$$

CREATE TABLE `tokens_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tokens_id` bigint(20) NOT NULL,
  `token_value` mediumtext,
  `author` varchar(128) NOT NULL,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tokens_revisions_1` (`tokens_id`),
  CONSTRAINT `fk_tokens_revisions_1` FOREIGN KEY (`tokens_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$


