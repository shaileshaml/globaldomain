CREATE TABLE IF NOT EXISTS `session_domain` (
  `username` varchar(1000) NOT NULL,
  `domains` varchar(5000) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `crdate` datetime NOT NULL,
  `mddate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
