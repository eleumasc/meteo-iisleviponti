/*
 * Project: Meteo
 * Description: Script SQL di creazione del database
 * Version: 1.6
 * Release date: 26/04/2016
 * Developer: Casarin Samuele, 5BIN A.S. 2015/2016
*/

SET sql_mode = 'TRADITIONAL';

CREATE TABLE `mlp_current_data` (
  `dateTime` DATETIME NOT NULL COMMENT 'Date and time of the measurement',
  `temperature` DECIMAL(5,2) NOT NULL COMMENT 'Temperature in degrees Celsius (°C)',
  `humidity` DECIMAL(5,2) NOT NULL COMMENT 'Humidity in percentual (%)',
  `pressure` DECIMAL(6,2) NOT NULL COMMENT 'Pressure in hectopascal (hPa)',
  PRIMARY KEY (`dateTime`)
);

CREATE TABLE `mlp_historical_data` (
  `dateTime` DATETIME NOT NULL COMMENT 'Date and time of the measurement',
  `temperature` DECIMAL(5,2) NOT NULL COMMENT 'Temperature in degrees Celsius (°C)',
  `humidity` DECIMAL(5,2) NOT NULL COMMENT 'Humidity in percentual (%)',
  `pressure` DECIMAL(6,2) NOT NULL COMMENT 'Pressure in hectopascal (hPa)',
  PRIMARY KEY (`dateTime`)
);

CREATE VIEW `mlp_last_data` AS
SELECT * FROM `mlp_historical_data` WHERE `dateTime` = ( SELECT MAX(`dateTime`) FROM `mlp_historical_data` ) UNION SELECT * FROM `mlp_current_data` WHERE `dateTime` = ( SELECT MAX(`dateTime`) FROM `mlp_current_data` );

DELIMITER //
CREATE PROCEDURE `mlp_commit`()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE dateTime1 DATETIME;
  DECLARE temperature1 DECIMAL(5, 2);
  DECLARE humidity1 DECIMAL(5, 2);
  DECLARE pressure1 DECIMAL(6, 2);
  DECLARE cur1 CURSOR FOR
    SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`dateTime`) - UNIX_TIMESTAMP(`dateTime`) % 900) `dateTimeDivision`, AVG(`temperature`), AVG(`humidity`), AVG(`pressure`) FROM `mlp_current_data` WHERE TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(UNIX_TIMESTAMP(`dateTime`) - UNIX_TIMESTAMP(`dateTime`) % 900), NOW()) >= 15 GROUP BY `dateTimeDivision`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  OPEN cur1;
  REPEAT
    FETCH cur1 INTO dateTime1, temperature1, humidity1, pressure1;
    IF NOT done THEN
      INSERT IGNORE INTO `mlp_historical_data` (`dateTime`, `temperature`, `humidity`, `pressure`) VALUES (TIMESTAMPADD(MINUTE, 15, dateTime1), temperature1, humidity1, pressure1);
    END IF;
  UNTIL done END REPEAT;
  CLOSE cur1;
  DELETE FROM `mlp_current_data` WHERE TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(UNIX_TIMESTAMP(`dateTime`) - UNIX_TIMESTAMP(`dateTime`) % 900), NOW()) >= 15;
END//
DELIMITER ;