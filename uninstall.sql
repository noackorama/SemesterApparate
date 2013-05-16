DROP TABLE IF EXISTS `esa_folder`;
DROP TABLE IF EXISTS `esa_lit_list`;
DROP TABLE IF EXISTS `esa_lit_list_content`;
DELETE FROM  `config` WHERE `field` LIKE 'ESA_LIT_CATALOG%' LIMIT 3;
