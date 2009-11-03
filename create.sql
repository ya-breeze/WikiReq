CREATE TABLE IF NOT EXISTS `breeze_Reqs` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('Unknown','Reserved','New','Agreed','Implemented','Tested','Deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'New',
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `test` text COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `upward` text COLLATE utf8_unicode_ci NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `priority` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
