SET NAMES utf8;

CREATE TABLE IF NOT EXISTS `iCalendar_Actions` (
  `cmd_id` INT NOT NULL,
  `datetime` DATETIME NOT NULL,
  `value` MEDIUMTEXT NULL DEFAULT NULL,
  INDEX `cmd_id_index` (`cmd_id` ASC),
  UNIQUE INDEX `unique` (`cmd_id` ASC, `datetime` ASC),
  CONSTRAINT `fk_iCalendarActions_cmd1`
    FOREIGN KEY (`cmd_id`)
    REFERENCES `cmd` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
