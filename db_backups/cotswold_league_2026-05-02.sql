-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cotswold_league
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `club_name` varchar(100) NOT NULL,
  `action` varchar(255) NOT NULL,
  `change_details` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'League Admin','Venue Update','Parking: \'Onsite car park paid \' -> \'Onsite car park paid but not expensive\'','2026-02-17 10:26:15'),(2,'League Admin','Venue Update','Parking: \'Onsite car park paid but not expensive\' -> \'Onsite car park paid but not expensive and numerous\'','2026-02-17 10:31:10'),(3,'Academy Swim Team','Venue Update','[AST] Parking: \'Onsite car park paid but not expensive and numerous\' -> \'Testing\'','2026-02-17 10:49:49'),(4,'Academy Swim Team','Venue Update','[AST] Parking: \'Testing\' -> \'Hello\'','2026-02-17 11:01:59'),(5,'Southwold SC','Venue Update','[Southwold] Parking: \'Rear Car Park Free from 6pm\' -> \'Rear Car Park Free from 6pm Front car park (McDonald\'s side) free for 4 hours)\'','2026-02-20 17:51:58'),(6,'Severnside Tritons Rep','Venue Update','[Severnside] WarmUp: \'Check with Host\' -> \'\'','2026-03-27 20:02:48'),(7,'Severnside Tritons Rep','Venue Update','[Severnside] Payment: \'Check with Host\' -> \'£3 card or cash\'','2026-03-27 20:03:29'),(8,'Severnside Tritons Rep','Venue Update','[Severnside] WarmUp: \'\' -> \'5:15pm, start 5:50pm\'','2026-03-28 09:48:11'),(9,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 15:57:57'),(10,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 15:58:36'),(11,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 15:58:50'),(12,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:09:04'),(13,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:10:19'),(14,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:13:21'),(15,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:13:47'),(16,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:18:55'),(17,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-19 16:19:37'),(18,'Super Admin','Venue Override','Overridden details for venue id: 10','2026-04-24 16:58:04'),(19,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-24 16:58:29'),(20,'Super Admin','Venue Override','Overridden details for venue id: 4','2026-04-24 16:58:49'),(21,'Super Admin','Venue Override','Overridden details for venue id: 5','2026-04-24 16:59:07'),(22,'Super Admin','Venue Override','Overridden details for venue id: 3','2026-04-24 16:59:19'),(23,'Super Admin','Venue Override','Overridden details for venue id: 1','2026-04-24 17:07:16'),(24,'Super Admin','Venue Override','Overridden details for venue id: 7','2026-04-24 17:07:43'),(25,'Super Admin','Venue Override','Overridden details for venue id: 9','2026-04-24 17:07:59'),(26,'Super Admin','Venue Override','Overridden details for venue id: 8','2026-04-24 17:08:15'),(27,'Super Admin','Venue Override','Overridden details for venue id: 6','2026-04-24 17:08:26'),(28,'Super Admin','Venue Override','Overridden details for venue id: 12','2026-04-24 17:09:13'),(29,'Super Admin','Venue Override','Overridden details for venue id: 13','2026-04-24 17:09:32'),(30,'Super Admin','Venue Override','Overridden details for venue id: 11','2026-04-24 17:09:49'),(31,'Super Admin','Venue Override','Overridden details for venue id: 15','2026-04-24 17:10:17'),(32,'Super Admin','Venue Override','Overridden details for venue id: 14','2026-04-24 17:10:39'),(33,'Super Admin','Venue Override','Overridden details for venue id: 18','2026-04-24 17:11:08'),(34,'Super Admin','Venue Override','Overridden details for venue id: 17','2026-04-24 17:11:25'),(35,'Super Admin','Venue Override','Overridden details for venue id: 16','2026-04-24 17:11:44'),(36,'Super Admin','Venue Override','Overridden details for venue id: 19','2026-04-24 17:11:58'),(37,'Super Admin','Venue Override','Overridden details for venue id: 20','2026-04-24 17:12:16'),(38,'Super Admin','Venue Override','Overridden details for venue id: 5','2026-04-24 17:27:41'),(39,'Super Admin','Venue Override','Overridden details for venue id: 3','2026-04-24 17:28:02'),(40,'Super Admin','Venue Override','Overridden details for venue id: 2','2026-04-24 17:28:13'),(41,'Super Admin','Venue Override','Overridden details for venue id: 4','2026-04-24 17:28:20'),(42,'Super Admin','Venue Override','Overridden details for venue id: 10','2026-04-24 17:28:37'),(43,'Super Admin','Venue Override','Overridden details for venue id: 10','2026-04-24 20:00:41'),(44,'Super Admin','Venue Override','Overridden details for venue id: 10','2026-04-25 10:15:21'),(45,'Super Admin','Venue Override','Overridden details for venue id: 16','2026-04-25 10:17:29');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `club_contacts`
--

DROP TABLE IF EXISTS `club_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `club_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `club_id` int(11) NOT NULL,
  `club_name` varchar(100) NOT NULL,
  `access_pin` varchar(4) NOT NULL DEFAULT '0000',
  `contact1_name` varchar(100) DEFAULT NULL,
  `contact1_email` varchar(150) DEFAULT NULL,
  `contact2_name` varchar(100) DEFAULT NULL,
  `contact2_email` varchar(150) DEFAULT NULL,
  `contact3_name` varchar(100) DEFAULT NULL,
  `contact3_email` varchar(150) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `club_id` (`club_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club_contacts`
--

LOCK TABLES `club_contacts` WRITE;
/*!40000 ALTER TABLE `club_contacts` DISABLE KEYS */;
INSERT INTO `club_contacts` VALUES (1,1,'Academy Swim Team','2015','Nic Hazelton','nic.hazelton@astbos.co.uk','Lewis Plume','lewis.plume@astbos.co.uk','','','2026-02-19 08:40:07'),(2,2,'Backwell','9836','Sue Rogers','suemerv@outlook.com','Colin Jackson-','colinjackson1972@gmail.com','Claire Jones','clairesjones@gmail.com','2026-03-05 10:53:15'),(3,3,'Bath Dolphin','1899','Tegan Carpenter','tegan.carpenter@hotmail.com','Comp Sec','bdsccompetitionsec@gmail.com','Chairman','bdscchair@gmail.com','2026-04-25 21:50:52'),(4,4,'Bridgwater','0000','Tia Hayes','yuletia@talktalk.net','Ricky Hayes','headcoach@bridgwaterswim.co.uk',NULL,NULL,'2026-02-18 17:32:32'),(5,5,'Bristol North','0000','Dan Bradshaw','chair@bristolnorthsc.org.uk','Petrina Casey','headcoach@bristolnorthsc.org.uk','Keri Vickery','clubsecretary@bristolnorthsc.org.uk','2026-02-18 17:32:32'),(6,6,'Brockworth','1976','Carrie','brockworthsc20@outlook.com','Phil Lane Chair','phil.brockworthsc@gmail.com',NULL,'membership_brockworthsc20@outlook.com','2026-02-19 11:09:41'),(7,7,'Burnham-On-Sea','8151','Kelly Podbury','kellypods07@gmail.com','Mark Podbury-','m_podbury@yahoo.co.uk','Burnham club','burnham@swimclubmanager.co.uk','2026-02-19 22:06:16'),(8,8,'Clevedon','2016','Sarah Boyle','Team_clevedon@clevedonasc.org.uk','Emma Wells','team_clevedon@clevedonasc.org.uk',NULL,NULL,'2026-04-25 07:36:45'),(9,9,'COB (City of Bristol)','1989','Marc Williams','cobassistheadcoach@gmail.com',NULL,NULL,NULL,NULL,'2026-04-24 21:00:06'),(10,10,'Corsham','0000','Zahid Mahmood','zahid.h.mahmood@gmail.com',NULL,NULL,NULL,NULL,'2026-02-18 17:32:32'),(11,11,'Cwmbran','1974','Lee-Anthony Carpenter','secretarycwmbranotters@hotmail.com','David Bendon','Headcoach.cwmbranottersasc@outlook.com','Gerald Sims','geraldsims@hotmail.co.uk','2026-02-19 10:59:19'),(12,12,'Dursley','0000','Adam','chair@dursleydolphins.org.uk',NULL,NULL,NULL,'jonfalco@hotmail.com','2026-02-18 17:32:32'),(13,13,'Forest of Dean','0000','Craig Skinner','headcoach.fodsc@gmail.com','Lorna Farbowski','secretary@fodsc.com','','','2026-03-03 09:56:57'),(14,14,'Monnow SC','1979','Alun Parker - Chair','chairman@monnowsc.co.uk','Angela McGrath','competitions@monnowsc.co.uk','Richard Glyn-Jones','treasurer@monnowsc.co.uk','2026-04-25 23:10:52'),(15,15,'Newport','0808','Barrie Roberts','meetmanager.newportswimming@gmail.com',NULL,NULL,NULL,NULL,'2026-03-06 19:01:56'),(16,16,'Severnside Tritons','1111','Becky Antliff','leagues@severnsidetritons.org.uk','Keith Smith','keith.smith@severnsidetritons.org.uk',NULL,NULL,'2026-03-28 11:40:04'),(17,17,'Southwold SC','7018','Neil Holloway','neihol@live.com','Simon Wilkins','simonwilkins556@gmail.com',NULL,NULL,'2026-04-26 07:40:15'),(18,18,'Swindon ASC','0000','Sarah Bailey','swindonascsbailey@gmail.com','Louise Cotton','garycotton@ntlworld.com',NULL,NULL,'2026-02-18 17:32:32'),(19,19,'Wells','0000','Lee Chard','wellsswimmingclub@live.co.uk','Paul Perry','wellssc.headcoach@gmail.com','Danielle Dodge','wellssc.competition.secretary@gmail.com','2026-02-18 17:32:32'),(20,20,'Yeovil','0000','Judi Swan','judiswan08@gmail.com','Clare','ydsc.competitions@gmail.com','Ian','ydsc.headcoachian@gmail.com','2026-04-17 20:02:00');
/*!40000 ALTER TABLE `club_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clubs`
--

DROP TABLE IF EXISTS `clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clubs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `logo` varchar(50) DEFAULT NULL,
  `pool_name` varchar(100) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clubs`
--

LOCK TABLES `clubs` WRITE;
/*!40000 ALTER TABLE `clubs` DISABLE KEYS */;
INSERT INTO `clubs` VALUES (1,'Academy Swim Team','AST.webp','Burnham Swim & Sports Academy','TA8 2ET','https://www.astbos.co.uk',51.24029000,-2.99700000),(2,'Backwell','backwell.webp','Backwell Leisure Centre','BS48 3PB','https://www.backwellswimmingclub.com',51.41572330,-2.73613790),(3,'Bath Dolphin','Bath.webp','Bath Sports & Leisure Centre','BA2 4ET','https://uk.gomotionapp.com/team/bdsc/page/home',51.38154800,-2.35400700),(4,'Bridgwater','bridgwater.webp','Trinity Sports & Leisure','TA6 3JA','http://bridgwaterswim.co.uk',51.13956660,-3.00630490),(5,'Bristol North','BristolNorth.webp','Filton Leisure Centre','BS34 7PS','http://www.bristolnorthsc.org.uk',51.50647040,-2.57486950),(6,'Brockworth','brockworth.webp','Brockworth Sports Centre','GL3 4QF','https://www.brockworth-swimming.co.uk',51.84861240,-2.14918150),(7,'Burnham-On-Sea','Burnham.webp','Brean Leisure Park','TA8 2QY','http://www.burnhamonseaswimmingclub.co.uk',51.28420310,-3.00330720),(8,'Clevedon','Clevedon.webp','Strode Leisure Centre','BS21 6QG','http://www.clevedonasc.co.uk',51.42940610,-2.86370110),(9,'COB (City of Bristol)','cob.webp','Hengrove Park Leisure Centre','BS14 0DE','https://www.cobswimmingclub.org',51.41257910,-2.58373330),(10,'Corsham','Corsham.webp','Springfield Community Campus','SN13 9DN','https://www.corshamasc.club',51.43186050,-2.19278810),(11,'Cwmbran','cwmbran.webp','Cwmbran Stadium','NP44 3YS','https://www.facebook.com/CwmbranSwimmingClub',51.64339930,-3.02093310),(12,'Dursley','Dursley.webp','The Pulse, Dursley','GL11 4JX','https://www.dursleydolphins.org.uk',51.68059000,-2.35451000),(13,'Forest of Dean','fod.webp','Freedom Leisure Cinderford','GL14 2QA','https://fodsc.com',51.82629720,-2.49009760),(14,'Monnow SC','monnow.webp','Monmouth Leisure Centre','NP25 3DP','https://www.monnowsc.co.uk',51.81336630,-2.70875200),(15,'Newport','Newport.webp','Regional Pool & Tennis Centre','NP19 4RA','https://www.newportswimmingclub.co.uk/',51.57142830,-2.96128780),(16,'Severnside Tritons','severnside.webp','Thornbury Leisure Centre','BS35 3JB','http://www.severnsidetritons.org.uk',51.60056710,-2.52731600),(17,'Southwold SC','Southwold.webp','Yate Leisure Centre','BS37 4DQ','http://www.southwoldswimmingclub.com',51.54063670,-2.41646180),(18,'Swindon ASC','Swindon.webp','Link Centre','SN5 7DL','https://swindonasc.org.uk',51.55877780,-1.82915330),(19,'Wells','Wells.webp','Wells Leisure Centre','BA5 2FB','https://www.wellsswimmingclub.com',51.21232630,-2.66349440),(20,'Yeovil','yeovil.webp','Goldenstones Leisure Centre','BA20 1QZ','https://www.ydsc.co.uk',50.93713000,-2.63546000);
/*!40000 ALTER TABLE `clubs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `club_id` int(11) DEFAULT NULL,
  `round_1` int(11) DEFAULT 0,
  `round_2` int(11) DEFAULT 0,
  `round_3` int(11) DEFAULT 0,
  `round_4` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  CONSTRAINT `results_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
INSERT INTO `results` VALUES (1,1,96,161,114,121,0),(2,2,115,107,121,139,0),(3,3,149,158,161,145,0),(4,4,152,147,149,154,0),(5,5,172,104,108,82,0),(6,6,115,137,148,127,0),(7,7,107,112,131,86,0),(8,8,130,141,135,136,0),(9,9,152,155,172,132,0),(10,10,100,96,72,72,0),(11,11,142,155,126,159,0),(12,12,113,122,112,73,0),(13,13,128,143,90,122,0),(14,14,142,129,147,164,0),(15,15,96,121,106,127,0),(16,16,118,120,152,164,0),(17,17,151,140,145,152,0),(18,18,130,122,131,158,0),(19,19,138,118,144,125,0),(20,20,111,114,113,114,0);
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracking_stats`
--

DROP TABLE IF EXISTS `tracking_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracking_stats` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `action_name` varchar(50) NOT NULL,
  `count` int(10) unsigned DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `action_name` (`action_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracking_stats`
--

LOCK TABLES `tracking_stats` WRITE;
/*!40000 ALTER TABLE `tracking_stats` DISABLE KEYS */;
INSERT INTO `tracking_stats` VALUES (1,'programme_generated',0,'2026-04-28 17:06:58'),(2,'report_generated',0,'2026-04-28 17:06:58');
/*!40000 ALTER TABLE `tracking_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venue_details`
--

DROP TABLE IF EXISTS `venue_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venue_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `club_id` int(11) DEFAULT NULL,
  `round_number` int(11) NOT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `warmup_time` varchar(100) DEFAULT 'Refer to Host',
  `start_time` varchar(100) DEFAULT NULL,
  `payment_info` varchar(255) DEFAULT 'Cash Only',
  `parking_info` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `team_1_id` int(11) DEFAULT NULL,
  `team_2_id` int(11) DEFAULT NULL,
  `team_3_id` int(11) DEFAULT NULL,
  `team_4_id` int(11) DEFAULT NULL,
  `round_date` varchar(50) DEFAULT NULL,
  `results_file` varchar(255) DEFAULT NULL,
  `teamsheet_link` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_venue_club` (`club_id`),
  CONSTRAINT `fk_venue_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_details`
--

LOCK TABLES `venue_details` WRITE;
/*!40000 ALTER TABLE `venue_details` DISABLE KEYS */;
INSERT INTO `venue_details` VALUES (1,11,1,'Halo Pontypool Active Living Centre','Trosnant St, Pontypool NP4 8AT','Doors 5:30pm','Start 6:00pm','Cash Only','Free Parking','2026-04-24 16:07:16',11,20,12,14,'31/01/2026','R1_Cwmbran_Results.xlsx',NULL),(2,2,1,'Backwell Leisure Centre','Farleigh Rd, Backwell, Bristol BS48 3PB','WU 5:15pm','Doors 5:00pm','Spectators £3 Cash','Check Centre Info','2026-04-24 16:28:13',2,6,4,13,'31/01/2026','R1_Backwell_Results.xlsx',NULL),(3,10,1,'Trowbridge Sports Centre','Frome Road, Trowbridge, Wilts BA14 0DN','WU 2:30pm','Doors 2:15pm','Spectators £3 (Cash/Card)','Free Parking','2026-04-24 16:28:02',10,18,7,5,'31/01/2026','R1_Corsham_Results.xlsx',NULL),(4,3,1,'Bath Leisure Centre','North Parade Rd, Bathwick, Bath BA2 4ET','WU 5:00pm','Doors 4:30pm','Spectators £3 Cash','Paid Parking (Centre or Cricket Club)','2026-04-24 16:28:20',3,8,19,15,'31/01/2026','R1_BathDolphin_Results.xlsx',NULL),(5,9,1,'Hengrove Park Leisure Centre','Hengrove Promenade, Bristol BS14 0DE','Check with Host','Doors 2:15pm','Spectators £3 Cash/Card','Free Parking (3hrs with reg input)','2026-04-24 16:27:41',9,1,17,16,'31/01/2026','R1_COBCityofBristol_Results.xlsx',NULL),(6,20,2,'Sherbourne Sports Centre','Bradford Rd, Sherborne, Dorset DT9 3QN','WU 5:30pm','Doors 5:00pm','Spectators £3 Cash','No Coffee Pod available','2026-04-24 16:08:26',20,3,4,5,'14/02/2026','R2_Yeovil_Results.xlsx',NULL),(7,6,2,'Leisure at Cheltenham','Tommy Taylors Ln, Cheltenham GL50 4RN','Check with Host','Doors 5:45pm','Check with Host','Check Centre Info','2026-04-24 16:07:43',6,9,7,15,'14/02/2026','R2_Brockworth_Results.xlsx',NULL),(8,18,2,'Health Hydro','Milton Rd, Swindon SN1 5JA','WU 1:30pm','Doors 1:15pm','Spectators £3','See local parking signs','2026-04-24 16:08:15',18,11,19,16,'14/02/2026','R2_SwindonASC_Results.xlsx',NULL),(9,8,2,'Hutton Moor Leisure Centre','Weston-super-Mare','Check with Host','Doors 6:15pm','Card Payment Preferred','Free Parking (Get permit from reception)','2026-04-24 16:07:59',8,2,17,14,'14/02/2026','R2_Clevedon_Results.xlsx',NULL),(10,1,2,'Burnham Swim & Sports Academy','Berrow Road, Burnham-on-Sea','15:00','Doors 3:00pm','Cash & Card','Hello','2026-04-24 16:28:37',1,10,12,13,'14/02/2026','R2_AcademySwimTeam_Results.xlsx',NULL),(11,12,3,'Keynsham Leisure Centre','Temple Street, BS31 1HE','Check with Host','Doors 12:30pm','Check with Host','Check Centre Info','2026-04-24 16:09:49',12,9,8,5,'07/03/2026','R3_Dursley_Results.xlsx',NULL),(12,4,3,'Trinity Leisure Centre','Bridgwater','Check with Host','Doors 5:00pm','Check with Host','Free Onsite Parking','2026-04-24 16:09:13',4,11,1,15,'07/03/2026','R3_Bridgwater_Results.xlsx',NULL),(13,7,3,'Millfield School','Street, BA16 0ST','Check with Host','Doors 6:00pm','Card Preferred / Cash Alt','Plenty of Free Parking','2026-04-24 16:09:32',7,2,20,16,'07/03/2026','R3_BurnhamOnSea_Results.xlsx',NULL),(14,19,3,'Millfield School','Street, BA16 0ST','Check with Host','Doors 6:00pm','Card Preferred / Cash Alt','Plenty of Free Parking','2026-04-24 16:10:39',19,10,6,14,'07/03/2026','R3_Wells_Results.xlsx',NULL),(15,17,3,'Yate Leisure Centre','BS37 4DQ','Check with Host','Doors 6:00pm','Spectating Upstairs','Rear Car Park Free from 6pm Front car park (McDonald\'s side) free for 4 hours)','2026-04-24 16:10:17',17,3,18,13,'07/03/2026','R3_SouthwoldSC_Results.xlsx',NULL),(16,14,4,'Newport Regional Pool','Spytty Blvd, Newport NP19 4RA','Check with Host','Doors 4:00pm','Cash Only','Free Parking outside','2026-04-25 09:17:29',14,3,1,7,'28/03/2026','R4_MonnowSC_Results.xlsx','https://docs.google.com/spreadsheets/d/1BX9KTNqzip1X7_3f5Vc-kwGgpUwhIOtfXLvy4SirD80/edit?usp=drive_link'),(17,13,4,'GL1 Leisure Centre','GL1 1DT','Check with Host','Doors 5:00pm','Check with Host','Check Centre Info','2026-04-24 16:11:25',13,9,20,19,'28/03/2026','R4_ForestofDean_Results.xlsx',NULL),(18,5,4,'Keynsham Leisure Centre','Temple Street, BS31 1HE','12:30pm - 3:30pm','Doors 12:30pm','Check with Host','Check Centre Info','2026-04-24 16:11:08',5,11,6,17,'28/03/2026','R4_BristolNorth_Results.xlsx',NULL),(19,15,4,'Newport Regional Pool','Spytty Blvd, Newport NP19 4RA','Check with Host','Doors 4:00pm','Cash Only','Free Parking outside','2026-04-24 16:11:58',15,2,18,12,'28/03/2026','R4_Newport_Results.xlsx',NULL),(20,16,4,'GL1 Leisure Centre','GL1 1DT','5:15pm, start 5:50pm','Doors 5:00pm','£3 card or cash','Check Centre Info','2026-04-24 16:12:16',16,10,8,4,'28/03/2026','R4_SevernsideTritons_Results.xlsx',NULL);
/*!40000 ALTER TABLE `venue_details` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-02 23:30:08
