CREATE TABLE IF NOT EXISTS `lsp` 
( 
  `lsp_id`    BIGINT(20) PRIMARY KEY NOT NULL AUTO_INCREMENT, 
  `device_id` INT(11) unsigned NOT NULL, 
  `lsp_name`  VARCHAR(128) NOT NULL, 
  `lsp_from`  VARCHAR(128) NOT NULL, 
  `lsp_to`    VARCHAR(128) NOT NULL, 
  `bandwidth` INT(11) unsigned NOT NULL,
  CONSTRAINT LSP UNIQUE (lsp_name,device_id)
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;