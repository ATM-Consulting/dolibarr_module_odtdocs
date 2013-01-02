CREATE TABLE IF NOT EXISTS `llx_atm_doctbs` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `id_entity` int(11) NOT NULL,
  `livedocx_login` varchar(255) NOT NULL,
  `livedocx_password` varchar(255) NOT NULL,
  `livedocx_use` int(11) NOT NULL,
  `date_cre` date NOT NULL,
  `date_maj` date NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `id_entity` (`id_entity`)
) ENGINE=innoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

