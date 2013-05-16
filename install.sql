CREATE TABLE IF NOT EXISTS `esa_lit_list` (
  `list_id` varchar(32) NOT NULL default '',
  `is_dynamic` tinyint(3) unsigned NOT NULL default '0',
  `query` varchar(255) NOT NULL default '',
  `query_interval` int(10) unsigned NOT NULL default '0',
  `last_query_time` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`list_id`)
) ENGINE=MyISAM;
CREATE TABLE IF NOT EXISTS `esa_lit_list_content` (
  `list_element_id` varchar(32) NOT NULL default '',
  `dokument_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`list_element_id`)
) ENGINE=MyISAM;
CREATE TABLE IF NOT EXISTS `esa_folder` (
  `folder_id` varchar(32) NOT NULL,
  `accesstime_start` int(10) unsigned NOT NULL,
  `accesstime_end` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`folder_id`)
) ENGINE=MyISAM;
INSERT IGNORE INTO `config` ( `config_id` , `parent_id` , `field` , `value` , `is_default` , `type` , `range` , `section` , `position` , `mkdate` , `chdate` , `description` , `comment` , `message_template` )
VALUES (
MD5( 'ESA_LIT_CATALOG' ) , '', 'ESA_LIT_CATALOG', 'TIBUBOpac', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Literaturkatalog für dynamische Literaturlisten', '', ''
), (
MD5( 'ESA_LIT_CATALOG_SEARCH_FIELD' ) , '', 'ESA_LIT_CATALOG_SEARCH_FIELD' , '1016', '1', 'string', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Suchfeld für dynamische Literaturlisten', '', ''
), (
MD5( 'ESA_LIT_CATALOG_SEARCH_MAX_HITS' ) , '', 'ESA_LIT_CATALOG_SEARCH_MAX_HITS' , '50', '1', 'integer', 'global', '', '0', UNIX_TIMESTAMP( ) , UNIX_TIMESTAMP( ) , 'Maximale Anzahl an Einträgen für dynamische Literaturlisten', '', ''
);
