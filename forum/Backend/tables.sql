-- CREATE DATABASE letsel;
--
-- use letsel;
use ictprodu_site;

CREATE TABLE `categories`
(
 `cat_name` char(50) NOT NULL ,
 PRIMARY KEY (`cat_name`)
);

CREATE TABLE `topics`
(
 `topic_id`         mediumint unsigned NOT NULL AUTO_INCREMENT,
 `topic_name`       char(100) NOT NULL,
 `cat_name`         char(50) NOT NULL ,
 `user_id`          mediumint unsigned NOT NULL ,
 `is_anonym`        bit(1) NOT NULL ,
 `create_timestamp` timestamp NOT NULL ,
PRIMARY KEY (`topic_id`),
FOREIGN KEY (cat_name)
        REFERENCES categories (cat_name)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);



CREATE TABLE `posts`
(
 `post_id`          int unsigned NOT NULL AUTO_INCREMENT ,
 `response_to`      int unsigned ,
 `topic_id`         mediumint unsigned NOT NULL ,
 `user_id`          mediumint unsigned NOT NULL ,
 `post_message`    text NOT NULL ,
 `is_anonym`        bit(1) NOT NULL ,
 `create_timestamp` timestamp NOT NULL ,
PRIMARY KEY (`post_id`),
FOREIGN KEY (topic_id)
        REFERENCES topics (topic_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


CREATE TABLE `favorites`
(
 `user_id`  mediumint unsigned NOT NULL ,
 `topic_id` mediumint unsigned NOT NULL ,
PRIMARY KEY (`user_id`, `topic_id`)
);


CREATE TABLE `likes`
(
 `post_id` int unsigned NOT NULL ,
 `user_id` mediumint unsigned NOT NULL ,
PRIMARY KEY (`post_id`, `user_id`)
);





ALTER TABLE ictprodu_wp996.posts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE ictprodu_wp996.topics CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
ALTER TABLE ictprodu_wp996.categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;