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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'League Admin','Venue Update','Parking: \'Onsite car park paid \' -> \'Onsite car park paid but not expensive\'','2026-02-17 10:26:15'),(2,'League Admin','Venue Update','Parking: \'Onsite car park paid but not expensive\' -> \'Onsite car park paid but not expensive and numerous\'','2026-02-17 10:31:10'),(3,'Academy Swim Team','Venue Update','[AST] Parking: \'Onsite car park paid but not expensive and numerous\' -> \'Testing\'','2026-02-17 10:49:49'),(4,'Academy Swim Team','Venue Update','[AST] Parking: \'Testing\' -> \'Hello\'','2026-02-17 11:01:59'),(5,'Southwold SC','Venue Update','[Southwold] Parking: \'Rear Car Park Free from 6pm\' -> \'Rear Car Park Free from 6pm Front car park (McDonald\'s side) free for 4 hours)\'','2026-02-20 17:51:58');
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
INSERT INTO `club_contacts` VALUES (1,1,'Academy Swim Team','2015','Nic Hazelton','nic.hazelton@astbos.co.uk','Lewis Plume','lewis.plume@astbos.co.uk','','','2026-02-19 08:40:07'),(2,2,'Backwell','9836','Sue Rogers','suemerv@outlook.com','Colin Jackson-','colinjackson1972@gmail.com','Claire Jones','clairesjones@gmail.com','2026-03-05 10:53:15'),(3,3,'Bath Dolphin','1986','Tegan Carpenter','tegan.carpenter@hotmail.com','Comp Sec','bdsccompetitionsec@gmail.com','Chairman','bdscchair@gmail.com','2026-02-22 12:55:56'),(4,4,'Bridgwater','0000','Tia Hayes','yuletia@talktalk.net','Ricky Hayes','headcoach@bridgwaterswim.co.uk',NULL,NULL,'2026-02-18 17:32:32'),(5,5,'Bristol North','0000','Dan Bradshaw','chair@bristolnorthsc.org.uk','Petrina Casey','headcoach@bristolnorthsc.org.uk','Keri Vickery','clubsecretary@bristolnorthsc.org.uk','2026-02-18 17:32:32'),(6,6,'Brockworth','1976','Carrie','brockworthsc20@outlook.com','Phil Lane Chair','phil.brockworthsc@gmail.com',NULL,'membership_brockworthsc20@outlook.com','2026-02-19 11:09:41'),(7,7,'Burnham-On-Sea','8151','Kelly Podbury','kellypods07@gmail.com','Mark Podbury-','m_podbury@yahoo.co.uk','Burnham club','burnham@swimclubmanager.co.uk','2026-02-19 22:06:16'),(8,8,'Clevedon','0000','Sarah Boyle','Team_clevedon@clevedonasc.org.uk','Emma Wells','team_clevedon@clevedonasc.org.uk',NULL,NULL,'2026-02-18 17:32:32'),(9,9,'COB (City of Bristol)','0000','Marc Williams','cobassistheadcoach@gmail.com',NULL,NULL,NULL,NULL,'2026-02-18 17:32:32'),(10,10,'Corsham','0000','Zahid Mahmood','zahid.h.mahmood@gmail.com',NULL,NULL,NULL,NULL,'2026-02-18 17:32:32'),(11,11,'Cwmbran','1974','Lee-Anthony Carpenter','secretarycwmbranotters@hotmail.com','David Bendon','Headcoach.cwmbranottersasc@outlook.com','Gerald Sims','geraldsims@hotmail.co.uk','2026-02-19 10:59:19'),(12,12,'Dursley','0000','Adam','chair@dursleydolphins.org.uk',NULL,NULL,NULL,'jonfalco@hotmail.com','2026-02-18 17:32:32'),(13,13,'Forest of Dean','0000','Craig Skinner','headcoach.fodsc@gmail.com','Lorna Farbowski','secretary@fodsc.com','','','2026-03-03 09:56:57'),(14,14,'Monnow SC','0000','Alun Parker - Chair','chairman@monnowsc.co.uk','Angela McGrath','competitions@monnowsc.co.uk','Richard Glyn-Jones','treasurer@monnowsc.co.uk','2026-02-18 17:32:32'),(15,15,'Newport','0808','Barrie Roberts','meetmanager.newportswimming@gmail.com',NULL,NULL,NULL,NULL,'2026-03-06 19:01:56'),(16,16,'Severnside Tritons','0000','Becky Antliff','leagues@severnsidetritons.org.uk','Keith Smith','keith.smith@severnsidetritons.org.uk',NULL,NULL,'2026-02-18 17:32:32'),(17,17,'Southwold SC','0000','Neil Holloway','neihol@live.com','Simon Wilkins','simonwilkins556@gmail.com',NULL,NULL,'2026-02-18 17:32:32'),(18,18,'Swindon ASC','0000','Sarah Bailey','swindonascsbailey@gmail.com','Louise Cotton','garycotton@ntlworld.com',NULL,NULL,'2026-02-18 17:32:32'),(19,19,'Wells','0000','Lee Chard','wellsswimmingclub@live.co.uk','Paul Perry','wellssc.headcoach@gmail.com','Danielle Dodge','wellssc.competition.secretary@gmail.com','2026-02-18 17:32:32'),(20,20,'Yeovil','0000','Judi Swan','ydsc.committee@gmail.com','Clare','ydsc.competitions@gmail.com','Ian','ydsc.headcoachian@gmail.com','2026-02-18 17:32:32');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clubs`
--

LOCK TABLES `clubs` WRITE;
/*!40000 ALTER TABLE `clubs` DISABLE KEYS */;
INSERT INTO `clubs` VALUES (1,'Academy Swim Team','AST.webp','Burnham Swim & Sports Academy','TA8 2ET','https://www.astbos.co.uk'),(2,'Backwell','backwell.webp','Backwell Leisure Centre','BS48 3PB','https://www.backwellswimmingclub.com'),(3,'Bath Dolphin','Bath.webp','Bath Sports & Leisure Centre','BA2 4ET','https://uk.gomotionapp.com/team/bdsc/page/home'),(4,'Bridgwater','bridgwater.webp','Trinity Sports & Leisure','TA6 3JA','http://bridgwaterswim.co.uk'),(5,'Bristol North','BristolNorth.webp','Filton Leisure Centre','BS34 7PS','http://www.bristolnorthsc.org.uk'),(6,'Brockworth','brockworth.webp','Brockworth Sports Centre','GL3 4QF','https://www.brockworth-swimming.co.uk'),(7,'Burnham-On-Sea','Burnham.webp','Brean Leisure Park','TA8 2QY','http://www.burnhamonseaswimmingclub.co.uk'),(8,'Clevedon','Clevedon.webp','Strode Leisure Centre','BS21 6QG','http://www.clevedonasc.co.uk'),(9,'COB (City of Bristol)','cob.webp','Hengrove Park Leisure Centre','BS14 0DE','https://www.cobswimmingclub.org'),(10,'Corsham','Corsham.webp','Springfield Community Campus','SN13 9DN','https://www.corshamasc.club'),(11,'Cwmbran','cwmbran.webp','Cwmbran Stadium','NP44 3YS','https://www.facebook.com/CwmbranSwimmingClub'),(12,'Dursley','Dursley.webp','The Pulse, Dursley','GL11 4JX','https://www.dursleydolphins.org.uk'),(13,'Forest of Dean','fod.webp','Freedom Leisure Cinderford','GL14 2QA','https://fodsc.com'),(14,'Monnow SC','monnow.webp','Monmouth Leisure Centre','NP25 3DP','https://www.monnowsc.co.uk'),(15,'Newport','Newport.webp','Regional Pool & Tennis Centre','NP19 4RA','https://www.newportswimmingclub.co.uk/'),(16,'Severnside Tritons','severnside.webp','Thornbury Leisure Centre','BS35 3JB','http://www.severnsidetritons.org.uk'),(17,'Southwold SC','Southwold.webp','Yate Leisure Centre','BS37 4DQ','http://www.southwoldswimmingclub.com'),(18,'Swindon ASC','Swindon.webp','Link Centre','SN5 7DL','https://swindonasc.org.uk'),(19,'Wells','Wells.webp','Wells Leisure Centre','BA5 2FB','https://www.wellsswimmingclub.com'),(20,'Yeovil','yeovil.webp','Goldenstones Leisure Centre','BA20 1QZ','https://www.ydsc.co.uk');
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
INSERT INTO `results` VALUES (1,1,96,161,0,0,0),(2,2,115,107,0,0,0),(3,3,149,158,0,0,0),(4,4,152,147,0,0,0),(5,5,172,104,0,0,0),(6,6,115,137,0,0,0),(7,7,107,112,0,0,0),(8,8,130,141,0,0,0),(9,9,152,155,0,0,0),(10,10,100,96,0,0,0),(11,11,142,155,0,0,0),(12,12,113,122,0,0,0),(13,13,128,143,0,0,0),(14,14,142,129,0,0,0),(15,15,96,121,0,0,0),(16,16,118,120,0,0,0),(17,17,151,140,0,0,0),(18,18,130,122,0,0,0),(19,19,138,118,0,0,0),(20,20,111,114,0,0,0);
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
INSERT INTO `tracking_stats` VALUES (1,'programme_generated',6,'2026-03-06 20:42:57'),(2,'report_generated',5,'2026-02-18 14:21:17');
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
  `host_club` varchar(100) NOT NULL,
  `round_number` int(11) NOT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `warmup_time` varchar(100) DEFAULT 'Refer to Host',
  `start_time` varchar(100) DEFAULT NULL,
  `payment_info` varchar(255) DEFAULT 'Cash Only',
  `parking_info` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_details`
--

LOCK TABLES `venue_details` WRITE;
/*!40000 ALTER TABLE `venue_details` DISABLE KEYS */;
INSERT INTO `venue_details` VALUES (1,'Cwmbran',1,'Halo Pontypool Active Living Centre','Trosnant St, Pontypool NP4 8AT','Doors 5:30pm','Start 6:00pm','Cash Only','Free Parking','2026-02-17 08:03:46'),(2,'Backwell',1,'Backwell Leisure Centre','Farleigh Rd, Backwell, Bristol BS48 3PB','WU 5:15pm','Doors 5:00pm','Spectators £3 Cash','Check Centre Info','2026-02-17 08:03:46'),(3,'Corsham',1,'Trowbridge Sports Centre','Frome Road, Trowbridge, Wilts BA14 0DN','WU 2:30pm','Doors 2:15pm','Spectators £3 (Cash/Card)','Free Parking','2026-02-17 08:03:46'),(4,'Bath',1,'Bath Leisure Centre','North Parade Rd, Bathwick, Bath BA2 4ET','WU 5:00pm','Doors 4:30pm','Spectators £3 Cash','Paid Parking (Centre or Cricket Club)','2026-02-17 08:03:46'),(5,'City Of Bristol',1,'Hengrove Park Leisure Centre','Hengrove Promenade, Bristol BS14 0DE','Check with Host','Doors 2:15pm','Spectators £3 Cash/Card','Free Parking (3hrs with reg input)','2026-02-17 08:03:46'),(6,'Yeovil',2,'Sherbourne Sports Centre','Bradford Rd, Sherborne, Dorset DT9 3QN','WU 5:30pm','Doors 5:00pm','Spectators £3 Cash','No Coffee Pod available','2026-02-17 08:03:46'),(7,'Brockworth',2,'Leisure at Cheltenham','Tommy Taylors Ln, Cheltenham GL50 4RN','Check with Host','Doors 5:45pm','Check with Host','Check Centre Info','2026-02-17 08:03:46'),(8,'Swindon',2,'Health Hydro','Milton Rd, Swindon SN1 5JA','WU 1:30pm','Doors 1:15pm','Spectators £3','See local parking signs','2026-02-17 08:03:46'),(9,'Clevedon',2,'Hutton Moor Leisure Centre','Weston-super-Mare','Check with Host','Doors 6:15pm','Card Payment Preferred','Free Parking (Get permit from reception)','2026-02-17 08:03:46'),(10,'AST',2,'Burnham Swim & Sports Academy','Berrow Road, Burnham-on-Sea','15:00','Doors 3:00pm','Cash & Card','Hello','2026-02-17 11:01:59'),(11,'Dursley',3,'Keynsham Leisure Centre','Temple Street, BS31 1HE','Check with Host','Doors 12:30pm','Check with Host','Check Centre Info','2026-02-17 08:03:46'),(12,'Bridgwater',3,'Trinity Leisure Centre','Bridgwater','Check with Host','Doors 5:00pm','Check with Host','Free Onsite Parking','2026-02-17 08:03:46'),(13,'Burnham',3,'Millfield School','Street, BA16 0ST','Check with Host','Doors 6:00pm','Card Preferred / Cash Alt','Plenty of Free Parking','2026-02-17 08:03:46'),(14,'Wells',3,'Millfield School','Street, BA16 0ST','Check with Host','Doors 6:00pm','Card Preferred / Cash Alt','Plenty of Free Parking','2026-02-17 08:03:46'),(15,'Southwold',3,'Yate Leisure Centre','BS37 4DQ','Check with Host','Doors 6:00pm','Spectating Upstairs','Rear Car Park Free from 6pm Front car park (McDonald\'s side) free for 4 hours)','2026-02-20 17:51:58'),(16,'Monnow',4,'Newport Regional Pool','Spytty Blvd, Newport NP19 4RA','Check with Host','Doors 4:00pm','Cash Only','Free Parking outside','2026-02-17 08:03:46'),(17,'FOD',4,'GL1 Leisure Centre','GL1 1DT','Check with Host','Doors 5:00pm','Check with Host','Check Centre Info','2026-02-17 08:03:46'),(18,'Bristol North',4,'Keynsham Leisure Centre','Temple Street, BS31 1HE','12:30pm - 3:30pm','Doors 12:30pm','Check with Host','Check Centre Info','2026-02-17 08:03:46'),(19,'Newport',4,'Newport Regional Pool','Spytty Blvd, Newport NP19 4RA','Check with Host','Doors 4:00pm','Cash Only','Free Parking outside','2026-02-17 08:03:46'),(20,'Severnside',4,'GL1 Leisure Centre','GL1 1DT','Check with Host','Doors 5:00pm','Check with Host','Check Centre Info','2026-02-17 08:03:46');
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

-- Dump completed on 2026-03-06 23:30:08
