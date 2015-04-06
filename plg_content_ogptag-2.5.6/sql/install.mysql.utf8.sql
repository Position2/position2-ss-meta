CREATE TABLE IF NOT EXISTS `#__content_ogptag` (
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`article_id` int(10) NOT NULL,
	`ogptags` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;