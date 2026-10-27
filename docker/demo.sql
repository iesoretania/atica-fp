-- MySQL dump 10.13  Distrib 8.4.0, for macos13.2 (arm64)
--
-- Host: 127.0.0.1    Database: fp_test
-- ------------------------------------------------------
-- Server version	8.2.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `answered_survey`
--

DROP TABLE IF EXISTS `answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_64D69900B3FE509D` (`survey_id`),
  CONSTRAINT `FK_64D69900B3FE509D` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answered_survey`
--

LOCK TABLES `answered_survey` WRITE;
/*!40000 ALTER TABLE `answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answered_survey_audit`
--

DROP TABLE IF EXISTS `answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `survey_id` int DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_b49bd09be566418816c3b13bd208621c_idx` (`rev`),
  CONSTRAINT `rev_b49bd09be566418816c3b13bd208621c_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answered_survey_audit`
--

LOCK TABLES `answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answered_survey_question`
--

DROP TABLE IF EXISTS `answered_survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answered_survey_question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `answered_survey_id` int NOT NULL,
  `survey_question_id` int NOT NULL,
  `text_value` longtext COLLATE utf8mb4_spanish_ci,
  `numeric_value` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F38ED8C7A6DF29BAA97283E6` (`survey_question_id`,`answered_survey_id`),
  KEY `IDX_F38ED8C7A97283E6` (`answered_survey_id`),
  KEY `IDX_F38ED8C7A6DF29BA` (`survey_question_id`),
  CONSTRAINT `FK_F38ED8C7A6DF29BA` FOREIGN KEY (`survey_question_id`) REFERENCES `survey_question` (`id`),
  CONSTRAINT `FK_F38ED8C7A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answered_survey_question`
--

LOCK TABLES `answered_survey_question` WRITE;
/*!40000 ALTER TABLE `answered_survey_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `answered_survey_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answered_survey_question_audit`
--

DROP TABLE IF EXISTS `answered_survey_question_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answered_survey_question_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `survey_question_id` int DEFAULT NULL,
  `text_value` longtext COLLATE utf8mb4_spanish_ci,
  `numeric_value` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_2e8f92797ef775558ce93daa1c47f1fd_idx` (`rev`),
  CONSTRAINT `rev_2e8f92797ef775558ce93daa1c47f1fd_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answered_survey_question_audit`
--

LOCK TABLES `answered_survey_question_audit` WRITE;
/*!40000 ALTER TABLE `answered_survey_question_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `answered_survey_question_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company` (
  `id` int NOT NULL AUTO_INCREMENT,
  `manager_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `web_site` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4FBF094F77153098` (`code`),
  KEY `IDX_4FBF094F783E3463` (`manager_id`),
  CONSTRAINT `FK_4FBF094F783E3463` FOREIGN KEY (`manager_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (1,NULL,'Tecnologías del Sur S.L.','B23000001','Calle Mayor, 1','Linares','23700','953000000',NULL,'b23000001@example.com',NULL),(2,NULL,'Consultoría del Norte S.A.','A23000002','Calle Mayor, 1','Úbeda','23400','953000000',NULL,'a23000002@example.com',NULL),(3,NULL,'Desarrollos de la Sierra S.L.','B23000003','Calle Mayor, 1','Baeza','23440','953000000',NULL,'b23000003@example.com',NULL);
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_audit`
--

DROP TABLE IF EXISTS `company_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `manager_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `web_site` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_3af728e1cf16bdb0f83bb90e3b1af48a_idx` (`rev`),
  CONSTRAINT `rev_3af728e1cf16bdb0f83bb90e3b1af48a_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_audit`
--

LOCK TABLES `company_audit` WRITE;
/*!40000 ALTER TABLE `company_audit` DISABLE KEYS */;
INSERT INTO `company_audit` VALUES (1,6,NULL,'Tecnologías del Sur S.L.','B23000001','Calle Mayor, 1','Linares','23700','953000000',NULL,'b23000001@example.com',NULL,'INS'),(2,6,NULL,'Consultoría del Norte S.A.','A23000002','Calle Mayor, 1','Úbeda','23400','953000000',NULL,'a23000002@example.com',NULL,'INS'),(3,6,NULL,'Desarrollos de la Sierra S.L.','B23000003','Calle Mayor, 1','Baeza','23440','953000000',NULL,'b23000003@example.com',NULL,'INS');
/*!40000 ALTER TABLE `company_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_academic_year`
--

DROP TABLE IF EXISTS `edu_academic_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_academic_year` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `principal_id` int DEFAULT NULL,
  `financial_manager_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `default_portrait_template_id` int DEFAULT NULL,
  `default_landscape_template_id` int DEFAULT NULL,
  `enabled_modules` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CFBE31D032C8A3DE` (`organization_id`),
  KEY `IDX_CFBE31D0474870EE` (`principal_id`),
  KEY `IDX_CFBE31D0FD5CC44A` (`financial_manager_id`),
  KEY `IDX_CFBE31D0FD003EB1` (`default_portrait_template_id`),
  KEY `IDX_CFBE31D07179DA55` (`default_landscape_template_id`),
  CONSTRAINT `FK_CFBE31D032C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`),
  CONSTRAINT `FK_CFBE31D0474870EE` FOREIGN KEY (`principal_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_CFBE31D07179DA55` FOREIGN KEY (`default_landscape_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_CFBE31D0FD003EB1` FOREIGN KEY (`default_portrait_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_CFBE31D0FD5CC44A` FOREIGN KEY (`financial_manager_id`) REFERENCES `edu_teacher` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_academic_year`
--

LOCK TABLES `edu_academic_year` WRITE;
/*!40000 ALTER TABLE `edu_academic_year` DISABLE KEYS */;
INSERT INTO `edu_academic_year` VALUES (1,1,2,3,'2026-2027','2026-09-01','2027-08-31',NULL,NULL,NULL),(2,2,7,8,'2026-2027','2026-09-01','2027-08-31',NULL,NULL,NULL);
/*!40000 ALTER TABLE `edu_academic_year` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_academic_year_audit`
--

DROP TABLE IF EXISTS `edu_academic_year_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_academic_year_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `organization_id` int DEFAULT NULL,
  `principal_id` int DEFAULT NULL,
  `financial_manager_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `default_portrait_template_id` int DEFAULT NULL,
  `default_landscape_template_id` int DEFAULT NULL,
  `enabled_modules` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_32b80cc3e6e41d58d2de465d160f9958_idx` (`rev`),
  CONSTRAINT `rev_32b80cc3e6e41d58d2de465d160f9958_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_academic_year_audit`
--

LOCK TABLES `edu_academic_year_audit` WRITE;
/*!40000 ALTER TABLE `edu_academic_year_audit` DISABLE KEYS */;
INSERT INTO `edu_academic_year_audit` VALUES (1,2,1,NULL,NULL,'2026-2027','2026-09-01','2027-08-31','INS',NULL,NULL,NULL),(1,7,1,2,3,'2026-2027','2026-09-01','2027-08-31','UPD',NULL,NULL,NULL),(2,4,2,NULL,NULL,'2026-2027','2026-09-01','2027-08-31','INS',NULL,NULL,NULL),(2,7,2,7,8,'2026-2027','2026-09-01','2027-08-31','UPD',NULL,NULL,NULL);
/*!40000 ALTER TABLE `edu_academic_year_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_competency`
--

DROP TABLE IF EXISTS `edu_competency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_competency` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_237FAB0BBEFD98D177153098` (`training_id`,`code`),
  KEY `IDX_237FAB0BBEFD98D1` (`training_id`),
  CONSTRAINT `FK_237FAB0BBEFD98D1` FOREIGN KEY (`training_id`) REFERENCES `edu_training` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_competency`
--

LOCK TABLES `edu_competency` WRITE;
/*!40000 ALTER TABLE `edu_competency` DISABLE KEYS */;
INSERT INTO `edu_competency` VALUES (1,1,'CG1','Configurar y explotar sistemas informáticos'),(2,2,'CG1','Configurar y explotar sistemas informáticos');
/*!40000 ALTER TABLE `edu_competency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_contact_method`
--

DROP TABLE IF EXISTS `edu_contact_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_contact_method` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4992BBDC54F3401` (`academic_year_id`),
  CONSTRAINT `FK_4992BBDC54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_contact_method`
--

LOCK TABLES `edu_contact_method` WRITE;
/*!40000 ALTER TABLE `edu_contact_method` DISABLE KEYS */;
INSERT INTO `edu_contact_method` VALUES (1,1,'Teléfono',1),(2,1,'Correo electrónico',1),(3,2,'Teléfono',1),(4,2,'Correo electrónico',1);
/*!40000 ALTER TABLE `edu_contact_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_contact_method_audit`
--

DROP TABLE IF EXISTS `edu_contact_method_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_contact_method_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `academic_year_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_f0caa067d32baecb08ec4f3fc820d0a6_idx` (`rev`),
  CONSTRAINT `rev_f0caa067d32baecb08ec4f3fc820d0a6_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_contact_method_audit`
--

LOCK TABLES `edu_contact_method_audit` WRITE;
/*!40000 ALTER TABLE `edu_contact_method_audit` DISABLE KEYS */;
INSERT INTO `edu_contact_method_audit` VALUES (1,7,1,'Teléfono','INS',1),(2,7,1,'Correo electrónico','INS',1),(3,7,2,'Teléfono','INS',1),(4,7,2,'Correo electrónico','INS',1);
/*!40000 ALTER TABLE `edu_contact_method_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_criterion`
--

DROP TABLE IF EXISTS `edu_criterion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_criterion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `learning_outcome_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `name` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_6A1D6D1335C2B2D5` (`learning_outcome_id`),
  CONSTRAINT `FK_6A1D6D1335C2B2D5` FOREIGN KEY (`learning_outcome_id`) REFERENCES `edu_learning_outcome` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_criterion`
--

LOCK TABLES `edu_criterion` WRITE;
/*!40000 ALTER TABLE `edu_criterion` DISABLE KEYS */;
INSERT INTO `edu_criterion` VALUES (1,1,'a','Criterio a','Criterio de evaluación de Programación'),(2,2,'a','Criterio a','Criterio de evaluación de Bases de datos'),(3,3,'a','Criterio a','Criterio de evaluación de Proyecto intermodular'),(4,4,'a','Criterio a','Criterio de evaluación de Programación'),(5,5,'a','Criterio a','Criterio de evaluación de Bases de datos'),(6,6,'a','Criterio a','Criterio de evaluación de Proyecto intermodular');
/*!40000 ALTER TABLE `edu_criterion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_department`
--

DROP TABLE IF EXISTS `edu_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_department` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `head_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6EB77EB1C54F3401` (`academic_year_id`),
  KEY `IDX_6EB77EB1F41A619E` (`head_id`),
  CONSTRAINT `FK_6EB77EB1C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`),
  CONSTRAINT `FK_6EB77EB1F41A619E` FOREIGN KEY (`head_id`) REFERENCES `edu_teacher` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_department`
--

LOCK TABLES `edu_department` WRITE;
/*!40000 ALTER TABLE `edu_department` DISABLE KEYS */;
INSERT INTO `edu_department` VALUES (1,1,1,'Informática','INF'),(2,1,NULL,'Administración','ADM'),(3,2,6,'Informática','INF'),(4,2,NULL,'Administración','ADM');
/*!40000 ALTER TABLE `edu_department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_grade`
--

DROP TABLE IF EXISTS `edu_grade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_grade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_id` int NOT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_78AC6283BEFD98D1` (`training_id`),
  CONSTRAINT `FK_78AC6283BEFD98D1` FOREIGN KEY (`training_id`) REFERENCES `edu_training` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_grade`
--

LOCK TABLES `edu_grade` WRITE;
/*!40000 ALTER TABLE `edu_grade` DISABLE KEYS */;
INSERT INTO `edu_grade` VALUES (1,1,'1DAM','1º DAM'),(2,1,'2DAM','2º DAM'),(3,2,'1DAM','1º DAM'),(4,2,'2DAM','2º DAM');
/*!40000 ALTER TABLE `edu_grade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_group`
--

DROP TABLE IF EXISTS `edu_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grade_id` int NOT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4C368872FE19A1A8` (`grade_id`),
  CONSTRAINT `FK_4C368872FE19A1A8` FOREIGN KEY (`grade_id`) REFERENCES `edu_grade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_group`
--

LOCK TABLES `edu_group` WRITE;
/*!40000 ALTER TABLE `edu_group` DISABLE KEYS */;
INSERT INTO `edu_group` VALUES (1,1,'1DAM-A','1º DAM A'),(2,2,'2DAM-A','2º DAM A'),(3,3,'1DAM-A','1º DAM A'),(4,4,'2DAM-A','2º DAM A');
/*!40000 ALTER TABLE `edu_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_group_tutor`
--

DROP TABLE IF EXISTS `edu_group_tutor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_group_tutor` (
  `group_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  PRIMARY KEY (`group_id`,`teacher_id`),
  KEY `IDX_78832E94FE54D947` (`group_id`),
  KEY `IDX_78832E9441807E1D` (`teacher_id`),
  CONSTRAINT `FK_78832E9441807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_78832E94FE54D947` FOREIGN KEY (`group_id`) REFERENCES `edu_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_group_tutor`
--

LOCK TABLES `edu_group_tutor` WRITE;
/*!40000 ALTER TABLE `edu_group_tutor` DISABLE KEYS */;
INSERT INTO `edu_group_tutor` VALUES (2,4),(4,9);
/*!40000 ALTER TABLE `edu_group_tutor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_learning_outcome`
--

DROP TABLE IF EXISTS `edu_learning_outcome`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_learning_outcome` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_301888C623EDC8777153098` (`subject_id`,`code`),
  KEY `IDX_301888C623EDC87` (`subject_id`),
  CONSTRAINT `FK_301888C623EDC87` FOREIGN KEY (`subject_id`) REFERENCES `edu_subject` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_learning_outcome`
--

LOCK TABLES `edu_learning_outcome` WRITE;
/*!40000 ALTER TABLE `edu_learning_outcome` DISABLE KEYS */;
INSERT INTO `edu_learning_outcome` VALUES (1,1,'RA1','Resultado de aprendizaje de Programación'),(2,2,'RA1','Resultado de aprendizaje de Bases de datos'),(3,3,'RA1','Resultado de aprendizaje de Proyecto intermodular'),(4,4,'RA1','Resultado de aprendizaje de Programación'),(5,5,'RA1','Resultado de aprendizaje de Bases de datos'),(6,6,'RA1','Resultado de aprendizaje de Proyecto intermodular');
/*!40000 ALTER TABLE `edu_learning_outcome` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_non_working_day`
--

DROP TABLE IF EXISTS `edu_non_working_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_non_working_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_505655C0C54F3401AA9E377A` (`academic_year_id`,`date`),
  KEY `IDX_505655C0C54F3401` (`academic_year_id`),
  CONSTRAINT `FK_505655C0C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_non_working_day`
--

LOCK TABLES `edu_non_working_day` WRITE;
/*!40000 ALTER TABLE `edu_non_working_day` DISABLE KEYS */;
INSERT INTO `edu_non_working_day` VALUES (1,1,'2026-10-11','Día de la Hispanidad'),(2,1,'2026-12-25','Navidad'),(3,2,'2026-10-11','Día de la Hispanidad'),(4,2,'2026-12-25','Navidad');
/*!40000 ALTER TABLE `edu_non_working_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_performance_scale`
--

DROP TABLE IF EXISTS `edu_performance_scale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_performance_scale` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_97FBF6F932C8A3DE` (`organization_id`),
  CONSTRAINT `FK_97FBF6F932C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_performance_scale`
--

LOCK TABLES `edu_performance_scale` WRITE;
/*!40000 ALTER TABLE `edu_performance_scale` DISABLE KEYS */;
INSERT INTO `edu_performance_scale` VALUES (1,1,'Escala de desempeño',1),(2,2,'Escala de desempeño',1);
/*!40000 ALTER TABLE `edu_performance_scale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_performance_scale_audit`
--

DROP TABLE IF EXISTS `edu_performance_scale_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_performance_scale_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `organization_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_872a21aa478051cdedd389213a503b28_idx` (`rev`),
  CONSTRAINT `rev_872a21aa478051cdedd389213a503b28_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_performance_scale_audit`
--

LOCK TABLES `edu_performance_scale_audit` WRITE;
/*!40000 ALTER TABLE `edu_performance_scale_audit` DISABLE KEYS */;
INSERT INTO `edu_performance_scale_audit` VALUES (1,7,1,'Escala de desempeño',1,'INS'),(2,7,2,'Escala de desempeño',1,'INS');
/*!40000 ALTER TABLE `edu_performance_scale_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_performance_scale_value`
--

DROP TABLE IF EXISTS `edu_performance_scale_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_performance_scale_value` (
  `id` int NOT NULL AUTO_INCREMENT,
  `performance_scale_id` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numeric_grade` int NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `negative_grade` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CB9D66577C2C26C0` (`performance_scale_id`),
  CONSTRAINT `FK_CB9D66577C2C26C0` FOREIGN KEY (`performance_scale_id`) REFERENCES `edu_performance_scale` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_performance_scale_value`
--

LOCK TABLES `edu_performance_scale_value` WRITE;
/*!40000 ALTER TABLE `edu_performance_scale_value` DISABLE KEYS */;
INSERT INTO `edu_performance_scale_value` VALUES (1,1,'Insuficiente',0,NULL,1),(2,1,'Suficiente',1,NULL,0),(3,1,'Notable',2,NULL,0),(4,2,'Insuficiente',0,NULL,1),(5,2,'Suficiente',1,NULL,0),(6,2,'Notable',2,NULL,0);
/*!40000 ALTER TABLE `edu_performance_scale_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_performance_scale_value_audit`
--

DROP TABLE IF EXISTS `edu_performance_scale_value_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_performance_scale_value_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `performance_scale_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numeric_grade` int DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `negative_grade` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_251f0c8c6a4a618acff9d2ea364b6c7d_idx` (`rev`),
  CONSTRAINT `rev_251f0c8c6a4a618acff9d2ea364b6c7d_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_performance_scale_value_audit`
--

LOCK TABLES `edu_performance_scale_value_audit` WRITE;
/*!40000 ALTER TABLE `edu_performance_scale_value_audit` DISABLE KEYS */;
INSERT INTO `edu_performance_scale_value_audit` VALUES (1,7,1,'Insuficiente',0,NULL,'INS',1),(2,7,1,'Suficiente',1,NULL,'INS',0),(3,7,1,'Notable',2,NULL,'INS',0),(4,7,2,'Insuficiente',0,NULL,'INS',1),(5,7,2,'Suficiente',1,NULL,'INS',0),(6,7,2,'Notable',2,NULL,'INS',0);
/*!40000 ALTER TABLE `edu_performance_scale_value_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_report_template`
--

DROP TABLE IF EXISTS `edu_report_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_report_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `data` longblob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F517C90732C8A3DE` (`organization_id`),
  CONSTRAINT `FK_F517C90732C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_report_template`
--

LOCK TABLES `edu_report_template` WRITE;
/*!40000 ALTER TABLE `edu_report_template` DISABLE KEYS */;
INSERT INTO `edu_report_template` VALUES (1,1,'Plantilla de informe de prueba',_binary '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n'),(2,2,'Plantilla de informe de prueba',_binary '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
/*!40000 ALTER TABLE `edu_report_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_student_enrollment`
--

DROP TABLE IF EXISTS `edu_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_student_enrollment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_id` int NOT NULL,
  `group_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7824A430217BBB47FE54D947` (`person_id`,`group_id`),
  KEY `IDX_7824A430217BBB47` (`person_id`),
  KEY `IDX_7824A430FE54D947` (`group_id`),
  CONSTRAINT `FK_7824A430217BBB47` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_7824A430FE54D947` FOREIGN KEY (`group_id`) REFERENCES `edu_group` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_student_enrollment`
--

LOCK TABLES `edu_student_enrollment` WRITE;
/*!40000 ALTER TABLE `edu_student_enrollment` DISABLE KEYS */;
INSERT INTO `edu_student_enrollment` VALUES (1,9,2),(2,10,2),(3,11,2),(4,12,1),(5,13,2),(6,14,2),(7,15,2),(8,16,2),(9,22,4),(10,23,4),(11,24,4),(12,25,3),(13,26,4),(14,27,4),(15,28,4),(16,29,4);
/*!40000 ALTER TABLE `edu_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_subject`
--

DROP TABLE IF EXISTS `edu_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_subject` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grade_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C298A968FE19A1A8` (`grade_id`),
  CONSTRAINT `FK_C298A968FE19A1A8` FOREIGN KEY (`grade_id`) REFERENCES `edu_grade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_subject`
--

LOCK TABLES `edu_subject` WRITE;
/*!40000 ALTER TABLE `edu_subject` DISABLE KEYS */;
INSERT INTO `edu_subject` VALUES (1,1,'PRG','PRG','Programación'),(2,1,'BBD','BBD','Bases de datos'),(3,2,'PIN','PIN','Proyecto intermodular'),(4,3,'PRG','PRG','Programación'),(5,3,'BBD','BBD','Bases de datos'),(6,4,'PIN','PIN','Proyecto intermodular');
/*!40000 ALTER TABLE `edu_subject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_teacher`
--

DROP TABLE IF EXISTS `edu_teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_teacher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_89A031C7217BBB47C54F3401` (`person_id`,`academic_year_id`),
  KEY `IDX_89A031C7217BBB47` (`person_id`),
  KEY `IDX_89A031C7C54F3401` (`academic_year_id`),
  KEY `IDX_89A031C7AE80F5DF` (`department_id`),
  CONSTRAINT `FK_89A031C7217BBB47` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_89A031C7AE80F5DF` FOREIGN KEY (`department_id`) REFERENCES `edu_department` (`id`),
  CONSTRAINT `FK_89A031C7C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_teacher`
--

LOCK TABLES `edu_teacher` WRITE;
/*!40000 ALTER TABLE `edu_teacher` DISABLE KEYS */;
INSERT INTO `edu_teacher` VALUES (1,4,1,1),(2,5,1,2),(3,6,1,2),(4,7,1,1),(5,8,1,1),(6,17,2,3),(7,18,2,4),(8,19,2,4),(9,20,2,3),(10,21,2,3),(11,34,1,1),(12,35,2,3),(13,36,1,1),(14,37,2,3);
/*!40000 ALTER TABLE `edu_teacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_teaching`
--

DROP TABLE IF EXISTS `edu_teaching`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_teaching` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `group_id` int NOT NULL,
  `subject_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_EB30486D41807E1DFE54D94723EDC87` (`teacher_id`,`group_id`,`subject_id`),
  KEY `IDX_EB30486D41807E1D` (`teacher_id`),
  KEY `IDX_EB30486DFE54D947` (`group_id`),
  KEY `IDX_EB30486D23EDC87` (`subject_id`),
  CONSTRAINT `FK_EB30486D23EDC87` FOREIGN KEY (`subject_id`) REFERENCES `edu_subject` (`id`),
  CONSTRAINT `FK_EB30486D41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_EB30486DFE54D947` FOREIGN KEY (`group_id`) REFERENCES `edu_group` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_teaching`
--

LOCK TABLES `edu_teaching` WRITE;
/*!40000 ALTER TABLE `edu_teaching` DISABLE KEYS */;
INSERT INTO `edu_teaching` VALUES (2,4,1,2),(3,4,2,3),(1,5,1,1),(5,9,3,5),(6,9,4,6),(4,10,3,4);
/*!40000 ALTER TABLE `edu_teaching` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_training`
--

DROP TABLE IF EXISTS `edu_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_training` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2692AD50C54F3401` (`academic_year_id`),
  KEY `IDX_2692AD50AE80F5DF` (`department_id`),
  CONSTRAINT `FK_2692AD50AE80F5DF` FOREIGN KEY (`department_id`) REFERENCES `edu_department` (`id`),
  CONSTRAINT `FK_2692AD50C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_training`
--

LOCK TABLES `edu_training` WRITE;
/*!40000 ALTER TABLE `edu_training` DISABLE KEYS */;
INSERT INTO `edu_training` VALUES (1,1,1,'DAM','CFGS Desarrollo de Aplicaciones Multiplataforma'),(2,2,3,'DAM','CFGS Desarrollo de Aplicaciones Multiplataforma');
/*!40000 ALTER TABLE `edu_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edu_travel_route`
--

DROP TABLE IF EXISTS `edu_travel_route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edu_travel_route` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `distance` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F66A658832C8A3DE` (`organization_id`),
  CONSTRAINT `FK_F66A658832C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edu_travel_route`
--

LOCK TABLES `edu_travel_route` WRITE;
/*!40000 ALTER TABLE `edu_travel_route` DISABLE KEYS */;
INSERT INTO `edu_travel_route` VALUES (1,1,'Linares - Jaén',1,52),(2,1,'Linares - Úbeda',1,28),(3,2,'Linares - Jaén',1,52),(4,2,'Linares - Úbeda',1,28);
/*!40000 ALTER TABLE `edu_travel_route` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_log`
--

DROP TABLE IF EXISTS `event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `ip` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `data` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `data_attachment` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_9EF0AD16A76ED395` (`user_id`),
  CONSTRAINT `FK_9EF0AD16A76ED395` FOREIGN KEY (`user_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_log`
--

LOCK TABLES `event_log` WRITE;
/*!40000 ALTER TABLE `event_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_activity`
--

DROP TABLE IF EXISTS `itp_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_grade_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `itp_activity_code_unique` (`code`,`program_grade_id`),
  KEY `IDX_12216C8B7F2C5D9D` (`program_grade_id`),
  CONSTRAINT `FK_12216C8B7F2C5D9D` FOREIGN KEY (`program_grade_id`) REFERENCES `itp_program_grade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_activity`
--

LOCK TABLES `itp_activity` WRITE;
/*!40000 ALTER TABLE `itp_activity` DISABLE KEYS */;
INSERT INTO `itp_activity` VALUES (1,1,'FFE1','Soporte técnico','Actividad de Soporte técnico'),(2,1,'FFE2','Desarrollo de software','Actividad de Desarrollo de software'),(3,2,'FFE1','Soporte técnico','Actividad de Soporte técnico'),(4,2,'FFE2','Desarrollo de software','Actividad de Desarrollo de software');
/*!40000 ALTER TABLE `itp_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_activity_audit`
--

DROP TABLE IF EXISTS `itp_activity_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_activity_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `program_grade_id` int DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_ec81cce9357b10e123ad7a8a61086606_idx` (`rev`),
  CONSTRAINT `rev_ec81cce9357b10e123ad7a8a61086606_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_activity_audit`
--

LOCK TABLES `itp_activity_audit` WRITE;
/*!40000 ALTER TABLE `itp_activity_audit` DISABLE KEYS */;
INSERT INTO `itp_activity_audit` VALUES (1,10,1,'FFE1','Soporte técnico','Actividad de Soporte técnico','INS'),(2,10,1,'FFE2','Desarrollo de software','Actividad de Desarrollo de software','INS'),(3,10,2,'FFE1','Soporte técnico','Actividad de Soporte técnico','INS'),(4,10,2,'FFE2','Desarrollo de software','Actividad de Desarrollo de software','INS');
/*!40000 ALTER TABLE `itp_activity_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_activity_criterion`
--

DROP TABLE IF EXISTS `itp_activity_criterion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_activity_criterion` (
  `activity_id` int NOT NULL,
  `criterion_id` int NOT NULL,
  PRIMARY KEY (`activity_id`,`criterion_id`),
  KEY `IDX_5031EC0581C06096` (`activity_id`),
  KEY `IDX_5031EC0597766307` (`criterion_id`),
  CONSTRAINT `FK_5031EC0581C06096` FOREIGN KEY (`activity_id`) REFERENCES `itp_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5031EC0597766307` FOREIGN KEY (`criterion_id`) REFERENCES `edu_criterion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_activity_criterion`
--

LOCK TABLES `itp_activity_criterion` WRITE;
/*!40000 ALTER TABLE `itp_activity_criterion` DISABLE KEYS */;
INSERT INTO `itp_activity_criterion` VALUES (1,3),(2,3),(3,6),(4,6);
/*!40000 ALTER TABLE `itp_activity_criterion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_activity_criterion_audit`
--

DROP TABLE IF EXISTS `itp_activity_criterion_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_activity_criterion_audit` (
  `activity_id` int NOT NULL,
  `criterion_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`activity_id`,`criterion_id`,`rev`),
  KEY `rev_84340deadd947925141c5ccb0e2175c4_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_activity_criterion_audit`
--

LOCK TABLES `itp_activity_criterion_audit` WRITE;
/*!40000 ALTER TABLE `itp_activity_criterion_audit` DISABLE KEYS */;
INSERT INTO `itp_activity_criterion_audit` VALUES (1,3,10,'INS'),(2,3,10,'INS'),(3,6,10,'INS'),(4,6,10,'INS');
/*!40000 ALTER TABLE `itp_activity_criterion_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_company_program`
--

DROP TABLE IF EXISTS `itp_company_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_company_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_grade_id` int NOT NULL,
  `company_id` int NOT NULL,
  `agreement_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monitoring_instruments` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_program_unique` (`program_grade_id`,`company_id`),
  KEY `IDX_EA6C82597F2C5D9D` (`program_grade_id`),
  KEY `IDX_EA6C8259979B1AD6` (`company_id`),
  CONSTRAINT `FK_EA6C82597F2C5D9D` FOREIGN KEY (`program_grade_id`) REFERENCES `itp_program_grade` (`id`),
  CONSTRAINT `FK_EA6C8259979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_company_program`
--

LOCK TABLES `itp_company_program` WRITE;
/*!40000 ALTER TABLE `itp_company_program` DISABLE KEYS */;
INSERT INTO `itp_company_program` VALUES (1,1,1,'FFE-001','Cuaderno de seguimiento'),(2,2,1,'FFE-001','Cuaderno de seguimiento');
/*!40000 ALTER TABLE `itp_company_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_company_program_activity`
--

DROP TABLE IF EXISTS `itp_company_program_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_company_program_activity` (
  `company_program_id` int NOT NULL,
  `activity_id` int NOT NULL,
  PRIMARY KEY (`company_program_id`,`activity_id`),
  KEY `IDX_B69227C58336EE9C` (`company_program_id`),
  KEY `IDX_B69227C581C06096` (`activity_id`),
  CONSTRAINT `FK_B69227C581C06096` FOREIGN KEY (`activity_id`) REFERENCES `itp_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B69227C58336EE9C` FOREIGN KEY (`company_program_id`) REFERENCES `itp_company_program` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_company_program_activity`
--

LOCK TABLES `itp_company_program_activity` WRITE;
/*!40000 ALTER TABLE `itp_company_program_activity` DISABLE KEYS */;
INSERT INTO `itp_company_program_activity` VALUES (1,1),(1,2),(2,3),(2,4);
/*!40000 ALTER TABLE `itp_company_program_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_company_program_activity_audit`
--

DROP TABLE IF EXISTS `itp_company_program_activity_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_company_program_activity_audit` (
  `company_program_id` int NOT NULL,
  `activity_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`company_program_id`,`activity_id`,`rev`),
  KEY `rev_16dda1ed93f1412f09bd9e0332001f79_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_company_program_activity_audit`
--

LOCK TABLES `itp_company_program_activity_audit` WRITE;
/*!40000 ALTER TABLE `itp_company_program_activity_audit` DISABLE KEYS */;
INSERT INTO `itp_company_program_activity_audit` VALUES (1,1,10,'INS'),(1,2,10,'INS'),(2,3,10,'INS'),(2,4,10,'INS');
/*!40000 ALTER TABLE `itp_company_program_activity_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_company_program_audit`
--

DROP TABLE IF EXISTS `itp_company_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_company_program_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `program_grade_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `agreement_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monitoring_instruments` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_2fa1ce202ffe5764db7e79671701410e_idx` (`rev`),
  CONSTRAINT `rev_2fa1ce202ffe5764db7e79671701410e_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_company_program_audit`
--

LOCK TABLES `itp_company_program_audit` WRITE;
/*!40000 ALTER TABLE `itp_company_program_audit` DISABLE KEYS */;
INSERT INTO `itp_company_program_audit` VALUES (1,10,1,1,'FFE-001','Cuaderno de seguimiento','INS'),(2,10,2,1,'FFE-001','Cuaderno de seguimiento','INS');
/*!40000 ALTER TABLE `itp_company_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact`
--

DROP TABLE IF EXISTS `itp_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `workcenter_id` int NOT NULL,
  `method_id` int DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `detail` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_1217274A41807E1D` (`teacher_id`),
  KEY `IDX_1217274AA2473C4B` (`workcenter_id`),
  KEY `IDX_1217274A19883967` (`method_id`),
  CONSTRAINT `FK_1217274A19883967` FOREIGN KEY (`method_id`) REFERENCES `edu_contact_method` (`id`),
  CONSTRAINT `FK_1217274A41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_1217274AA2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact`
--

LOCK TABLES `itp_contact` WRITE;
/*!40000 ALTER TABLE `itp_contact` DISABLE KEYS */;
INSERT INTO `itp_contact` VALUES (1,5,1,1,'2026-10-15 11:00:00','<p>Visita de seguimiento de la <b>FFE</b>.</p>'),(2,10,1,3,'2026-10-15 11:00:00','<p>Visita de seguimiento de la <b>FFE</b>.</p>');
/*!40000 ALTER TABLE `itp_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact_audit`
--

DROP TABLE IF EXISTS `itp_contact_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `workcenter_id` int DEFAULT NULL,
  `method_id` int DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `detail` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_f7bd140f4ac13ef88636d1bbd6394d2d_idx` (`rev`),
  CONSTRAINT `rev_f7bd140f4ac13ef88636d1bbd6394d2d_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact_audit`
--

LOCK TABLES `itp_contact_audit` WRITE;
/*!40000 ALTER TABLE `itp_contact_audit` DISABLE KEYS */;
INSERT INTO `itp_contact_audit` VALUES (1,10,5,1,1,'2026-10-15 11:00:00','<p>Visita de seguimiento de la <b>FFE</b>.</p>','INS'),(2,10,10,1,3,'2026-10-15 11:00:00','<p>Visita de seguimiento de la <b>FFE</b>.</p>','INS');
/*!40000 ALTER TABLE `itp_contact_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact_student_enrollment`
--

DROP TABLE IF EXISTS `itp_contact_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact_student_enrollment` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`),
  KEY `IDX_4CF4C60DE7A1254A` (`contact_id`),
  KEY `IDX_4CF4C60DDAE14AC5` (`student_enrollment_id`),
  CONSTRAINT `FK_4CF4C60DDAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_4CF4C60DE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `itp_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact_student_enrollment`
--

LOCK TABLES `itp_contact_student_enrollment` WRITE;
/*!40000 ALTER TABLE `itp_contact_student_enrollment` DISABLE KEYS */;
INSERT INTO `itp_contact_student_enrollment` VALUES (1,7),(2,15);
/*!40000 ALTER TABLE `itp_contact_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact_student_enrollment_audit`
--

DROP TABLE IF EXISTS `itp_contact_student_enrollment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact_student_enrollment_audit` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`,`rev`),
  KEY `rev_c63c374aec0e3b506b7573a2dac45d48_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact_student_enrollment_audit`
--

LOCK TABLES `itp_contact_student_enrollment_audit` WRITE;
/*!40000 ALTER TABLE `itp_contact_student_enrollment_audit` DISABLE KEYS */;
INSERT INTO `itp_contact_student_enrollment_audit` VALUES (1,7,10,'INS'),(2,15,10,'INS');
/*!40000 ALTER TABLE `itp_contact_student_enrollment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact_training_program`
--

DROP TABLE IF EXISTS `itp_contact_training_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact_training_program` (
  `contact_id` int NOT NULL,
  `training_program_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`training_program_id`),
  KEY `IDX_3B01C30FE7A1254A` (`contact_id`),
  KEY `IDX_3B01C30F8406BD6C` (`training_program_id`),
  CONSTRAINT `FK_3B01C30F8406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_3B01C30FE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `itp_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact_training_program`
--

LOCK TABLES `itp_contact_training_program` WRITE;
/*!40000 ALTER TABLE `itp_contact_training_program` DISABLE KEYS */;
INSERT INTO `itp_contact_training_program` VALUES (1,1),(2,2);
/*!40000 ALTER TABLE `itp_contact_training_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_contact_training_program_audit`
--

DROP TABLE IF EXISTS `itp_contact_training_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_contact_training_program_audit` (
  `contact_id` int NOT NULL,
  `training_program_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`training_program_id`,`rev`),
  KEY `rev_75bfc426b9bd672949b090d7f5cc54d8_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_contact_training_program_audit`
--

LOCK TABLES `itp_contact_training_program_audit` WRITE;
/*!40000 ALTER TABLE `itp_contact_training_program_audit` DISABLE KEYS */;
INSERT INTO `itp_contact_training_program_audit` VALUES (1,1,10,'INS'),(2,2,10,'INS');
/*!40000 ALTER TABLE `itp_contact_training_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_educational_tutor_answered_survey`
--

DROP TABLE IF EXISTS `itp_educational_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_educational_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_program_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D8F214A68406BD6C` (`training_program_id`),
  KEY `IDX_D8F214A641807E1D` (`teacher_id`),
  KEY `IDX_D8F214A6A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_D8F214A641807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_D8F214A68406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`),
  CONSTRAINT `FK_D8F214A6A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_educational_tutor_answered_survey`
--

LOCK TABLES `itp_educational_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `itp_educational_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_educational_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_educational_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `itp_educational_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_educational_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `training_program_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_38efae102c3fd89ad405521f3b727132_idx` (`rev`),
  CONSTRAINT `rev_38efae102c3fd89ad405521f3b727132_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_educational_tutor_answered_survey_audit`
--

LOCK TABLES `itp_educational_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `itp_educational_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_educational_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_grade`
--

DROP TABLE IF EXISTS `itp_program_grade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_grade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_program_id` int NOT NULL,
  `grade_id` int NOT NULL,
  `target_hours` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6F6FE3778406BD6C` (`training_program_id`),
  KEY `IDX_6F6FE377FE19A1A8` (`grade_id`),
  CONSTRAINT `FK_6F6FE3778406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`),
  CONSTRAINT `FK_6F6FE377FE19A1A8` FOREIGN KEY (`grade_id`) REFERENCES `edu_grade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_grade`
--

LOCK TABLES `itp_program_grade` WRITE;
/*!40000 ALTER TABLE `itp_program_grade` DISABLE KEYS */;
INSERT INTO `itp_program_grade` VALUES (1,1,2,10000),(2,2,4,10000);
/*!40000 ALTER TABLE `itp_program_grade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_grade_audit`
--

DROP TABLE IF EXISTS `itp_program_grade_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_grade_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `training_program_id` int DEFAULT NULL,
  `grade_id` int DEFAULT NULL,
  `target_hours` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_77fa13b589b0147f9bba81f01535097c_idx` (`rev`),
  CONSTRAINT `rev_77fa13b589b0147f9bba81f01535097c_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_grade_audit`
--

LOCK TABLES `itp_program_grade_audit` WRITE;
/*!40000 ALTER TABLE `itp_program_grade_audit` DISABLE KEYS */;
INSERT INTO `itp_program_grade_audit` VALUES (1,10,1,2,10000,'INS'),(2,10,2,4,10000,'INS');
/*!40000 ALTER TABLE `itp_program_grade_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_group`
--

DROP TABLE IF EXISTS `itp_program_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_grade_id` int NOT NULL,
  `group_id` int NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `target_hours` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5BF509867F2C5D9D` (`program_grade_id`),
  KEY `IDX_5BF50986FE54D947` (`group_id`),
  CONSTRAINT `FK_5BF509867F2C5D9D` FOREIGN KEY (`program_grade_id`) REFERENCES `itp_program_grade` (`id`),
  CONSTRAINT `FK_5BF50986FE54D947` FOREIGN KEY (`group_id`) REFERENCES `edu_group` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_group`
--

LOCK TABLES `itp_program_group` WRITE;
/*!40000 ALTER TABLE `itp_program_group` DISABLE KEYS */;
INSERT INTO `itp_program_group` VALUES (1,1,2,0,'Grupo de FFE',10000),(2,2,4,0,'Grupo de FFE',10000);
/*!40000 ALTER TABLE `itp_program_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_group_audit`
--

DROP TABLE IF EXISTS `itp_program_group_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_group_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `program_grade_id` int DEFAULT NULL,
  `group_id` int DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `target_hours` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_1bbc384c45dccae2d1b5f0fa90bcce44_idx` (`rev`),
  CONSTRAINT `rev_1bbc384c45dccae2d1b5f0fa90bcce44_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_group_audit`
--

LOCK TABLES `itp_program_group_audit` WRITE;
/*!40000 ALTER TABLE `itp_program_group_audit` DISABLE KEYS */;
INSERT INTO `itp_program_group_audit` VALUES (1,10,1,2,0,'Grupo de FFE',10000,'INS'),(2,10,2,4,0,'Grupo de FFE',10000,'INS');
/*!40000 ALTER TABLE `itp_program_group_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_group_manager`
--

DROP TABLE IF EXISTS `itp_program_group_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_group_manager` (
  `program_group_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  PRIMARY KEY (`program_group_id`,`teacher_id`),
  KEY `IDX_843C7B3A7F612572` (`program_group_id`),
  KEY `IDX_843C7B3A41807E1D` (`teacher_id`),
  CONSTRAINT `FK_843C7B3A41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_843C7B3A7F612572` FOREIGN KEY (`program_group_id`) REFERENCES `itp_program_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_group_manager`
--

LOCK TABLES `itp_program_group_manager` WRITE;
/*!40000 ALTER TABLE `itp_program_group_manager` DISABLE KEYS */;
INSERT INTO `itp_program_group_manager` VALUES (1,13),(2,14);
/*!40000 ALTER TABLE `itp_program_group_manager` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_program_group_manager_audit`
--

DROP TABLE IF EXISTS `itp_program_group_manager_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_program_group_manager_audit` (
  `program_group_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`program_group_id`,`teacher_id`,`rev`),
  KEY `rev_1bbe35b53cfc174c39ddb9083c090a95_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_program_group_manager_audit`
--

LOCK TABLES `itp_program_group_manager_audit` WRITE;
/*!40000 ALTER TABLE `itp_program_group_manager_audit` DISABLE KEYS */;
INSERT INTO `itp_program_group_manager_audit` VALUES (1,13,10,'INS'),(2,14,10,'INS');
/*!40000 ALTER TABLE `itp_program_group_manager_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_specific_training`
--

DROP TABLE IF EXISTS `itp_specific_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_specific_training` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_program_id` int NOT NULL,
  `company_id` int DEFAULT NULL,
  `hours` int NOT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_company_data` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_28631CA88406BD6C` (`training_program_id`),
  KEY `IDX_28631CA8979B1AD6` (`company_id`),
  CONSTRAINT `FK_28631CA88406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`),
  CONSTRAINT `FK_28631CA8979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_specific_training`
--

LOCK TABLES `itp_specific_training` WRITE;
/*!40000 ALTER TABLE `itp_specific_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_specific_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_specific_training_audit`
--

DROP TABLE IF EXISTS `itp_specific_training_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_specific_training_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `training_program_id` int DEFAULT NULL,
  `company_id` int DEFAULT NULL,
  `hours` int DEFAULT NULL,
  `name` longtext COLLATE utf8mb4_unicode_ci,
  `additional_company_data` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_954fda7ced28fa7da330bb57d44d6bae_idx` (`rev`),
  CONSTRAINT `rev_954fda7ced28fa7da330bb57d44d6bae_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_specific_training_audit`
--

LOCK TABLES `itp_specific_training_audit` WRITE;
/*!40000 ALTER TABLE `itp_specific_training_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_specific_training_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_answered_survey`
--

DROP TABLE IF EXISTS `itp_student_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_program_workcenter_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C093319BF0C2682D` (`student_program_workcenter_id`),
  KEY `IDX_C093319BDAE14AC5` (`student_enrollment_id`),
  KEY `IDX_C093319BA97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_C093319BA97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_C093319BDAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`),
  CONSTRAINT `FK_C093319BF0C2682D` FOREIGN KEY (`student_program_workcenter_id`) REFERENCES `itp_student_program_workcenter` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_answered_survey`
--

LOCK TABLES `itp_student_answered_survey` WRITE;
/*!40000 ALTER TABLE `itp_student_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_student_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_answered_survey_audit`
--

DROP TABLE IF EXISTS `itp_student_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `student_program_workcenter_id` int DEFAULT NULL,
  `student_enrollment_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_1a93d5d96185253714565e301f10fe27_idx` (`rev`),
  CONSTRAINT `rev_1a93d5d96185253714565e301f10fe27_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_answered_survey_audit`
--

LOCK TABLES `itp_student_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `itp_student_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_student_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program`
--

DROP TABLE IF EXISTS `itp_student_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_group_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `authorization_needed` tinyint(1) NOT NULL,
  `authorization_description` longtext COLLATE utf8mb4_unicode_ci,
  `adaptation_needed` tinyint(1) NOT NULL,
  `adaptation_description` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_27082D76DAE14AC5` (`student_enrollment_id`),
  KEY `IDX_27082D767F612572` (`program_group_id`),
  CONSTRAINT `FK_27082D767F612572` FOREIGN KEY (`program_group_id`) REFERENCES `itp_program_group` (`id`),
  CONSTRAINT `FK_27082D76DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program`
--

LOCK TABLES `itp_student_program` WRITE;
/*!40000 ALTER TABLE `itp_student_program` DISABLE KEYS */;
INSERT INTO `itp_student_program` VALUES (1,1,7,0,NULL,0,NULL),(2,1,8,0,NULL,0,NULL),(3,2,15,0,NULL,0,NULL),(4,2,16,0,NULL,0,NULL);
/*!40000 ALTER TABLE `itp_student_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_audit`
--

DROP TABLE IF EXISTS `itp_student_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `program_group_id` int DEFAULT NULL,
  `student_enrollment_id` int DEFAULT NULL,
  `authorization_needed` tinyint(1) DEFAULT NULL,
  `authorization_description` longtext COLLATE utf8mb4_unicode_ci,
  `adaptation_needed` tinyint(1) DEFAULT NULL,
  `adaptation_description` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_1a3f5deeb15ff9811fc18707d72c2c8f_idx` (`rev`),
  CONSTRAINT `rev_1a3f5deeb15ff9811fc18707d72c2c8f_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_audit`
--

LOCK TABLES `itp_student_program_audit` WRITE;
/*!40000 ALTER TABLE `itp_student_program_audit` DISABLE KEYS */;
INSERT INTO `itp_student_program_audit` VALUES (1,10,1,7,0,NULL,0,NULL,'INS'),(2,10,1,8,0,NULL,0,NULL,'INS'),(3,10,2,15,0,NULL,0,NULL,'INS'),(4,10,2,16,0,NULL,0,NULL,'INS');
/*!40000 ALTER TABLE `itp_student_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_program_id` int NOT NULL,
  `workcenter_id` int NOT NULL,
  `educational_tutor_id` int NOT NULL,
  `additional_educational_tutor_id` int DEFAULT NULL,
  `work_tutor_id` int NOT NULL,
  `additional_work_tutor_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `end_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `work_tutor_remarks` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_65275568D367EAEB` (`student_program_id`),
  KEY `IDX_65275568A2473C4B` (`workcenter_id`),
  KEY `IDX_65275568E7F72E80` (`educational_tutor_id`),
  KEY `IDX_652755686C7EFCBC` (`additional_educational_tutor_id`),
  KEY `IDX_65275568F53AEEAD` (`work_tutor_id`),
  KEY `IDX_65275568364A8C43` (`additional_work_tutor_id`),
  CONSTRAINT `FK_65275568364A8C43` FOREIGN KEY (`additional_work_tutor_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_652755686C7EFCBC` FOREIGN KEY (`additional_educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_65275568A2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`),
  CONSTRAINT `FK_65275568D367EAEB` FOREIGN KEY (`student_program_id`) REFERENCES `itp_student_program` (`id`),
  CONSTRAINT `FK_65275568E7F72E80` FOREIGN KEY (`educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_65275568F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter`
--

LOCK TABLES `itp_student_program_workcenter` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter` VALUES (1,1,1,5,NULL,30,NULL,'2026-09-15','2026-11-24',NULL),(2,2,3,4,NULL,31,NULL,'2026-09-15','2026-11-24',NULL),(3,3,1,10,NULL,32,NULL,'2026-09-15','2026-11-24',NULL),(4,4,3,9,NULL,33,NULL,'2026-09-15','2026-11-24',NULL);
/*!40000 ALTER TABLE `itp_student_program_workcenter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter_activity`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL,
  `student_program_workcenter_id` int NOT NULL,
  `scale_value_id` int DEFAULT NULL,
  `valued_by_id` int DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_2AEA0BDF81C06096` (`activity_id`),
  KEY `IDX_2AEA0BDFF0C2682D` (`student_program_workcenter_id`),
  KEY `IDX_2AEA0BDF1B925809` (`scale_value_id`),
  KEY `IDX_2AEA0BDF26F862F5` (`valued_by_id`),
  CONSTRAINT `FK_2AEA0BDF1B925809` FOREIGN KEY (`scale_value_id`) REFERENCES `edu_performance_scale_value` (`id`),
  CONSTRAINT `FK_2AEA0BDF26F862F5` FOREIGN KEY (`valued_by_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_2AEA0BDF81C06096` FOREIGN KEY (`activity_id`) REFERENCES `itp_activity` (`id`),
  CONSTRAINT `FK_2AEA0BDFF0C2682D` FOREIGN KEY (`student_program_workcenter_id`) REFERENCES `itp_student_program_workcenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter_activity`
--

LOCK TABLES `itp_student_program_workcenter_activity` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter_activity` VALUES (1,1,1,3,8,0,'Actividad completada'),(2,2,1,NULL,NULL,0,NULL),(3,1,2,3,8,0,'Actividad completada'),(4,2,2,NULL,NULL,0,NULL),(5,3,3,6,21,0,'Actividad completada'),(6,4,3,NULL,NULL,0,NULL),(7,3,4,6,21,0,'Actividad completada'),(8,4,4,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter_activity_audit`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter_activity_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter_activity_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `activity_id` int DEFAULT NULL,
  `student_program_workcenter_id` int DEFAULT NULL,
  `scale_value_id` int DEFAULT NULL,
  `valued_by_id` int DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_50184693a2db8bf225d8ecb9dcdf64e2_idx` (`rev`),
  CONSTRAINT `rev_50184693a2db8bf225d8ecb9dcdf64e2_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter_activity_audit`
--

LOCK TABLES `itp_student_program_workcenter_activity_audit` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_audit` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter_activity_audit` VALUES (1,10,1,1,3,8,0,'Actividad completada','INS'),(2,10,2,1,NULL,NULL,0,NULL,'INS'),(3,10,1,2,3,8,0,'Actividad completada','INS'),(4,10,2,2,NULL,NULL,0,NULL,'INS'),(5,10,3,3,6,21,0,'Actividad completada','INS'),(6,10,4,3,NULL,NULL,0,NULL,'INS'),(7,10,3,4,6,21,0,'Actividad completada','INS'),(8,10,4,4,NULL,NULL,0,NULL,'INS');
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter_activity_comment`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter_activity_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter_activity_comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_program_activity_id` int NOT NULL,
  `person_id` int NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_1A88C1A8F13F316` (`student_program_activity_id`),
  KEY `IDX_1A88C1A217BBB47` (`person_id`),
  CONSTRAINT `FK_1A88C1A217BBB47` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_1A88C1A8F13F316` FOREIGN KEY (`student_program_activity_id`) REFERENCES `itp_student_program_workcenter_activity` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter_activity_comment`
--

LOCK TABLES `itp_student_program_workcenter_activity_comment` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_comment` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter_activity_comment` VALUES (1,1,8,'Buen desempeño en la actividad','2026-10-27 00:00:00'),(2,3,8,'Buen desempeño en la actividad','2026-10-27 00:00:00'),(3,5,21,'Buen desempeño en la actividad','2026-10-27 00:00:00'),(4,7,21,'Buen desempeño en la actividad','2026-10-27 00:00:00');
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter_activity_comment_audit`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter_activity_comment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter_activity_comment_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `student_program_activity_id` int DEFAULT NULL,
  `person_id` int DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `timestamp` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_7da87a914b245b80a9e64d313a43ea86_idx` (`rev`),
  CONSTRAINT `rev_7da87a914b245b80a9e64d313a43ea86_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter_activity_comment_audit`
--

LOCK TABLES `itp_student_program_workcenter_activity_comment_audit` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_comment_audit` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter_activity_comment_audit` VALUES (1,10,1,8,'Buen desempeño en la actividad','2026-10-27 00:00:00','INS'),(2,10,3,8,'Buen desempeño en la actividad','2026-10-27 00:00:00','INS'),(3,10,5,21,'Buen desempeño en la actividad','2026-10-27 00:00:00','INS'),(4,10,7,21,'Buen desempeño en la actividad','2026-10-27 00:00:00','INS');
/*!40000 ALTER TABLE `itp_student_program_workcenter_activity_comment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_student_program_workcenter_audit`
--

DROP TABLE IF EXISTS `itp_student_program_workcenter_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_student_program_workcenter_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `student_program_id` int DEFAULT NULL,
  `workcenter_id` int DEFAULT NULL,
  `educational_tutor_id` int DEFAULT NULL,
  `additional_educational_tutor_id` int DEFAULT NULL,
  `work_tutor_id` int DEFAULT NULL,
  `additional_work_tutor_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `end_date` date DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
  `work_tutor_remarks` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_06d3662eda53d40081d2087a87118e04_idx` (`rev`),
  CONSTRAINT `rev_06d3662eda53d40081d2087a87118e04_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_student_program_workcenter_audit`
--

LOCK TABLES `itp_student_program_workcenter_audit` WRITE;
/*!40000 ALTER TABLE `itp_student_program_workcenter_audit` DISABLE KEYS */;
INSERT INTO `itp_student_program_workcenter_audit` VALUES (1,10,1,1,5,NULL,30,NULL,'2026-09-15','2026-11-24',NULL,'INS'),(2,10,2,3,4,NULL,31,NULL,'2026-09-15','2026-11-24',NULL,'INS'),(3,10,3,1,10,NULL,32,NULL,'2026-09-15','2026-11-24',NULL,'INS'),(4,10,4,3,9,NULL,33,NULL,'2026-09-15','2026-11-24',NULL,'INS');
/*!40000 ALTER TABLE `itp_student_program_workcenter_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_training_program`
--

DROP TABLE IF EXISTS `itp_training_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_training_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `performance_scale_id` int NOT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `final_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `activity_summary_report_template_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_hours` int DEFAULT NULL,
  `total_hours` int NOT NULL,
  `modality` int NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `weekly_activity_report_template_type` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AF5EDD7B7C2C26C0` (`performance_scale_id`),
  KEY `IDX_AF5EDD7BD490911D` (`student_survey_id`),
  KEY `IDX_AF5EDD7B80E5DA6D` (`company_survey_id`),
  KEY `IDX_AF5EDD7B68F6798B` (`educational_tutor_survey_id`),
  KEY `IDX_AF5EDD7B13E6472B` (`attendance_report_template_id`),
  KEY `IDX_AF5EDD7B10BDE131` (`final_report_template_id`),
  KEY `IDX_AF5EDD7B3BBF8EEC` (`weekly_activity_report_template_id`),
  KEY `IDX_AF5EDD7B7791D10` (`activity_summary_report_template_id`),
  CONSTRAINT `FK_AF5EDD7B10BDE131` FOREIGN KEY (`final_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_AF5EDD7B13E6472B` FOREIGN KEY (`attendance_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_AF5EDD7B3BBF8EEC` FOREIGN KEY (`weekly_activity_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_AF5EDD7B68F6798B` FOREIGN KEY (`educational_tutor_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AF5EDD7B7791D10` FOREIGN KEY (`activity_summary_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_AF5EDD7B7C2C26C0` FOREIGN KEY (`performance_scale_id`) REFERENCES `edu_performance_scale` (`id`),
  CONSTRAINT `FK_AF5EDD7B80E5DA6D` FOREIGN KEY (`company_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_AF5EDD7BD490911D` FOREIGN KEY (`student_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_training_program`
--

LOCK TABLES `itp_training_program` WRITE;
/*!40000 ALTER TABLE `itp_training_program` DISABLE KEYS */;
INSERT INTO `itp_training_program` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Programa de formación en empresas de DAM',10000,20000,1,0,0),(2,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Programa de formación en empresas de DAM',10000,20000,1,0,0);
/*!40000 ALTER TABLE `itp_training_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_training_program_audit`
--

DROP TABLE IF EXISTS `itp_training_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_training_program_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `performance_scale_id` int DEFAULT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `final_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `activity_summary_report_template_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_hours` int DEFAULT NULL,
  `total_hours` int DEFAULT NULL,
  `modality` int DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  `weekly_activity_report_template_type` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_8ae70b07fee7de0a9454869a1a3e11b9_idx` (`rev`),
  CONSTRAINT `rev_8ae70b07fee7de0a9454869a1a3e11b9_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_training_program_audit`
--

LOCK TABLES `itp_training_program_audit` WRITE;
/*!40000 ALTER TABLE `itp_training_program_audit` DISABLE KEYS */;
INSERT INTO `itp_training_program_audit` VALUES (1,10,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Programa de formación en empresas de DAM',10000,20000,1,0,0,'INS'),(2,10,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Programa de formación en empresas de DAM',10000,20000,1,0,0,'INS');
/*!40000 ALTER TABLE `itp_training_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_travel_expense`
--

DROP TABLE IF EXISTS `itp_travel_expense`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_travel_expense` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `travel_route_id` int NOT NULL,
  `from_date_time` datetime NOT NULL,
  `to_date_time` datetime NOT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_unicode_ci,
  `other_expenses` int NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_57DA218A41807E1D` (`teacher_id`),
  KEY `IDX_57DA218A5E9DD7A3` (`travel_route_id`),
  CONSTRAINT `FK_57DA218A41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_57DA218A5E9DD7A3` FOREIGN KEY (`travel_route_id`) REFERENCES `edu_travel_route` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_travel_expense`
--

LOCK TABLES `itp_travel_expense` WRITE;
/*!40000 ALTER TABLE `itp_travel_expense` DISABLE KEYS */;
INSERT INTO `itp_travel_expense` VALUES (1,5,1,'2026-10-15 10:30:00','2026-10-15 13:00:00',NULL,0,'Visita FFE'),(2,10,3,'2026-10-15 10:30:00','2026-10-15 13:00:00',NULL,0,'Visita FFE');
/*!40000 ALTER TABLE `itp_travel_expense` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_travel_expense_audit`
--

DROP TABLE IF EXISTS `itp_travel_expense_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_travel_expense_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `travel_route_id` int DEFAULT NULL,
  `from_date_time` datetime DEFAULT NULL,
  `to_date_time` datetime DEFAULT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_unicode_ci,
  `other_expenses` int DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_bae1d08b03654151182f7f59945a6497_idx` (`rev`),
  CONSTRAINT `rev_bae1d08b03654151182f7f59945a6497_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_travel_expense_audit`
--

LOCK TABLES `itp_travel_expense_audit` WRITE;
/*!40000 ALTER TABLE `itp_travel_expense_audit` DISABLE KEYS */;
INSERT INTO `itp_travel_expense_audit` VALUES (1,10,5,1,'2026-10-15 10:30:00','2026-10-15 13:00:00',NULL,0,'Visita FFE','INS'),(2,10,10,3,'2026-10-15 10:30:00','2026-10-15 13:00:00',NULL,0,'Visita FFE','INS');
/*!40000 ALTER TABLE `itp_travel_expense_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_travel_expense_training_program`
--

DROP TABLE IF EXISTS `itp_travel_expense_training_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_travel_expense_training_program` (
  `travel_expense_id` int NOT NULL,
  `training_program_id` int NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`training_program_id`),
  KEY `IDX_323264A1AA203AA8` (`travel_expense_id`),
  KEY `IDX_323264A18406BD6C` (`training_program_id`),
  CONSTRAINT `FK_323264A18406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_323264A1AA203AA8` FOREIGN KEY (`travel_expense_id`) REFERENCES `itp_travel_expense` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_travel_expense_training_program`
--

LOCK TABLES `itp_travel_expense_training_program` WRITE;
/*!40000 ALTER TABLE `itp_travel_expense_training_program` DISABLE KEYS */;
INSERT INTO `itp_travel_expense_training_program` VALUES (1,1),(2,2);
/*!40000 ALTER TABLE `itp_travel_expense_training_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_travel_expense_training_program_audit`
--

DROP TABLE IF EXISTS `itp_travel_expense_training_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_travel_expense_training_program_audit` (
  `travel_expense_id` int NOT NULL,
  `training_program_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`training_program_id`,`rev`),
  KEY `rev_69545932f5ea1b2ed3d2febd50185e25_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_travel_expense_training_program_audit`
--

LOCK TABLES `itp_travel_expense_training_program_audit` WRITE;
/*!40000 ALTER TABLE `itp_travel_expense_training_program_audit` DISABLE KEYS */;
INSERT INTO `itp_travel_expense_training_program_audit` VALUES (1,1,10,'INS'),(2,2,10,'INS');
/*!40000 ALTER TABLE `itp_travel_expense_training_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_day`
--

DROP TABLE IF EXISTS `itp_work_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_program_workcenter_id` int NOT NULL,
  `hours` int NOT NULL,
  `date` date NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `other_activities` longtext COLLATE utf8mb4_unicode_ci,
  `locked` tinyint(1) NOT NULL,
  `absence` int NOT NULL,
  `start_time1` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time1` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time2` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time2` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_219B1BDDF0C2682D` (`student_program_workcenter_id`),
  CONSTRAINT `FK_219B1BDDF0C2682D` FOREIGN KEY (`student_program_workcenter_id`) REFERENCES `itp_student_program_workcenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_day`
--

LOCK TABLES `itp_work_day` WRITE;
/*!40000 ALTER TABLE `itp_work_day` DISABLE KEYS */;
INSERT INTO `itp_work_day` VALUES (1,1,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(2,1,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(3,1,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(4,1,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(5,1,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(6,1,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(7,1,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(8,1,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(9,1,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(10,1,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(11,1,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(12,1,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(13,1,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(14,1,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(15,1,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(16,1,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(17,1,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(18,1,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(19,1,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(20,1,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(21,1,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(22,1,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(23,1,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(24,1,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(25,1,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(26,1,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(27,1,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(28,1,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(29,1,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(30,1,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(31,1,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(32,1,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(33,1,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(34,1,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(35,1,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(36,1,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(37,1,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(38,1,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(39,1,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(40,1,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(41,1,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(42,1,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(43,1,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(44,1,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(45,1,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(46,1,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(47,1,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(48,1,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(49,1,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(50,1,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(51,1,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(52,2,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(53,2,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(54,2,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(55,2,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(56,2,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(57,2,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(58,2,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(59,2,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(60,2,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(61,2,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(62,2,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(63,2,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(64,2,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(65,2,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(66,2,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(67,2,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(68,2,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(69,2,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(70,2,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(71,2,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(72,2,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(73,2,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(74,2,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(75,2,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(76,2,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(77,2,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(78,2,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(79,2,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(80,2,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(81,2,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(82,2,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(83,2,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(84,2,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(85,2,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(86,2,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(87,2,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(88,2,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(89,2,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(90,2,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(91,2,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(92,2,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(93,2,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(94,2,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(95,2,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(96,2,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(97,2,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(98,2,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(99,2,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(100,2,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(101,2,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(102,2,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(103,3,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(104,3,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(105,3,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(106,3,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(107,3,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(108,3,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(109,3,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(110,3,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(111,3,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(112,3,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(113,3,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(114,3,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(115,3,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(116,3,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(117,3,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(118,3,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(119,3,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(120,3,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(121,3,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(122,3,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(123,3,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(124,3,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(125,3,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(126,3,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(127,3,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(128,3,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(129,3,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(130,3,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(131,3,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(132,3,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(133,3,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(134,3,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(135,3,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(136,3,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(137,3,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(138,3,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(139,3,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(140,3,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(141,3,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(142,3,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(143,3,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(144,3,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(145,3,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(146,3,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(147,3,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(148,3,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(149,3,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(150,3,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(151,3,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(152,3,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(153,3,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(154,4,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(155,4,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(156,4,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(157,4,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(158,4,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(159,4,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(160,4,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(161,4,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(162,4,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(163,4,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(164,4,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(165,4,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(166,4,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(167,4,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(168,4,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(169,4,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(170,4,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(171,4,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(172,4,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(173,4,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(174,4,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(175,4,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(176,4,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(177,4,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(178,4,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(179,4,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(180,4,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(181,4,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(182,4,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(183,4,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(184,4,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL),(185,4,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(186,4,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(187,4,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(188,4,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(189,4,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(190,4,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(191,4,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(192,4,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(193,4,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(194,4,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(195,4,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(196,4,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(197,4,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(198,4,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(199,4,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(200,4,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(201,4,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(202,4,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(203,4,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL),(204,4,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `itp_work_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_day_activity`
--

DROP TABLE IF EXISTS `itp_work_day_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_day_activity` (
  `work_day_id` int NOT NULL,
  `activity_id` int NOT NULL,
  PRIMARY KEY (`work_day_id`,`activity_id`),
  KEY `IDX_6712337CA23B8704` (`work_day_id`),
  KEY `IDX_6712337C81C06096` (`activity_id`),
  CONSTRAINT `FK_6712337C81C06096` FOREIGN KEY (`activity_id`) REFERENCES `itp_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6712337CA23B8704` FOREIGN KEY (`work_day_id`) REFERENCES `itp_work_day` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_day_activity`
--

LOCK TABLES `itp_work_day_activity` WRITE;
/*!40000 ALTER TABLE `itp_work_day_activity` DISABLE KEYS */;
INSERT INTO `itp_work_day_activity` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,3),(104,3),(105,3),(106,3),(107,3),(108,3),(109,3),(110,3),(111,3),(112,3),(113,3),(114,3),(115,3),(116,3),(117,3),(118,3),(119,3),(120,3),(121,3),(122,3),(123,3),(124,3),(125,3),(126,3),(127,3),(128,3),(129,3),(130,3),(131,3),(132,3),(133,3),(134,3),(135,3),(136,3),(137,3),(138,3),(139,3),(140,3),(141,3),(142,3),(143,3),(144,3),(145,3),(146,3),(147,3),(148,3),(149,3),(150,3),(151,3),(152,3),(153,3),(154,3),(155,3),(156,3),(157,3),(158,3),(159,3),(160,3),(161,3),(162,3),(163,3),(164,3),(165,3),(166,3),(167,3),(168,3),(169,3),(170,3),(171,3),(172,3),(173,3),(174,3),(175,3),(176,3),(177,3),(178,3),(179,3),(180,3),(181,3),(182,3),(183,3),(184,3),(185,3),(186,3),(187,3),(188,3),(189,3),(190,3),(191,3),(192,3),(193,3),(194,3),(195,3),(196,3),(197,3),(198,3),(199,3),(200,3),(201,3),(202,3),(203,3),(204,3);
/*!40000 ALTER TABLE `itp_work_day_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_day_activity_audit`
--

DROP TABLE IF EXISTS `itp_work_day_activity_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_day_activity_audit` (
  `work_day_id` int NOT NULL,
  `activity_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`work_day_id`,`activity_id`,`rev`),
  KEY `rev_9df7fef31aed011f266d448db6920ead_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_day_activity_audit`
--

LOCK TABLES `itp_work_day_activity_audit` WRITE;
/*!40000 ALTER TABLE `itp_work_day_activity_audit` DISABLE KEYS */;
INSERT INTO `itp_work_day_activity_audit` VALUES (1,1,10,'INS'),(2,1,10,'INS'),(3,1,10,'INS'),(4,1,10,'INS'),(5,1,10,'INS'),(6,1,10,'INS'),(7,1,10,'INS'),(8,1,10,'INS'),(9,1,10,'INS'),(10,1,10,'INS'),(11,1,10,'INS'),(12,1,10,'INS'),(13,1,10,'INS'),(14,1,10,'INS'),(15,1,10,'INS'),(16,1,10,'INS'),(17,1,10,'INS'),(18,1,10,'INS'),(19,1,10,'INS'),(20,1,10,'INS'),(21,1,10,'INS'),(22,1,10,'INS'),(23,1,10,'INS'),(24,1,10,'INS'),(25,1,10,'INS'),(26,1,10,'INS'),(27,1,10,'INS'),(28,1,10,'INS'),(29,1,10,'INS'),(30,1,10,'INS'),(31,1,10,'INS'),(32,1,10,'INS'),(33,1,10,'INS'),(34,1,10,'INS'),(35,1,10,'INS'),(36,1,10,'INS'),(37,1,10,'INS'),(38,1,10,'INS'),(39,1,10,'INS'),(40,1,10,'INS'),(41,1,10,'INS'),(42,1,10,'INS'),(43,1,10,'INS'),(44,1,10,'INS'),(45,1,10,'INS'),(46,1,10,'INS'),(47,1,10,'INS'),(48,1,10,'INS'),(49,1,10,'INS'),(50,1,10,'INS'),(51,1,10,'INS'),(52,1,10,'INS'),(53,1,10,'INS'),(54,1,10,'INS'),(55,1,10,'INS'),(56,1,10,'INS'),(57,1,10,'INS'),(58,1,10,'INS'),(59,1,10,'INS'),(60,1,10,'INS'),(61,1,10,'INS'),(62,1,10,'INS'),(63,1,10,'INS'),(64,1,10,'INS'),(65,1,10,'INS'),(66,1,10,'INS'),(67,1,10,'INS'),(68,1,10,'INS'),(69,1,10,'INS'),(70,1,10,'INS'),(71,1,10,'INS'),(72,1,10,'INS'),(73,1,10,'INS'),(74,1,10,'INS'),(75,1,10,'INS'),(76,1,10,'INS'),(77,1,10,'INS'),(78,1,10,'INS'),(79,1,10,'INS'),(80,1,10,'INS'),(81,1,10,'INS'),(82,1,10,'INS'),(83,1,10,'INS'),(84,1,10,'INS'),(85,1,10,'INS'),(86,1,10,'INS'),(87,1,10,'INS'),(88,1,10,'INS'),(89,1,10,'INS'),(90,1,10,'INS'),(91,1,10,'INS'),(92,1,10,'INS'),(93,1,10,'INS'),(94,1,10,'INS'),(95,1,10,'INS'),(96,1,10,'INS'),(97,1,10,'INS'),(98,1,10,'INS'),(99,1,10,'INS'),(100,1,10,'INS'),(101,1,10,'INS'),(102,1,10,'INS'),(103,3,10,'INS'),(104,3,10,'INS'),(105,3,10,'INS'),(106,3,10,'INS'),(107,3,10,'INS'),(108,3,10,'INS'),(109,3,10,'INS'),(110,3,10,'INS'),(111,3,10,'INS'),(112,3,10,'INS'),(113,3,10,'INS'),(114,3,10,'INS'),(115,3,10,'INS'),(116,3,10,'INS'),(117,3,10,'INS'),(118,3,10,'INS'),(119,3,10,'INS'),(120,3,10,'INS'),(121,3,10,'INS'),(122,3,10,'INS'),(123,3,10,'INS'),(124,3,10,'INS'),(125,3,10,'INS'),(126,3,10,'INS'),(127,3,10,'INS'),(128,3,10,'INS'),(129,3,10,'INS'),(130,3,10,'INS'),(131,3,10,'INS'),(132,3,10,'INS'),(133,3,10,'INS'),(134,3,10,'INS'),(135,3,10,'INS'),(136,3,10,'INS'),(137,3,10,'INS'),(138,3,10,'INS'),(139,3,10,'INS'),(140,3,10,'INS'),(141,3,10,'INS'),(142,3,10,'INS'),(143,3,10,'INS'),(144,3,10,'INS'),(145,3,10,'INS'),(146,3,10,'INS'),(147,3,10,'INS'),(148,3,10,'INS'),(149,3,10,'INS'),(150,3,10,'INS'),(151,3,10,'INS'),(152,3,10,'INS'),(153,3,10,'INS'),(154,3,10,'INS'),(155,3,10,'INS'),(156,3,10,'INS'),(157,3,10,'INS'),(158,3,10,'INS'),(159,3,10,'INS'),(160,3,10,'INS'),(161,3,10,'INS'),(162,3,10,'INS'),(163,3,10,'INS'),(164,3,10,'INS'),(165,3,10,'INS'),(166,3,10,'INS'),(167,3,10,'INS'),(168,3,10,'INS'),(169,3,10,'INS'),(170,3,10,'INS'),(171,3,10,'INS'),(172,3,10,'INS'),(173,3,10,'INS'),(174,3,10,'INS'),(175,3,10,'INS'),(176,3,10,'INS'),(177,3,10,'INS'),(178,3,10,'INS'),(179,3,10,'INS'),(180,3,10,'INS'),(181,3,10,'INS'),(182,3,10,'INS'),(183,3,10,'INS'),(184,3,10,'INS'),(185,3,10,'INS'),(186,3,10,'INS'),(187,3,10,'INS'),(188,3,10,'INS'),(189,3,10,'INS'),(190,3,10,'INS'),(191,3,10,'INS'),(192,3,10,'INS'),(193,3,10,'INS'),(194,3,10,'INS'),(195,3,10,'INS'),(196,3,10,'INS'),(197,3,10,'INS'),(198,3,10,'INS'),(199,3,10,'INS'),(200,3,10,'INS'),(201,3,10,'INS'),(202,3,10,'INS'),(203,3,10,'INS'),(204,3,10,'INS');
/*!40000 ALTER TABLE `itp_work_day_activity_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_day_audit`
--

DROP TABLE IF EXISTS `itp_work_day_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_day_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `student_program_workcenter_id` int DEFAULT NULL,
  `hours` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `other_activities` longtext COLLATE utf8mb4_unicode_ci,
  `locked` tinyint(1) DEFAULT NULL,
  `absence` int DEFAULT NULL,
  `start_time1` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time1` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time2` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time2` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_da616dc821dbbf2762747da7f44177b6_idx` (`rev`),
  CONSTRAINT `rev_da616dc821dbbf2762747da7f44177b6_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_day_audit`
--

LOCK TABLES `itp_work_day_audit` WRITE;
/*!40000 ALTER TABLE `itp_work_day_audit` DISABLE KEYS */;
INSERT INTO `itp_work_day_audit` VALUES (1,10,1,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(2,10,1,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(3,10,1,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(4,10,1,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(5,10,1,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(6,10,1,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(7,10,1,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(8,10,1,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(9,10,1,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(10,10,1,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(11,10,1,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(12,10,1,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(13,10,1,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(14,10,1,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(15,10,1,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(16,10,1,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(17,10,1,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(18,10,1,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(19,10,1,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(20,10,1,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(21,10,1,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(22,10,1,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(23,10,1,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(24,10,1,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(25,10,1,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(26,10,1,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(27,10,1,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(28,10,1,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(29,10,1,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(30,10,1,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(31,10,1,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(32,10,1,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(33,10,1,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(34,10,1,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(35,10,1,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(36,10,1,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(37,10,1,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(38,10,1,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(39,10,1,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(40,10,1,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(41,10,1,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(42,10,1,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(43,10,1,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(44,10,1,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(45,10,1,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(46,10,1,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(47,10,1,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(48,10,1,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(49,10,1,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(50,10,1,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(51,10,1,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(52,10,2,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(53,10,2,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(54,10,2,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(55,10,2,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(56,10,2,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(57,10,2,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(58,10,2,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(59,10,2,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(60,10,2,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(61,10,2,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(62,10,2,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(63,10,2,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(64,10,2,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(65,10,2,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(66,10,2,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(67,10,2,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(68,10,2,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(69,10,2,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(70,10,2,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(71,10,2,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(72,10,2,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(73,10,2,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(74,10,2,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(75,10,2,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(76,10,2,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(77,10,2,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(78,10,2,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(79,10,2,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(80,10,2,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(81,10,2,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(82,10,2,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(83,10,2,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(84,10,2,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(85,10,2,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(86,10,2,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(87,10,2,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(88,10,2,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(89,10,2,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(90,10,2,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(91,10,2,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(92,10,2,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(93,10,2,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(94,10,2,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(95,10,2,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(96,10,2,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(97,10,2,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(98,10,2,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(99,10,2,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(100,10,2,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(101,10,2,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(102,10,2,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(103,10,3,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(104,10,3,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(105,10,3,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(106,10,3,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(107,10,3,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(108,10,3,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(109,10,3,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(110,10,3,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(111,10,3,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(112,10,3,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(113,10,3,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(114,10,3,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(115,10,3,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(116,10,3,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(117,10,3,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(118,10,3,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(119,10,3,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(120,10,3,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(121,10,3,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(122,10,3,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(123,10,3,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(124,10,3,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(125,10,3,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(126,10,3,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(127,10,3,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(128,10,3,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(129,10,3,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(130,10,3,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(131,10,3,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(132,10,3,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(133,10,3,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(134,10,3,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(135,10,3,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(136,10,3,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(137,10,3,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(138,10,3,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(139,10,3,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(140,10,3,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(141,10,3,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(142,10,3,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(143,10,3,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(144,10,3,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(145,10,3,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(146,10,3,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(147,10,3,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(148,10,3,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(149,10,3,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(150,10,3,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(151,10,3,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(152,10,3,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(153,10,3,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(154,10,4,500,'2026-09-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(155,10,4,500,'2026-09-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(156,10,4,500,'2026-09-17','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(157,10,4,500,'2026-09-18','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(158,10,4,500,'2026-09-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(159,10,4,500,'2026-09-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(160,10,4,500,'2026-09-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(161,10,4,500,'2026-09-24','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(162,10,4,500,'2026-09-25','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(163,10,4,500,'2026-09-28','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(164,10,4,500,'2026-09-29','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(165,10,4,500,'2026-09-30','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(166,10,4,500,'2026-10-01','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(167,10,4,500,'2026-10-02','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(168,10,4,500,'2026-10-05','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(169,10,4,500,'2026-10-06','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(170,10,4,500,'2026-10-07','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(171,10,4,500,'2026-10-08','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(172,10,4,500,'2026-10-09','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(173,10,4,500,'2026-10-12','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(174,10,4,500,'2026-10-13','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(175,10,4,500,'2026-10-14','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(176,10,4,500,'2026-10-15','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(177,10,4,500,'2026-10-16','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(178,10,4,500,'2026-10-19','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(179,10,4,500,'2026-10-20','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(180,10,4,500,'2026-10-21','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(181,10,4,500,'2026-10-22','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(182,10,4,500,'2026-10-23','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(183,10,4,500,'2026-10-26','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(184,10,4,500,'2026-10-27','Jornada de formación en la empresa',NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(185,10,4,500,'2026-10-28',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(186,10,4,500,'2026-10-29',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(187,10,4,500,'2026-10-30',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(188,10,4,500,'2026-11-02',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(189,10,4,500,'2026-11-03',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(190,10,4,500,'2026-11-04',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(191,10,4,500,'2026-11-05',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(192,10,4,500,'2026-11-06',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(193,10,4,500,'2026-11-09',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(194,10,4,500,'2026-11-10',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(195,10,4,500,'2026-11-11',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(196,10,4,500,'2026-11-12',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(197,10,4,500,'2026-11-13',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(198,10,4,500,'2026-11-16',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(199,10,4,500,'2026-11-17',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(200,10,4,500,'2026-11-18',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(201,10,4,500,'2026-11-19',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(202,10,4,500,'2026-11-20',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(203,10,4,500,'2026-11-23',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS'),(204,10,4,500,'2026-11-24',NULL,NULL,0,0,NULL,NULL,NULL,NULL,'INS');
/*!40000 ALTER TABLE `itp_work_day_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_tutor_answered_survey`
--

DROP TABLE IF EXISTS `itp_work_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_program_id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `work_tutor_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5B7F9F038406BD6C` (`training_program_id`),
  KEY `IDX_5B7F9F03C54F3401` (`academic_year_id`),
  KEY `IDX_5B7F9F03F53AEEAD` (`work_tutor_id`),
  KEY `IDX_5B7F9F03A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_5B7F9F038406BD6C` FOREIGN KEY (`training_program_id`) REFERENCES `itp_training_program` (`id`),
  CONSTRAINT `FK_5B7F9F03A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_5B7F9F03C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`),
  CONSTRAINT `FK_5B7F9F03F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_tutor_answered_survey`
--

LOCK TABLES `itp_work_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `itp_work_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_work_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itp_work_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `itp_work_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itp_work_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `training_program_id` int DEFAULT NULL,
  `academic_year_id` int DEFAULT NULL,
  `work_tutor_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_10863b6a7ed3dcadea8594902a57e17a_idx` (`rev`),
  CONSTRAINT `rev_10863b6a7ed3dcadea8594902a57e17a_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itp_work_tutor_answered_survey_audit`
--

LOCK TABLES `itp_work_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `itp_work_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `itp_work_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration_versions`
--

DROP TABLE IF EXISTS `migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration_versions`
--

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;
INSERT INTO `migration_versions` VALUES ('App\\Migrations\\Version20190115204202','2026-10-27 23:57:23',836),('App\\Migrations\\Version20190120214146','2026-10-27 23:57:24',5),('App\\Migrations\\Version20190121231221','2026-10-27 23:57:24',48),('App\\Migrations\\Version20190125082340','2026-10-27 23:57:24',12),('App\\Migrations\\Version20190606175732','2026-10-27 23:57:24',249),('App\\Migrations\\Version20190606201046','2026-10-27 23:57:24',8),('App\\Migrations\\Version20190606210233','2026-10-27 23:57:24',41),('App\\Migrations\\Version20190607112650','2026-10-27 23:57:24',12),('App\\Migrations\\Version20190610105704','2026-10-27 23:57:24',52),('App\\Migrations\\Version20190611135227','2026-10-27 23:57:24',7),('App\\Migrations\\Version20190707224036','2026-10-27 23:57:24',315),('App\\Migrations\\Version20190707224037','2026-10-27 23:57:25',219),('App\\Migrations\\Version20191013171656','2026-10-27 23:57:25',19),('App\\Migrations\\Version20191105214506','2026-10-27 23:57:25',48),('App\\Migrations\\Version20191111175911','2026-10-27 23:57:25',98),('App\\Migrations\\Version20191117202915','2026-10-27 23:57:25',22),('App\\Migrations\\Version20200130213632','2026-10-27 23:57:25',21),('App\\Migrations\\Version20200130213703','2026-10-27 23:57:25',27),('App\\Migrations\\Version20200130222216','2026-10-27 23:57:25',4),('App\\Migrations\\Version20200219220420','2026-10-27 23:57:25',13),('App\\Migrations\\Version20200220080844','2026-10-27 23:57:25',9),('App\\Migrations\\Version20200220221122','2026-10-27 23:57:25',49),('App\\Migrations\\Version20200220223755','2026-10-27 23:57:25',49),('App\\Migrations\\Version20200304222616','2026-10-27 23:57:25',663),('App\\Migrations\\Version20200305125141','2026-10-27 23:57:26',97),('App\\Migrations\\Version20200306184748','2026-10-27 23:57:26',248),('App\\Migrations\\Version20200306225637','2026-10-27 23:57:26',162),('App\\Migrations\\Version20200308211815','2026-10-27 23:57:27',40),('App\\Migrations\\Version20200308223141','2026-10-27 23:57:27',4),('App\\Migrations\\Version20200309164705','2026-10-27 23:57:27',67),('App\\Migrations\\Version20200604183226','2026-10-27 23:57:27',35),('App\\Migrations\\Version20200613200942','2026-10-27 23:57:27',66),('App\\Migrations\\Version20220310091153','2026-10-27 23:57:27',135),('App\\Migrations\\Version20220330114555','2026-10-27 23:57:27',10),('App\\Migrations\\Version20220331071212','2026-10-27 23:57:27',66),('App\\Migrations\\Version20220331234101','2026-10-27 23:57:27',3),('App\\Migrations\\Version20220404195924','2026-10-27 23:57:27',67),('App\\Migrations\\Version20220411193210','2026-10-27 23:57:27',170),('App\\Migrations\\Version20220421122325','2026-10-27 23:57:27',17),('App\\Migrations\\Version20220421123848','2026-10-27 23:57:27',178),('App\\Migrations\\Version20220421172825','2026-10-27 23:57:27',45),('App\\Migrations\\Version20220422070736','2026-10-27 23:57:27',8),('App\\Migrations\\Version20220519103119','2026-10-27 23:57:27',8),('App\\Migrations\\Version20220519121305','2026-10-27 23:57:27',6),('App\\Migrations\\Version20220523120848','2026-10-27 23:57:27',165),('App\\Migrations\\Version20221229112412','2026-10-27 23:57:28',47),('App\\Migrations\\Version20231220130658','2026-10-27 23:57:28',118),('App\\Migrations\\Version20231220131607','2026-10-27 23:57:28',14),('App\\Migrations\\Version20231220132031','2026-10-27 23:57:28',225),('App\\Migrations\\Version20231221085431','2026-10-27 23:57:28',12),('App\\Migrations\\Version20240814114503','2026-10-27 23:57:28',424),('App\\Migrations\\Version20240816165216','2026-10-27 23:57:29',11),('App\\Migrations\\Version20241007062837','2026-10-27 23:57:29',128),('App\\Migrations\\Version20241215214512','2026-10-27 23:57:29',811),('App\\Migrations\\Version20250118213721','2026-10-27 23:57:29',106),('App\\Migrations\\Version20250313190839','2026-10-27 23:57:30',1),('App\\Migrations\\Version20250603185300','2026-10-27 23:57:30',182),('App\\Migrations\\Version20250604123324','2026-10-27 23:57:30',0),('App\\Migrations\\Version20250604140910','2026-10-27 23:57:30',7),('App\\Migrations\\Version20250605103756','2026-10-27 23:57:30',73);
/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization`
--

DROP TABLE IF EXISTS `organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization` (
  `id` int NOT NULL AUTO_INCREMENT,
  `current_academic_year_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `web_site` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_C1EE637C77153098` (`code`),
  UNIQUE KEY `UNIQ_C1EE637C2B06A9F7` (`current_academic_year_id`),
  CONSTRAINT `FK_C1EE637C2B06A9F7` FOREIGN KEY (`current_academic_year_id`) REFERENCES `edu_academic_year` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization`
--

LOCK TABLES `organization` WRITE;
/*!40000 ALTER TABLE `organization` DISABLE KEYS */;
INSERT INTO `organization` VALUES (1,1,'I.E.S. Alfa','23000001',NULL,'Linares',NULL,NULL,NULL,NULL,NULL,NULL),(2,2,'I.E.S. Beta','23000002',NULL,'Úbeda',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `organization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `person` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `unique_identifier` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `gender` int NOT NULL,
  `login_username` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `global_administrator` tinyint(1) NOT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_type` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_expiration` datetime DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `external_check` tinyint(1) NOT NULL,
  `allow_external_check` tinyint(1) NOT NULL,
  `default_organization_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_34DCD1766BD2BEA0` (`unique_identifier`),
  UNIQUE KEY `UNIQ_34DCD176D6FA26E8` (`login_username`),
  UNIQUE KEY `UNIQ_34DCD176B08E074E` (`email_address`),
  KEY `IDX_34DCD176AA9E0B02` (`default_organization_id`),
  CONSTRAINT `FK_34DCD176AA9E0B02` FOREIGN KEY (`default_organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `person`
--

LOCK TABLES `person` WRITE;
/*!40000 ALTER TABLE `person` DISABLE KEYS */;
INSERT INTO `person` VALUES (1,'Administrador','Global',NULL,NULL,0,'admin','$2y$13$bbbYmi7oNOGm0Oki5G8U8uwKTUYSVKI0NPEaLk5fEDyQbsS2gC4XW',0,1,1,'admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(2,'Administrador','Local I.E.S. Alfa',NULL,NULL,0,'alfa.admin','$2y$04$DwoyliwI3l9suKEXiV9e6uEZnIPKBSxWb90GyeW.DA.lAZAsywHUi',0,1,0,'alfa.admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(3,'Administrador','Local I.E.S. Beta',NULL,NULL,0,'beta.admin','$2y$04$jOtzR3BnraPpUftQALlJh.A6BuB7d3CzXqDeZYQSpBGiZI0tFzQp2',0,1,0,'beta.admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(4,'Jefe','Informática Alfa',NULL,NULL,0,'alfa.jefe','$2y$04$5Kxuyrt.vCE8hnHmxzOyKubY2q2MHJgI9inY.IPTdQqbX2STnKfW.',0,1,0,'alfa.jefe@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(5,'Directora','Dirección Alfa',NULL,NULL,0,'alfa.director','$2y$04$bVGDX85wJFeEuFiBsxMaJuEAUF4dvCcA8Iaf7NHS44t3RrC1xRAcG',0,1,0,'alfa.director@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(6,'Secretario','Económico Alfa',NULL,NULL,0,'alfa.economico','$2y$04$1v/jmJNYBCFMrz6fQG73aOiQRLxSwH5EoLRAd83/rFNESqaagGnSG',0,1,0,'alfa.economico@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(7,'Tutor','Segundo Alfa',NULL,NULL,0,'alfa.tutor','$2y$04$cPQkFytnkX3umd/EW5yPLeZLBP7tnPB4tYSpq8O3rTCY37FNmnqIG',0,1,0,'alfa.tutor@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(8,'Profesor','Docente Alfa',NULL,NULL,0,'alfa.profesor','$2y$04$Xohf0W7h4xkx/kUumroggeBcQrUgqKCaV1AAZqzcBHcktp5HmPoGS',0,1,0,'alfa.profesor@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(9,'Alumno 1','Estudiante Alfa',NULL,NULL,0,'alfa.alumno1','$2y$04$Qdr/VE9WXeg6jCtu.1PdwuJCfC/IZY4gEkdS7pAyJgv32Irap2Ace',0,1,0,'alfa.alumno1@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(10,'Alumno 2','Estudiante Alfa',NULL,NULL,0,'alfa.alumno2','$2y$04$eWajItop7w.8PqAl1ehVJ.je3W69aaWwRSH8DsRxo331oHJkzSxT.',0,1,0,'alfa.alumno2@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(11,'Alumno 3','Estudiante Alfa',NULL,NULL,0,'alfa.alumno3','$2y$04$s4oFd7wzNgLsohOp4KVBDOm.sEc7TSzvYBC5p2vA/J2Q94ZeOyv9i',0,1,0,'alfa.alumno3@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(12,'Alumno 4','Estudiante Alfa',NULL,NULL,0,'alfa.alumno4','$2y$04$vPrIq4TPZeb1OXW4aXwhVOZwyBsmlfRHacqzSqcBU3GywmTTQdx9q',0,1,0,'alfa.alumno4@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(13,'Alumno 5','Estudiante Alfa',NULL,NULL,0,'alfa.alumno5','$2y$04$UoLk7HhFMhzzfAr0Tyvl5.ihAl6vDGuFhGblJyDKIB17TUkZDIKeO',0,1,0,'alfa.alumno5@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(14,'Alumno 6','Estudiante Alfa',NULL,NULL,0,'alfa.alumno6','$2y$04$az9.9ISNIOCWdqkL6yDrNuiaxn7nQCK0Tk/8gSHRte.InEBBAzwuK',0,1,0,'alfa.alumno6@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(15,'Alumno 7','Estudiante Alfa',NULL,NULL,0,'alfa.alumno7','$2y$04$iZaLjXKa7nLvADLQT2F63.e9sUsaT.1rhQJLguEhqZVpA/fieoP7y',0,1,0,'alfa.alumno7@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(16,'Alumno 8','Estudiante Alfa',NULL,NULL,0,'alfa.alumno8','$2y$04$2LKsrzQsY15s2aeCLVQmMudjKADfKmubJebQqttlGVaKmpedBCmQm',0,1,0,'alfa.alumno8@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(17,'Jefe','Informática Beta',NULL,NULL,0,'beta.jefe','$2y$04$WuioCGo394BWOTJ/rg5a/uLGe32x1qqGo2EcfMxKz7EQcvtckEWEK',0,1,0,'beta.jefe@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(18,'Directora','Dirección Beta',NULL,NULL,0,'beta.director','$2y$04$UsBqWYEgxq0SbM/3oFF2ceBZ/gWfeYPWbpPnMIWpsU3e8QYssWRlW',0,1,0,'beta.director@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(19,'Secretario','Económico Beta',NULL,NULL,0,'beta.economico','$2y$04$mlJWRZ1H17P9NzmzrxR/FOhDTT/I7DXWW9gBXzLvNBODU7P1LWxiO',0,1,0,'beta.economico@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(20,'Tutor','Segundo Beta',NULL,NULL,0,'beta.tutor','$2y$04$Xlxmc.GVVN1Njjauoz4P3OlapudyDu4FIP45wwG4ecypwQOmTP2Fu',0,1,0,'beta.tutor@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(21,'Profesor','Docente Beta',NULL,NULL,0,'beta.profesor','$2y$04$9rDXHHpTykscHlkKlYBBpOmfApC6V1UN/LJNDdXR.1aPdtS96CIMO',0,1,0,'beta.profesor@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(22,'Alumno 1','Estudiante Beta',NULL,NULL,0,'beta.alumno1','$2y$04$qulpHu/Z5mB374hQnOdyWu5aL0wMgMLDsOpMfuy7WSWG4UgsXhbGy',0,1,0,'beta.alumno1@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(23,'Alumno 2','Estudiante Beta',NULL,NULL,0,'beta.alumno2','$2y$04$7.6AC4fCKt/eI.yqur3lS.m/iYKgTH/IOyEEOI55TR4Xx1/bRG/7O',0,1,0,'beta.alumno2@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(24,'Alumno 3','Estudiante Beta',NULL,NULL,0,'beta.alumno3','$2y$04$rrm19jh.1wQFdhRqE9qc6u6nI9t9VfLl89zGK0Tv8UXJnpF3FUDUS',0,1,0,'beta.alumno3@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(25,'Alumno 4','Estudiante Beta',NULL,NULL,0,'beta.alumno4','$2y$04$D6GB96LmGuTN0kGHul70mulVXzq55vXV9BQjXQ40BYNDY6r6t0Q.S',0,1,0,'beta.alumno4@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(26,'Alumno 5','Estudiante Beta',NULL,NULL,0,'beta.alumno5','$2y$04$/lpGdtuwoH1QIe0hqeNmkeS2Vg.Bfve7q7TcwjTvNqQW0fEjWGzxG',0,1,0,'beta.alumno5@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(27,'Alumno 6','Estudiante Beta',NULL,NULL,0,'beta.alumno6','$2y$04$BBmuDWWVSEg6wbKBAE4Qj.w8YvoQnsZavbu6.OGYDnnTUvtR4R2hu',0,1,0,'beta.alumno6@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(28,'Alumno 7','Estudiante Beta',NULL,NULL,0,'beta.alumno7','$2y$04$tKk80QDqmwXfihiDgm1qeON6iP0QIRtVgfVhmZC1JKrmu1ML4jL6i',0,1,0,'beta.alumno7@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(29,'Alumno 8','Estudiante Beta',NULL,NULL,0,'beta.alumno8','$2y$04$jFsPWNrjsd95hmdsmwIC2.2Mg.n.DjlMT1UBxS5BCPy//S/QEbeZi',0,1,0,'beta.alumno8@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(30,'Tutor','Laboral Uno Alfa',NULL,NULL,0,'alfa.tutorlaboral','$2y$04$aU5djZQwEZeVHPnukJ0rluXxbuWqE/aeZEwKByCYbMynYmk7EQPGi',0,1,0,'alfa.tutorlaboral@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(31,'Tutora','Laboral Dos Alfa',NULL,NULL,0,'alfa.tutorlaboral2','$2y$04$XEjg56QZ2FhE8NkEPyVusOOJJnOev1TwLBe0SpT6uwqUtSBV5Tf4m',0,1,0,'alfa.tutorlaboral2@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(32,'Tutor','Laboral Uno Beta',NULL,NULL,0,'beta.tutorlaboral','$2y$04$lh2yIrGapcKC1/IEa0JEGetbYqkj22npAjbtPqUp2fODLJn9ZimQu',0,1,0,'beta.tutorlaboral@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(33,'Tutora','Laboral Dos Beta',NULL,NULL,0,'beta.tutorlaboral2','$2y$04$Uye3t1uHSMIAqPdmU.NiH.7wecwoW/dbSUtmk8.d.PcEinvmp2HN6',0,1,0,'beta.tutorlaboral2@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(34,'Coordinador','Dual Alfa',NULL,NULL,0,'alfa.coordinador','$2y$04$oh9u8/ohOLl/owhe7p8EHOUL/usW6MUg4CZKYjJmMjm.YP1Hy34P2',0,1,0,'alfa.coordinador@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(35,'Coordinador','Dual Beta',NULL,NULL,0,'beta.coordinador','$2y$04$I2dOMbnVccoBfrGJgPqFxe2T09r3Z3FHbZRGHSyHMfGSjUkWOAJlq',0,1,0,'beta.coordinador@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(36,'Gestor','FFE Alfa',NULL,NULL,0,'alfa.gestorffe','$2y$04$9oD7dnXbtZyOHt15AZw1huuLwc1v.TUok5Ph2jkCMRbjRu8jEh.cO',0,1,0,'alfa.gestorffe@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL),(37,'Gestor','FFE Beta',NULL,NULL,0,'beta.gestorffe','$2y$04$3mvYgfTbfS/teNCvrqr7mOvGkDfSDyJS9F3A78gA8i9P2KBagWjzq',0,1,0,'beta.gestorffe@example.com',NULL,NULL,NULL,NULL,NULL,0,0,NULL);
/*!40000 ALTER TABLE `person` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `person_audit`
--

DROP TABLE IF EXISTS `person_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `person_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `default_organization_id` int DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `unique_identifier` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `gender` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `login_username` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `global_administrator` tinyint(1) DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_type` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `token_expiration` datetime DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `external_check` tinyint(1) DEFAULT NULL,
  `allow_external_check` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_907be00c9c366335b3359c1e8e2f6227_idx` (`rev`),
  CONSTRAINT `rev_907be00c9c366335b3359c1e8e2f6227_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `person_audit`
--

LOCK TABLES `person_audit` WRITE;
/*!40000 ALTER TABLE `person_audit` DISABLE KEYS */;
INSERT INTO `person_audit` VALUES (1,1,NULL,'Administrador','Global',NULL,NULL,0,'INS','admin','$2y$04$dXCqQYP.zoJWLNHbl8QXLeyZz1Lv5x5NJN7XLX05Zjt5Cl1zDecAW',0,1,1,'admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(2,3,NULL,'Administrador','Local I.E.S. Alfa',NULL,NULL,0,'INS','alfa.admin','$2y$04$DwoyliwI3l9suKEXiV9e6uEZnIPKBSxWb90GyeW.DA.lAZAsywHUi',0,1,0,'alfa.admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(3,5,NULL,'Administrador','Local I.E.S. Beta',NULL,NULL,0,'INS','beta.admin','$2y$04$jOtzR3BnraPpUftQALlJh.A6BuB7d3CzXqDeZYQSpBGiZI0tFzQp2',0,1,0,'beta.admin@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(4,7,NULL,'Jefe','Informática Alfa',NULL,NULL,0,'INS','alfa.jefe','$2y$04$5Kxuyrt.vCE8hnHmxzOyKubY2q2MHJgI9inY.IPTdQqbX2STnKfW.',0,1,0,'alfa.jefe@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(5,7,NULL,'Directora','Dirección Alfa',NULL,NULL,0,'INS','alfa.director','$2y$04$bVGDX85wJFeEuFiBsxMaJuEAUF4dvCcA8Iaf7NHS44t3RrC1xRAcG',0,1,0,'alfa.director@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(6,7,NULL,'Secretario','Económico Alfa',NULL,NULL,0,'INS','alfa.economico','$2y$04$1v/jmJNYBCFMrz6fQG73aOiQRLxSwH5EoLRAd83/rFNESqaagGnSG',0,1,0,'alfa.economico@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(7,7,NULL,'Tutor','Segundo Alfa',NULL,NULL,0,'INS','alfa.tutor','$2y$04$cPQkFytnkX3umd/EW5yPLeZLBP7tnPB4tYSpq8O3rTCY37FNmnqIG',0,1,0,'alfa.tutor@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(8,7,NULL,'Profesor','Docente Alfa',NULL,NULL,0,'INS','alfa.profesor','$2y$04$Xohf0W7h4xkx/kUumroggeBcQrUgqKCaV1AAZqzcBHcktp5HmPoGS',0,1,0,'alfa.profesor@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(9,7,NULL,'Alumno 1','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno1','$2y$04$Qdr/VE9WXeg6jCtu.1PdwuJCfC/IZY4gEkdS7pAyJgv32Irap2Ace',0,1,0,'alfa.alumno1@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(10,7,NULL,'Alumno 2','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno2','$2y$04$eWajItop7w.8PqAl1ehVJ.je3W69aaWwRSH8DsRxo331oHJkzSxT.',0,1,0,'alfa.alumno2@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(11,7,NULL,'Alumno 3','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno3','$2y$04$s4oFd7wzNgLsohOp4KVBDOm.sEc7TSzvYBC5p2vA/J2Q94ZeOyv9i',0,1,0,'alfa.alumno3@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(12,7,NULL,'Alumno 4','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno4','$2y$04$vPrIq4TPZeb1OXW4aXwhVOZwyBsmlfRHacqzSqcBU3GywmTTQdx9q',0,1,0,'alfa.alumno4@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(13,7,NULL,'Alumno 5','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno5','$2y$04$UoLk7HhFMhzzfAr0Tyvl5.ihAl6vDGuFhGblJyDKIB17TUkZDIKeO',0,1,0,'alfa.alumno5@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(14,7,NULL,'Alumno 6','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno6','$2y$04$az9.9ISNIOCWdqkL6yDrNuiaxn7nQCK0Tk/8gSHRte.InEBBAzwuK',0,1,0,'alfa.alumno6@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(15,7,NULL,'Alumno 7','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno7','$2y$04$iZaLjXKa7nLvADLQT2F63.e9sUsaT.1rhQJLguEhqZVpA/fieoP7y',0,1,0,'alfa.alumno7@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(16,7,NULL,'Alumno 8','Estudiante Alfa',NULL,NULL,0,'INS','alfa.alumno8','$2y$04$2LKsrzQsY15s2aeCLVQmMudjKADfKmubJebQqttlGVaKmpedBCmQm',0,1,0,'alfa.alumno8@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(17,7,NULL,'Jefe','Informática Beta',NULL,NULL,0,'INS','beta.jefe','$2y$04$WuioCGo394BWOTJ/rg5a/uLGe32x1qqGo2EcfMxKz7EQcvtckEWEK',0,1,0,'beta.jefe@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(18,7,NULL,'Directora','Dirección Beta',NULL,NULL,0,'INS','beta.director','$2y$04$UsBqWYEgxq0SbM/3oFF2ceBZ/gWfeYPWbpPnMIWpsU3e8QYssWRlW',0,1,0,'beta.director@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(19,7,NULL,'Secretario','Económico Beta',NULL,NULL,0,'INS','beta.economico','$2y$04$mlJWRZ1H17P9NzmzrxR/FOhDTT/I7DXWW9gBXzLvNBODU7P1LWxiO',0,1,0,'beta.economico@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(20,7,NULL,'Tutor','Segundo Beta',NULL,NULL,0,'INS','beta.tutor','$2y$04$Xlxmc.GVVN1Njjauoz4P3OlapudyDu4FIP45wwG4ecypwQOmTP2Fu',0,1,0,'beta.tutor@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(21,7,NULL,'Profesor','Docente Beta',NULL,NULL,0,'INS','beta.profesor','$2y$04$9rDXHHpTykscHlkKlYBBpOmfApC6V1UN/LJNDdXR.1aPdtS96CIMO',0,1,0,'beta.profesor@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(22,7,NULL,'Alumno 1','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno1','$2y$04$qulpHu/Z5mB374hQnOdyWu5aL0wMgMLDsOpMfuy7WSWG4UgsXhbGy',0,1,0,'beta.alumno1@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(23,7,NULL,'Alumno 2','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno2','$2y$04$7.6AC4fCKt/eI.yqur3lS.m/iYKgTH/IOyEEOI55TR4Xx1/bRG/7O',0,1,0,'beta.alumno2@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(24,7,NULL,'Alumno 3','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno3','$2y$04$rrm19jh.1wQFdhRqE9qc6u6nI9t9VfLl89zGK0Tv8UXJnpF3FUDUS',0,1,0,'beta.alumno3@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(25,7,NULL,'Alumno 4','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno4','$2y$04$D6GB96LmGuTN0kGHul70mulVXzq55vXV9BQjXQ40BYNDY6r6t0Q.S',0,1,0,'beta.alumno4@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(26,7,NULL,'Alumno 5','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno5','$2y$04$/lpGdtuwoH1QIe0hqeNmkeS2Vg.Bfve7q7TcwjTvNqQW0fEjWGzxG',0,1,0,'beta.alumno5@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(27,7,NULL,'Alumno 6','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno6','$2y$04$BBmuDWWVSEg6wbKBAE4Qj.w8YvoQnsZavbu6.OGYDnnTUvtR4R2hu',0,1,0,'beta.alumno6@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(28,7,NULL,'Alumno 7','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno7','$2y$04$tKk80QDqmwXfihiDgm1qeON6iP0QIRtVgfVhmZC1JKrmu1ML4jL6i',0,1,0,'beta.alumno7@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(29,7,NULL,'Alumno 8','Estudiante Beta',NULL,NULL,0,'INS','beta.alumno8','$2y$04$jFsPWNrjsd95hmdsmwIC2.2Mg.n.DjlMT1UBxS5BCPy//S/QEbeZi',0,1,0,'beta.alumno8@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(30,8,NULL,'Tutor','Laboral Uno Alfa',NULL,NULL,0,'INS','alfa.tutorlaboral','$2y$04$aU5djZQwEZeVHPnukJ0rluXxbuWqE/aeZEwKByCYbMynYmk7EQPGi',0,1,0,'alfa.tutorlaboral@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(31,8,NULL,'Tutora','Laboral Dos Alfa',NULL,NULL,0,'INS','alfa.tutorlaboral2','$2y$04$XEjg56QZ2FhE8NkEPyVusOOJJnOev1TwLBe0SpT6uwqUtSBV5Tf4m',0,1,0,'alfa.tutorlaboral2@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(32,8,NULL,'Tutor','Laboral Uno Beta',NULL,NULL,0,'INS','beta.tutorlaboral','$2y$04$lh2yIrGapcKC1/IEa0JEGetbYqkj22npAjbtPqUp2fODLJn9ZimQu',0,1,0,'beta.tutorlaboral@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(33,8,NULL,'Tutora','Laboral Dos Beta',NULL,NULL,0,'INS','beta.tutorlaboral2','$2y$04$Uye3t1uHSMIAqPdmU.NiH.7wecwoW/dbSUtmk8.d.PcEinvmp2HN6',0,1,0,'beta.tutorlaboral2@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(34,9,NULL,'Coordinador','Dual Alfa',NULL,NULL,0,'INS','alfa.coordinador','$2y$04$oh9u8/ohOLl/owhe7p8EHOUL/usW6MUg4CZKYjJmMjm.YP1Hy34P2',0,1,0,'alfa.coordinador@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(35,9,NULL,'Coordinador','Dual Beta',NULL,NULL,0,'INS','beta.coordinador','$2y$04$I2dOMbnVccoBfrGJgPqFxe2T09r3Z3FHbZRGHSyHMfGSjUkWOAJlq',0,1,0,'beta.coordinador@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(36,10,NULL,'Gestor','FFE Alfa',NULL,NULL,0,'INS','alfa.gestorffe','$2y$04$9oD7dnXbtZyOHt15AZw1huuLwc1v.TUok5Ph2jkCMRbjRu8jEh.cO',0,1,0,'alfa.gestorffe@example.com',NULL,NULL,NULL,NULL,NULL,0,0),(37,10,NULL,'Gestor','FFE Beta',NULL,NULL,0,'INS','beta.gestorffe','$2y$04$3mvYgfTbfS/teNCvrqr7mOvGkDfSDyJS9F3A78gA8i9P2KBagWjzq',0,1,0,'beta.gestorffe@example.com',NULL,NULL,NULL,NULL,NULL,0,0);
/*!40000 ALTER TABLE `person_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revisions`
--

DROP TABLE IF EXISTS `revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revisions`
--

LOCK TABLES `revisions` WRITE;
/*!40000 ALTER TABLE `revisions` DISABLE KEYS */;
INSERT INTO `revisions` VALUES (1,'2026-10-27 23:57:30',''),(2,'2026-10-27 23:57:30',''),(3,'2026-10-27 23:57:30',''),(4,'2026-10-27 23:57:30',''),(5,'2026-10-27 23:57:30',''),(6,'2026-10-27 23:57:30',''),(7,'2026-10-27 23:57:30',''),(8,'2026-10-27 23:57:30',''),(9,'2026-10-27 23:57:30',''),(10,'2026-10-27 23:57:31','');
/*!40000 ALTER TABLE `revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_id` int NOT NULL,
  `organization_id` int NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_57698A6A217BBB4732C8A3DE57698A6A` (`person_id`,`organization_id`,`role`),
  KEY `IDX_57698A6A217BBB47` (`person_id`),
  KEY `IDX_57698A6A32C8A3DE` (`organization_id`),
  CONSTRAINT `FK_57698A6A217BBB47` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_57698A6A32C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,2,1,'LOCAL_ADMIN'),(2,3,2,'LOCAL_ADMIN');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_audit`
--

DROP TABLE IF EXISTS `role_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `person_id` int DEFAULT NULL,
  `organization_id` int DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_92317bf7adb4788531df0b1cb910b5fc_idx` (`rev`),
  CONSTRAINT `rev_92317bf7adb4788531df0b1cb910b5fc_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_audit`
--

LOCK TABLES `role_audit` WRITE;
/*!40000 ALTER TABLE `role_audit` DISABLE KEYS */;
INSERT INTO `role_audit` VALUES (1,3,2,1,'LOCAL_ADMIN','INS'),(2,5,3,2,'LOCAL_ADMIN','INS');
/*!40000 ALTER TABLE `role_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey`
--

DROP TABLE IF EXISTS `survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `start_timestamp` datetime DEFAULT NULL,
  `end_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AD5F9BFC32C8A3DE` (`organization_id`),
  CONSTRAINT `FK_AD5F9BFC32C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey`
--

LOCK TABLES `survey` WRITE;
/*!40000 ALTER TABLE `survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_audit`
--

DROP TABLE IF EXISTS `survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `organization_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `start_timestamp` datetime DEFAULT NULL,
  `end_timestamp` datetime DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_0b043444544b35c998515597d9c72406_idx` (`rev`),
  CONSTRAINT `rev_0b043444544b35c998515597d9c72406_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_audit`
--

LOCK TABLES `survey_audit` WRITE;
/*!40000 ALTER TABLE `survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_question`
--

DROP TABLE IF EXISTS `survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `mandatory` tinyint(1) NOT NULL,
  `order_nr` int NOT NULL,
  `items` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_EA000F69B3FE509D` (`survey_id`),
  CONSTRAINT `FK_EA000F69B3FE509D` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_question`
--

LOCK TABLES `survey_question` WRITE;
/*!40000 ALTER TABLE `survey_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_question_audit`
--

DROP TABLE IF EXISTS `survey_question_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_question_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `survey_id` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci,
  `type` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `mandatory` tinyint(1) DEFAULT NULL,
  `order_nr` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `items` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_c3e747f7590f2b4f4a7532c099a72b37_idx` (`rev`),
  CONSTRAINT `rev_c3e747f7590f2b4f4a7532c099a72b37_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_question_audit`
--

LOCK TABLES `survey_question_audit` WRITE;
/*!40000 ALTER TABLE `survey_question_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_question_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_activity`
--

DROP TABLE IF EXISTS `wlt_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  `prior_learning` longtext COLLATE utf8mb4_spanish_ci,
  `project_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_EAD6D79D166D1F9C77153098` (`project_id`,`code`),
  KEY `IDX_EAD6D79D166D1F9C` (`project_id`),
  CONSTRAINT `FK_EAD6D79D166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_activity`
--

LOCK TABLES `wlt_activity` WRITE;
/*!40000 ALTER TABLE `wlt_activity` DISABLE KEYS */;
INSERT INTO `wlt_activity` VALUES (1,'A1','Desarrollo de un portal web','Conocimientos básicos de programación',1),(2,'A2','Mantenimiento de bases de datos','Conocimientos básicos de programación',1),(3,'A1','Desarrollo de un portal web','Conocimientos básicos de programación',2),(4,'A2','Mantenimiento de bases de datos','Conocimientos básicos de programación',2);
/*!40000 ALTER TABLE `wlt_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_activity_competency`
--

DROP TABLE IF EXISTS `wlt_activity_competency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_activity_competency` (
  `activity_id` int NOT NULL,
  `competency_id` int NOT NULL,
  PRIMARY KEY (`activity_id`,`competency_id`),
  KEY `IDX_39DDED3181C06096` (`activity_id`),
  KEY `IDX_39DDED31FB9F58C` (`competency_id`),
  CONSTRAINT `FK_39DDED3181C06096` FOREIGN KEY (`activity_id`) REFERENCES `wlt_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_39DDED31FB9F58C` FOREIGN KEY (`competency_id`) REFERENCES `edu_competency` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_activity_competency`
--

LOCK TABLES `wlt_activity_competency` WRITE;
/*!40000 ALTER TABLE `wlt_activity_competency` DISABLE KEYS */;
INSERT INTO `wlt_activity_competency` VALUES (1,1),(2,1),(3,2),(4,2);
/*!40000 ALTER TABLE `wlt_activity_competency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_activity_realization`
--

DROP TABLE IF EXISTS `wlt_activity_realization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_activity_realization` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_BA14088981C0609677153098` (`activity_id`,`code`),
  KEY `IDX_BA14088981C06096` (`activity_id`),
  CONSTRAINT `FK_BA14088981C06096` FOREIGN KEY (`activity_id`) REFERENCES `wlt_activity` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_activity_realization`
--

LOCK TABLES `wlt_activity_realization` WRITE;
/*!40000 ALTER TABLE `wlt_activity_realization` DISABLE KEYS */;
INSERT INTO `wlt_activity_realization` VALUES (1,1,'A1.1','Tarea 1 de Desarrollo de un portal web'),(2,1,'A1.2','Tarea 2 de Desarrollo de un portal web'),(3,2,'A2.1','Tarea 1 de Mantenimiento de bases de datos'),(4,2,'A2.2','Tarea 2 de Mantenimiento de bases de datos'),(5,3,'A1.1','Tarea 1 de Desarrollo de un portal web'),(6,3,'A1.2','Tarea 2 de Desarrollo de un portal web'),(7,4,'A2.1','Tarea 1 de Mantenimiento de bases de datos'),(8,4,'A2.2','Tarea 2 de Mantenimiento de bases de datos');
/*!40000 ALTER TABLE `wlt_activity_realization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_activity_realization_learning_outcome`
--

DROP TABLE IF EXISTS `wlt_activity_realization_learning_outcome`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_activity_realization_learning_outcome` (
  `activity_realization_id` int NOT NULL,
  `learning_outcome_id` int NOT NULL,
  PRIMARY KEY (`activity_realization_id`,`learning_outcome_id`),
  KEY `IDX_C17FFB1E862E876A` (`activity_realization_id`),
  KEY `IDX_C17FFB1E35C2B2D5` (`learning_outcome_id`),
  CONSTRAINT `FK_C17FFB1E35C2B2D5` FOREIGN KEY (`learning_outcome_id`) REFERENCES `edu_learning_outcome` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C17FFB1E862E876A` FOREIGN KEY (`activity_realization_id`) REFERENCES `wlt_activity_realization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_activity_realization_learning_outcome`
--

LOCK TABLES `wlt_activity_realization_learning_outcome` WRITE;
/*!40000 ALTER TABLE `wlt_activity_realization_learning_outcome` DISABLE KEYS */;
INSERT INTO `wlt_activity_realization_learning_outcome` VALUES (1,3),(2,3),(3,3),(4,3),(5,6),(6,6),(7,6),(8,6);
/*!40000 ALTER TABLE `wlt_activity_realization_learning_outcome` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_agreement`
--

DROP TABLE IF EXISTS `wlt_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_agreement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `workcenter_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `work_tutor_id` int NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `default_start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `project_id` int NOT NULL,
  `educational_tutor_id` int NOT NULL,
  `additional_work_tutor_id` int DEFAULT NULL,
  `additional_educational_tutor_id` int DEFAULT NULL,
  `work_tutor_remarks` longtext COLLATE utf8mb4_spanish_ci,
  `internal_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2B23AFE9166D1F9CDAE14AC5A2473C4B` (`project_id`,`student_enrollment_id`,`workcenter_id`),
  KEY `IDX_2B23AFE9A2473C4B` (`workcenter_id`),
  KEY `IDX_2B23AFE9DAE14AC5` (`student_enrollment_id`),
  KEY `IDX_2B23AFE9F53AEEAD` (`work_tutor_id`),
  KEY `IDX_2B23AFE9166D1F9C` (`project_id`),
  KEY `IDX_2B23AFE9E7F72E80` (`educational_tutor_id`),
  KEY `IDX_2B23AFE9364A8C43` (`additional_work_tutor_id`),
  KEY `IDX_2B23AFE96C7EFCBC` (`additional_educational_tutor_id`),
  CONSTRAINT `FK_2B23AFE9166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_2B23AFE9364A8C43` FOREIGN KEY (`additional_work_tutor_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_2B23AFE96C7EFCBC` FOREIGN KEY (`additional_educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_2B23AFE9A2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`),
  CONSTRAINT `FK_2B23AFE9DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`),
  CONSTRAINT `FK_2B23AFE9E7F72E80` FOREIGN KEY (`educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_2B23AFE9F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_agreement`
--

LOCK TABLES `wlt_agreement` WRITE;
/*!40000 ALTER TABLE `wlt_agreement` DISABLE KEYS */;
INSERT INTO `wlt_agreement` VALUES (1,1,5,30,'2026-09-15','2026-11-24',NULL,NULL,NULL,NULL,1,4,NULL,NULL,NULL,'DUAL-1'),(2,3,6,31,'2026-09-15','2026-11-24',NULL,NULL,NULL,NULL,1,5,NULL,NULL,NULL,'DUAL-2'),(3,1,13,32,'2026-09-15','2026-11-24',NULL,NULL,NULL,NULL,2,9,NULL,NULL,NULL,'DUAL-1'),(4,3,14,33,'2026-09-15','2026-11-24',NULL,NULL,NULL,NULL,2,10,NULL,NULL,NULL,'DUAL-2');
/*!40000 ALTER TABLE `wlt_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_agreement_activity_realization`
--

DROP TABLE IF EXISTS `wlt_agreement_activity_realization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_agreement_activity_realization` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `activity_realization_id` int NOT NULL,
  `grade_id` int DEFAULT NULL,
  `graded_by_id` int DEFAULT NULL,
  `graded_on` date DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BD86F07824890B2B` (`agreement_id`),
  KEY `IDX_BD86F078862E876A` (`activity_realization_id`),
  KEY `IDX_BD86F078FE19A1A8` (`grade_id`),
  KEY `IDX_BD86F078C814BC2E` (`graded_by_id`),
  CONSTRAINT `FK_BD86F07824890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wlt_agreement` (`id`),
  CONSTRAINT `FK_BD86F078862E876A` FOREIGN KEY (`activity_realization_id`) REFERENCES `wlt_activity_realization` (`id`),
  CONSTRAINT `FK_BD86F078C814BC2E` FOREIGN KEY (`graded_by_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_BD86F078FE19A1A8` FOREIGN KEY (`grade_id`) REFERENCES `edu_performance_scale_value` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_agreement_activity_realization`
--

LOCK TABLES `wlt_agreement_activity_realization` WRITE;
/*!40000 ALTER TABLE `wlt_agreement_activity_realization` DISABLE KEYS */;
INSERT INTO `wlt_agreement_activity_realization` VALUES (1,1,1,3,7,'2026-10-27',0),(2,1,2,NULL,NULL,NULL,0),(3,1,3,3,7,'2026-10-27',0),(4,1,4,NULL,NULL,NULL,0),(5,2,1,3,7,'2026-10-27',0),(6,2,2,NULL,NULL,NULL,0),(7,2,3,3,7,'2026-10-27',0),(8,2,4,NULL,NULL,NULL,0),(9,3,5,6,20,'2026-10-27',0),(10,3,6,NULL,NULL,NULL,0),(11,3,7,6,20,'2026-10-27',0),(12,3,8,NULL,NULL,NULL,0),(13,4,5,6,20,'2026-10-27',0),(14,4,6,NULL,NULL,NULL,0),(15,4,7,6,20,'2026-10-27',0),(16,4,8,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `wlt_agreement_activity_realization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_agreement_activity_realization_audit`
--

DROP TABLE IF EXISTS `wlt_agreement_activity_realization_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_agreement_activity_realization_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `agreement_id` int DEFAULT NULL,
  `activity_realization_id` int DEFAULT NULL,
  `grade_id` int DEFAULT NULL,
  `graded_by_id` int DEFAULT NULL,
  `graded_on` date DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `disabled` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_3aa810fb5b23d95bfecc473791749abf_idx` (`rev`),
  CONSTRAINT `rev_3aa810fb5b23d95bfecc473791749abf_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_agreement_activity_realization_audit`
--

LOCK TABLES `wlt_agreement_activity_realization_audit` WRITE;
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_audit` DISABLE KEYS */;
INSERT INTO `wlt_agreement_activity_realization_audit` VALUES (1,9,1,1,3,7,'2026-10-27','INS',0),(2,9,1,2,NULL,NULL,NULL,'INS',0),(3,9,1,3,3,7,'2026-10-27','INS',0),(4,9,1,4,NULL,NULL,NULL,'INS',0),(5,9,2,1,3,7,'2026-10-27','INS',0),(6,9,2,2,NULL,NULL,NULL,'INS',0),(7,9,2,3,3,7,'2026-10-27','INS',0),(8,9,2,4,NULL,NULL,NULL,'INS',0),(9,9,3,5,6,20,'2026-10-27','INS',0),(10,9,3,6,NULL,NULL,NULL,'INS',0),(11,9,3,7,6,20,'2026-10-27','INS',0),(12,9,3,8,NULL,NULL,NULL,'INS',0),(13,9,4,5,6,20,'2026-10-27','INS',0),(14,9,4,6,NULL,NULL,NULL,'INS',0),(15,9,4,7,6,20,'2026-10-27','INS',0),(16,9,4,8,NULL,NULL,NULL,'INS',0);
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_agreement_activity_realization_comment`
--

DROP TABLE IF EXISTS `wlt_agreement_activity_realization_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_agreement_activity_realization_comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_activity_realization_id` int NOT NULL,
  `person_id` int NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB49388DDEF5E775` (`agreement_activity_realization_id`),
  KEY `IDX_DB49388D217BBB47` (`person_id`),
  CONSTRAINT `FK_DB49388D217BBB47` FOREIGN KEY (`person_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_DB49388DDEF5E775` FOREIGN KEY (`agreement_activity_realization_id`) REFERENCES `wlt_agreement_activity_realization` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_agreement_activity_realization_comment`
--

LOCK TABLES `wlt_agreement_activity_realization_comment` WRITE;
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_comment` DISABLE KEYS */;
INSERT INTO `wlt_agreement_activity_realization_comment` VALUES (1,1,7,'Realización correcta de la tarea','2026-10-27 00:00:00'),(2,5,7,'Realización correcta de la tarea','2026-10-27 00:00:00'),(3,9,20,'Realización correcta de la tarea','2026-10-27 00:00:00'),(4,13,20,'Realización correcta de la tarea','2026-10-27 00:00:00');
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_agreement_activity_realization_comment_audit`
--

DROP TABLE IF EXISTS `wlt_agreement_activity_realization_comment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_agreement_activity_realization_comment_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `agreement_activity_realization_id` int DEFAULT NULL,
  `person_id` int DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `timestamp` datetime DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_935c6eb03972c07b2fc1ea23e44b8f95_idx` (`rev`),
  CONSTRAINT `rev_935c6eb03972c07b2fc1ea23e44b8f95_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_agreement_activity_realization_comment_audit`
--

LOCK TABLES `wlt_agreement_activity_realization_comment_audit` WRITE;
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_comment_audit` DISABLE KEYS */;
INSERT INTO `wlt_agreement_activity_realization_comment_audit` VALUES (1,9,1,7,'Realización correcta de la tarea','2026-10-27 00:00:00','INS'),(2,9,5,7,'Realización correcta de la tarea','2026-10-27 00:00:00','INS'),(3,9,9,20,'Realización correcta de la tarea','2026-10-27 00:00:00','INS'),(4,9,13,20,'Realización correcta de la tarea','2026-10-27 00:00:00','INS');
/*!40000 ALTER TABLE `wlt_agreement_activity_realization_comment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact`
--

DROP TABLE IF EXISTS `wlt_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `workcenter_id` int NOT NULL,
  `date_time` datetime NOT NULL,
  `detail` longtext COLLATE utf8mb4_spanish_ci,
  `method_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8702589741807E1D` (`teacher_id`),
  KEY `IDX_87025897A2473C4B` (`workcenter_id`),
  KEY `IDX_8702589719883967` (`method_id`),
  CONSTRAINT `FK_8702589719883967` FOREIGN KEY (`method_id`) REFERENCES `edu_contact_method` (`id`),
  CONSTRAINT `FK_8702589741807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_87025897A2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact`
--

LOCK TABLES `wlt_contact` WRITE;
/*!40000 ALTER TABLE `wlt_contact` DISABLE KEYS */;
INSERT INTO `wlt_contact` VALUES (1,4,1,'2026-10-17 10:00:00','<p>Visita al centro de trabajo del <b>alumnado dual</b>.</p>',1),(2,9,1,'2026-10-17 10:00:00','<p>Visita al centro de trabajo del <b>alumnado dual</b>.</p>',3);
/*!40000 ALTER TABLE `wlt_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact_audit`
--

DROP TABLE IF EXISTS `wlt_contact_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `workcenter_id` int DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `detail` longtext COLLATE utf8mb4_spanish_ci,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `method_id` int DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_1a3b3959a5ced6400bc91d305339a7c0_idx` (`rev`),
  CONSTRAINT `rev_1a3b3959a5ced6400bc91d305339a7c0_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact_audit`
--

LOCK TABLES `wlt_contact_audit` WRITE;
/*!40000 ALTER TABLE `wlt_contact_audit` DISABLE KEYS */;
INSERT INTO `wlt_contact_audit` VALUES (1,9,4,1,'2026-10-17 10:00:00','<p>Visita al centro de trabajo del <b>alumnado dual</b>.</p>','INS',1),(2,9,9,1,'2026-10-17 10:00:00','<p>Visita al centro de trabajo del <b>alumnado dual</b>.</p>','INS',3);
/*!40000 ALTER TABLE `wlt_contact_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact_project`
--

DROP TABLE IF EXISTS `wlt_contact_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact_project` (
  `contact_id` int NOT NULL,
  `project_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`project_id`),
  KEY `IDX_208AE622E7A1254A` (`contact_id`),
  KEY `IDX_208AE622166D1F9C` (`project_id`),
  CONSTRAINT `FK_208AE622166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_208AE622E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `wlt_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact_project`
--

LOCK TABLES `wlt_contact_project` WRITE;
/*!40000 ALTER TABLE `wlt_contact_project` DISABLE KEYS */;
INSERT INTO `wlt_contact_project` VALUES (1,1),(2,2);
/*!40000 ALTER TABLE `wlt_contact_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact_project_audit`
--

DROP TABLE IF EXISTS `wlt_contact_project_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact_project_audit` (
  `contact_id` int NOT NULL,
  `project_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`project_id`,`rev`),
  KEY `rev_abc3d380c2aa42af58909c1fd4f4dc82_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact_project_audit`
--

LOCK TABLES `wlt_contact_project_audit` WRITE;
/*!40000 ALTER TABLE `wlt_contact_project_audit` DISABLE KEYS */;
INSERT INTO `wlt_contact_project_audit` VALUES (1,1,9,'INS'),(2,2,9,'INS');
/*!40000 ALTER TABLE `wlt_contact_project_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact_student_enrollment`
--

DROP TABLE IF EXISTS `wlt_contact_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact_student_enrollment` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`),
  KEY `IDX_A3EF12EDE7A1254A` (`contact_id`),
  KEY `IDX_A3EF12EDDAE14AC5` (`student_enrollment_id`),
  CONSTRAINT `FK_A3EF12EDDAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A3EF12EDE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `wlt_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact_student_enrollment`
--

LOCK TABLES `wlt_contact_student_enrollment` WRITE;
/*!40000 ALTER TABLE `wlt_contact_student_enrollment` DISABLE KEYS */;
INSERT INTO `wlt_contact_student_enrollment` VALUES (1,5),(2,13);
/*!40000 ALTER TABLE `wlt_contact_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_contact_student_enrollment_audit`
--

DROP TABLE IF EXISTS `wlt_contact_student_enrollment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_contact_student_enrollment_audit` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`,`rev`),
  KEY `rev_e0bd3edfb4c855c59d87e3bcba7e9825_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_contact_student_enrollment_audit`
--

LOCK TABLES `wlt_contact_student_enrollment_audit` WRITE;
/*!40000 ALTER TABLE `wlt_contact_student_enrollment_audit` DISABLE KEYS */;
INSERT INTO `wlt_contact_student_enrollment_audit` VALUES (1,5,9,'INS'),(2,13,9,'INS');
/*!40000 ALTER TABLE `wlt_contact_student_enrollment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_educational_tutor_answered_survey`
--

DROP TABLE IF EXISTS `wlt_educational_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_educational_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CBD11B75166D1F9C` (`project_id`),
  KEY `IDX_CBD11B75A97283E6` (`answered_survey_id`),
  KEY `IDX_CBD11B7541807E1D` (`teacher_id`),
  CONSTRAINT `FK_CBD11B75166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_CBD11B7541807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_CBD11B75A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_educational_tutor_answered_survey`
--

LOCK TABLES `wlt_educational_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `wlt_educational_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_educational_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_educational_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `wlt_educational_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_educational_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_68c9c40ff36ffd2b1378aa4b2746dcac_idx` (`rev`),
  CONSTRAINT `rev_68c9c40ff36ffd2b1378aa4b2746dcac_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_educational_tutor_answered_survey_audit`
--

LOCK TABLES `wlt_educational_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wlt_educational_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_educational_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_learning_program`
--

DROP TABLE IF EXISTS `wlt_learning_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_learning_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `project_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5EB2E44D979B1AD6` (`company_id`),
  KEY `IDX_5EB2E44D166D1F9C` (`project_id`),
  CONSTRAINT `FK_5EB2E44D166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_5EB2E44D979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_learning_program`
--

LOCK TABLES `wlt_learning_program` WRITE;
/*!40000 ALTER TABLE `wlt_learning_program` DISABLE KEYS */;
INSERT INTO `wlt_learning_program` VALUES (1,1,1),(2,1,2);
/*!40000 ALTER TABLE `wlt_learning_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_learning_program_activity_realization`
--

DROP TABLE IF EXISTS `wlt_learning_program_activity_realization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_learning_program_activity_realization` (
  `learning_program_id` int NOT NULL,
  `activity_realization_id` int NOT NULL,
  PRIMARY KEY (`learning_program_id`,`activity_realization_id`),
  KEY `IDX_7EADE17BED94D8BC` (`learning_program_id`),
  KEY `IDX_7EADE17B862E876A` (`activity_realization_id`),
  CONSTRAINT `FK_7EADE17B862E876A` FOREIGN KEY (`activity_realization_id`) REFERENCES `wlt_activity_realization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_7EADE17BED94D8BC` FOREIGN KEY (`learning_program_id`) REFERENCES `wlt_learning_program` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_learning_program_activity_realization`
--

LOCK TABLES `wlt_learning_program_activity_realization` WRITE;
/*!40000 ALTER TABLE `wlt_learning_program_activity_realization` DISABLE KEYS */;
INSERT INTO `wlt_learning_program_activity_realization` VALUES (1,1),(1,2),(1,3),(1,4),(2,5),(2,6),(2,7),(2,8);
/*!40000 ALTER TABLE `wlt_learning_program_activity_realization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_learning_program_activity_realization_audit`
--

DROP TABLE IF EXISTS `wlt_learning_program_activity_realization_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_learning_program_activity_realization_audit` (
  `learning_program_id` int NOT NULL,
  `activity_realization_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`learning_program_id`,`activity_realization_id`,`rev`),
  KEY `rev_ea7fce12514ea7fc1d7a89d6eedaccbf_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_learning_program_activity_realization_audit`
--

LOCK TABLES `wlt_learning_program_activity_realization_audit` WRITE;
/*!40000 ALTER TABLE `wlt_learning_program_activity_realization_audit` DISABLE KEYS */;
INSERT INTO `wlt_learning_program_activity_realization_audit` VALUES (1,1,9,'INS'),(1,2,9,'INS'),(1,3,9,'INS'),(1,4,9,'INS'),(2,5,9,'INS'),(2,6,9,'INS'),(2,7,9,'INS'),(2,8,9,'INS');
/*!40000 ALTER TABLE `wlt_learning_program_activity_realization_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_learning_program_audit`
--

DROP TABLE IF EXISTS `wlt_learning_program_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_learning_program_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `company_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `project_id` int DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_3e4a8a47037e8657861e40d29d7511a6_idx` (`rev`),
  CONSTRAINT `rev_3e4a8a47037e8657861e40d29d7511a6_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_learning_program_audit`
--

LOCK TABLES `wlt_learning_program_audit` WRITE;
/*!40000 ALTER TABLE `wlt_learning_program_audit` DISABLE KEYS */;
INSERT INTO `wlt_learning_program_audit` VALUES (1,9,1,'INS',1),(2,9,1,'INS',2);
/*!40000 ALTER TABLE `wlt_learning_program_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting`
--

DROP TABLE IF EXISTS `wlt_meeting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_id` int NOT NULL,
  `date_time` datetime NOT NULL,
  `detail` longtext COLLATE utf8mb4_spanish_ci,
  `project_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3E755F96B03A8386` (`created_by_id`),
  KEY `IDX_3E755F96166D1F9C` (`project_id`),
  CONSTRAINT `FK_3E755F96166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_3E755F96B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `edu_teacher` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting`
--

LOCK TABLES `wlt_meeting` WRITE;
/*!40000 ALTER TABLE `wlt_meeting` DISABLE KEYS */;
INSERT INTO `wlt_meeting` VALUES (1,4,'2026-10-06 12:00:00','<p>Reunión de <b>seguimiento</b> del proyecto.</p>',1),(2,9,'2026-10-06 12:00:00','<p>Reunión de <b>seguimiento</b> del proyecto.</p>',2);
/*!40000 ALTER TABLE `wlt_meeting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting_audit`
--

DROP TABLE IF EXISTS `wlt_meeting_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `detail` longtext COLLATE utf8mb4_spanish_ci,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `project_id` int DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_4f5b29d8966971ed79a052aacd98c68f_idx` (`rev`),
  CONSTRAINT `rev_4f5b29d8966971ed79a052aacd98c68f_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting_audit`
--

LOCK TABLES `wlt_meeting_audit` WRITE;
/*!40000 ALTER TABLE `wlt_meeting_audit` DISABLE KEYS */;
INSERT INTO `wlt_meeting_audit` VALUES (1,9,4,'2026-10-06 12:00:00','<p>Reunión de <b>seguimiento</b> del proyecto.</p>','INS',1),(2,9,9,'2026-10-06 12:00:00','<p>Reunión de <b>seguimiento</b> del proyecto.</p>','INS',2);
/*!40000 ALTER TABLE `wlt_meeting_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting_student_enrollment`
--

DROP TABLE IF EXISTS `wlt_meeting_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting_student_enrollment` (
  `meeting_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  PRIMARY KEY (`meeting_id`,`student_enrollment_id`),
  KEY `IDX_A19B8C2067433D9C` (`meeting_id`),
  KEY `IDX_A19B8C20DAE14AC5` (`student_enrollment_id`),
  CONSTRAINT `FK_A19B8C2067433D9C` FOREIGN KEY (`meeting_id`) REFERENCES `wlt_meeting` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A19B8C20DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting_student_enrollment`
--

LOCK TABLES `wlt_meeting_student_enrollment` WRITE;
/*!40000 ALTER TABLE `wlt_meeting_student_enrollment` DISABLE KEYS */;
INSERT INTO `wlt_meeting_student_enrollment` VALUES (1,5),(1,6),(2,13),(2,14);
/*!40000 ALTER TABLE `wlt_meeting_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting_student_enrollment_audit`
--

DROP TABLE IF EXISTS `wlt_meeting_student_enrollment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting_student_enrollment_audit` (
  `meeting_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`meeting_id`,`student_enrollment_id`,`rev`),
  KEY `rev_eec9e93b7a9dff9fef2970100c0b2da9_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting_student_enrollment_audit`
--

LOCK TABLES `wlt_meeting_student_enrollment_audit` WRITE;
/*!40000 ALTER TABLE `wlt_meeting_student_enrollment_audit` DISABLE KEYS */;
INSERT INTO `wlt_meeting_student_enrollment_audit` VALUES (1,5,9,'INS'),(1,6,9,'INS'),(2,13,9,'INS'),(2,14,9,'INS');
/*!40000 ALTER TABLE `wlt_meeting_student_enrollment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting_teacher`
--

DROP TABLE IF EXISTS `wlt_meeting_teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting_teacher` (
  `meeting_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  PRIMARY KEY (`meeting_id`,`teacher_id`),
  KEY `IDX_BD32F91567433D9C` (`meeting_id`),
  KEY `IDX_BD32F91541807E1D` (`teacher_id`),
  CONSTRAINT `FK_BD32F91541807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BD32F91567433D9C` FOREIGN KEY (`meeting_id`) REFERENCES `wlt_meeting` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting_teacher`
--

LOCK TABLES `wlt_meeting_teacher` WRITE;
/*!40000 ALTER TABLE `wlt_meeting_teacher` DISABLE KEYS */;
INSERT INTO `wlt_meeting_teacher` VALUES (1,4),(2,9);
/*!40000 ALTER TABLE `wlt_meeting_teacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_meeting_teacher_audit`
--

DROP TABLE IF EXISTS `wlt_meeting_teacher_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_meeting_teacher_audit` (
  `meeting_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`meeting_id`,`teacher_id`,`rev`),
  KEY `rev_188d4938230f9bff872496b972c09981_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_meeting_teacher_audit`
--

LOCK TABLES `wlt_meeting_teacher_audit` WRITE;
/*!40000 ALTER TABLE `wlt_meeting_teacher_audit` DISABLE KEYS */;
INSERT INTO `wlt_meeting_teacher_audit` VALUES (1,4,9,'INS'),(2,9,9,'INS');
/*!40000 ALTER TABLE `wlt_meeting_teacher_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project`
--

DROP TABLE IF EXISTS `wlt_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `manager_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `performance_scale_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E4D36E4132C8A3DE` (`organization_id`),
  KEY `IDX_E4D36E41783E3463` (`manager_id`),
  KEY `IDX_E4D36E41D490911D` (`student_survey_id`),
  KEY `IDX_E4D36E4180E5DA6D` (`company_survey_id`),
  KEY `IDX_E4D36E4168F6798B` (`educational_tutor_survey_id`),
  KEY `IDX_E4D36E4113E6472B` (`attendance_report_template_id`),
  KEY `IDX_E4D36E413BBF8EEC` (`weekly_activity_report_template_id`),
  KEY `IDX_E4D36E417C2C26C0` (`performance_scale_id`),
  CONSTRAINT `FK_E4D36E4113E6472B` FOREIGN KEY (`attendance_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_E4D36E4132C8A3DE` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`),
  CONSTRAINT `FK_E4D36E413BBF8EEC` FOREIGN KEY (`weekly_activity_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_E4D36E4168F6798B` FOREIGN KEY (`educational_tutor_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E4D36E41783E3463` FOREIGN KEY (`manager_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_E4D36E417C2C26C0` FOREIGN KEY (`performance_scale_id`) REFERENCES `edu_performance_scale` (`id`),
  CONSTRAINT `FK_E4D36E4180E5DA6D` FOREIGN KEY (`company_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_E4D36E41D490911D` FOREIGN KEY (`student_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project`
--

LOCK TABLES `wlt_project` WRITE;
/*!40000 ALTER TABLE `wlt_project` DISABLE KEYS */;
INSERT INTO `wlt_project` VALUES (1,1,34,'Proyecto de FP dual en Desarrollo de Aplicaciones',NULL,NULL,NULL,NULL,NULL,0,1),(2,2,35,'Proyecto de FP dual en Desarrollo de Aplicaciones',NULL,NULL,NULL,NULL,NULL,0,2);
/*!40000 ALTER TABLE `wlt_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project_audit`
--

DROP TABLE IF EXISTS `wlt_project_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `organization_id` int DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  `performance_scale_id` int DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_e55594229ab1a86fcf85548ae1e37a5e_idx` (`rev`),
  CONSTRAINT `rev_e55594229ab1a86fcf85548ae1e37a5e_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project_audit`
--

LOCK TABLES `wlt_project_audit` WRITE;
/*!40000 ALTER TABLE `wlt_project_audit` DISABLE KEYS */;
INSERT INTO `wlt_project_audit` VALUES (1,9,1,34,NULL,NULL,'Proyecto de FP dual en Desarrollo de Aplicaciones','INS',NULL,NULL,NULL,0,1),(2,9,2,35,NULL,NULL,'Proyecto de FP dual en Desarrollo de Aplicaciones','INS',NULL,NULL,NULL,0,2);
/*!40000 ALTER TABLE `wlt_project_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project_group`
--

DROP TABLE IF EXISTS `wlt_project_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project_group` (
  `project_id` int NOT NULL,
  `group_id` int NOT NULL,
  PRIMARY KEY (`project_id`,`group_id`),
  KEY `IDX_9FF4D6B7166D1F9C` (`project_id`),
  KEY `IDX_9FF4D6B7FE54D947` (`group_id`),
  CONSTRAINT `FK_9FF4D6B7166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9FF4D6B7FE54D947` FOREIGN KEY (`group_id`) REFERENCES `edu_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project_group`
--

LOCK TABLES `wlt_project_group` WRITE;
/*!40000 ALTER TABLE `wlt_project_group` DISABLE KEYS */;
INSERT INTO `wlt_project_group` VALUES (1,2),(2,4);
/*!40000 ALTER TABLE `wlt_project_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project_group_audit`
--

DROP TABLE IF EXISTS `wlt_project_group_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project_group_audit` (
  `project_id` int NOT NULL,
  `group_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`project_id`,`group_id`,`rev`),
  KEY `rev_d9538b953230ccb18da4bab0b4de9237_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project_group_audit`
--

LOCK TABLES `wlt_project_group_audit` WRITE;
/*!40000 ALTER TABLE `wlt_project_group_audit` DISABLE KEYS */;
INSERT INTO `wlt_project_group_audit` VALUES (1,2,9,'INS'),(2,4,9,'INS');
/*!40000 ALTER TABLE `wlt_project_group_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project_student_enrollment`
--

DROP TABLE IF EXISTS `wlt_project_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project_student_enrollment` (
  `project_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  PRIMARY KEY (`project_id`,`student_enrollment_id`),
  KEY `IDX_E0458B76166D1F9C` (`project_id`),
  KEY `IDX_E0458B76DAE14AC5` (`student_enrollment_id`),
  CONSTRAINT `FK_E0458B76166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_E0458B76DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project_student_enrollment`
--

LOCK TABLES `wlt_project_student_enrollment` WRITE;
/*!40000 ALTER TABLE `wlt_project_student_enrollment` DISABLE KEYS */;
INSERT INTO `wlt_project_student_enrollment` VALUES (1,5),(1,6),(2,13),(2,14);
/*!40000 ALTER TABLE `wlt_project_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_project_student_enrollment_audit`
--

DROP TABLE IF EXISTS `wlt_project_student_enrollment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_project_student_enrollment_audit` (
  `project_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`project_id`,`student_enrollment_id`,`rev`),
  KEY `rev_50632f17d0233de6402b0980b438b5e1_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_project_student_enrollment_audit`
--

LOCK TABLES `wlt_project_student_enrollment_audit` WRITE;
/*!40000 ALTER TABLE `wlt_project_student_enrollment_audit` DISABLE KEYS */;
INSERT INTO `wlt_project_student_enrollment_audit` VALUES (1,5,9,'INS'),(1,6,9,'INS'),(2,13,9,'INS'),(2,14,9,'INS');
/*!40000 ALTER TABLE `wlt_project_student_enrollment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_student_answered_survey`
--

DROP TABLE IF EXISTS `wlt_student_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_student_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E4FA2DBC166D1F9C` (`project_id`),
  KEY `IDX_E4FA2DBCDAE14AC5` (`student_enrollment_id`),
  KEY `IDX_E4FA2DBCA97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_E4FA2DBC166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_E4FA2DBCA97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_E4FA2DBCDAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_student_answered_survey`
--

LOCK TABLES `wlt_student_answered_survey` WRITE;
/*!40000 ALTER TABLE `wlt_student_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_student_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_student_answered_survey_audit`
--

DROP TABLE IF EXISTS `wlt_student_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_student_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `student_enrollment_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_7233771bba2039be6047b538a296dbb3_idx` (`rev`),
  CONSTRAINT `rev_7233771bba2039be6047b538a296dbb3_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_student_answered_survey_audit`
--

LOCK TABLES `wlt_student_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wlt_student_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_student_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_tracking`
--

DROP TABLE IF EXISTS `wlt_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_tracking` (
  `work_day_id` int NOT NULL,
  `activity_realization_id` int NOT NULL,
  PRIMARY KEY (`work_day_id`,`activity_realization_id`),
  KEY `IDX_EEDEBCDBA23B8704` (`work_day_id`),
  KEY `IDX_EEDEBCDB862E876A` (`activity_realization_id`),
  CONSTRAINT `FK_EEDEBCDB862E876A` FOREIGN KEY (`activity_realization_id`) REFERENCES `wlt_activity_realization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EEDEBCDBA23B8704` FOREIGN KEY (`work_day_id`) REFERENCES `wlt_work_day` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_tracking`
--

LOCK TABLES `wlt_tracking` WRITE;
/*!40000 ALTER TABLE `wlt_tracking` DISABLE KEYS */;
INSERT INTO `wlt_tracking` VALUES (1,1),(1,2),(2,1),(2,2),(3,1),(3,2),(4,1),(4,2),(5,1),(5,2),(6,1),(6,2),(7,1),(7,2),(8,1),(8,2),(9,1),(9,2),(10,1),(10,2),(11,1),(11,2),(12,1),(12,2),(13,1),(13,2),(14,1),(14,2),(15,1),(15,2),(16,1),(16,2),(17,1),(17,2),(18,1),(18,2),(19,1),(19,2),(20,1),(20,2),(21,1),(21,2),(22,1),(22,2),(23,1),(23,2),(24,1),(24,2),(25,1),(25,2),(26,1),(26,2),(27,1),(27,2),(28,1),(28,2),(29,1),(29,2),(30,1),(30,2),(31,1),(31,2),(32,1),(32,2),(33,1),(33,2),(34,1),(34,2),(35,1),(35,2),(36,1),(36,2),(37,1),(37,2),(38,1),(38,2),(39,1),(39,2),(40,1),(40,2),(41,1),(41,2),(42,1),(42,2),(43,1),(43,2),(44,1),(44,2),(45,1),(45,2),(46,1),(46,2),(47,1),(47,2),(48,1),(48,2),(49,1),(49,2),(50,1),(50,2),(51,1),(51,2),(52,1),(52,2),(53,1),(53,2),(54,1),(54,2),(55,1),(55,2),(56,1),(56,2),(57,1),(57,2),(58,1),(58,2),(59,1),(59,2),(60,1),(60,2),(61,1),(61,2),(62,1),(62,2),(63,1),(63,2),(64,1),(64,2),(65,1),(65,2),(66,1),(66,2),(67,1),(67,2),(68,1),(68,2),(69,1),(69,2),(70,1),(70,2),(71,1),(71,2),(72,1),(72,2),(73,1),(73,2),(74,1),(74,2),(75,1),(75,2),(76,1),(76,2),(77,1),(77,2),(78,1),(78,2),(79,1),(79,2),(80,1),(80,2),(81,1),(81,2),(82,1),(82,2),(83,1),(83,2),(84,1),(84,2),(85,1),(85,2),(86,1),(86,2),(87,1),(87,2),(88,1),(88,2),(89,1),(89,2),(90,1),(90,2),(91,1),(91,2),(92,1),(92,2),(93,1),(93,2),(94,1),(94,2),(95,1),(95,2),(96,1),(96,2),(97,1),(97,2),(98,1),(98,2),(99,1),(99,2),(100,1),(100,2),(101,1),(101,2),(102,1),(102,2),(103,5),(103,6),(104,5),(104,6),(105,5),(105,6),(106,5),(106,6),(107,5),(107,6),(108,5),(108,6),(109,5),(109,6),(110,5),(110,6),(111,5),(111,6),(112,5),(112,6),(113,5),(113,6),(114,5),(114,6),(115,5),(115,6),(116,5),(116,6),(117,5),(117,6),(118,5),(118,6),(119,5),(119,6),(120,5),(120,6),(121,5),(121,6),(122,5),(122,6),(123,5),(123,6),(124,5),(124,6),(125,5),(125,6),(126,5),(126,6),(127,5),(127,6),(128,5),(128,6),(129,5),(129,6),(130,5),(130,6),(131,5),(131,6),(132,5),(132,6),(133,5),(133,6),(134,5),(134,6),(135,5),(135,6),(136,5),(136,6),(137,5),(137,6),(138,5),(138,6),(139,5),(139,6),(140,5),(140,6),(141,5),(141,6),(142,5),(142,6),(143,5),(143,6),(144,5),(144,6),(145,5),(145,6),(146,5),(146,6),(147,5),(147,6),(148,5),(148,6),(149,5),(149,6),(150,5),(150,6),(151,5),(151,6),(152,5),(152,6),(153,5),(153,6),(154,5),(154,6),(155,5),(155,6),(156,5),(156,6),(157,5),(157,6),(158,5),(158,6),(159,5),(159,6),(160,5),(160,6),(161,5),(161,6),(162,5),(162,6),(163,5),(163,6),(164,5),(164,6),(165,5),(165,6),(166,5),(166,6),(167,5),(167,6),(168,5),(168,6),(169,5),(169,6),(170,5),(170,6),(171,5),(171,6),(172,5),(172,6),(173,5),(173,6),(174,5),(174,6),(175,5),(175,6),(176,5),(176,6),(177,5),(177,6),(178,5),(178,6),(179,5),(179,6),(180,5),(180,6),(181,5),(181,6),(182,5),(182,6),(183,5),(183,6),(184,5),(184,6),(185,5),(185,6),(186,5),(186,6),(187,5),(187,6),(188,5),(188,6),(189,5),(189,6),(190,5),(190,6),(191,5),(191,6),(192,5),(192,6),(193,5),(193,6),(194,5),(194,6),(195,5),(195,6),(196,5),(196,6),(197,5),(197,6),(198,5),(198,6),(199,5),(199,6),(200,5),(200,6),(201,5),(201,6),(202,5),(202,6),(203,5),(203,6),(204,5),(204,6);
/*!40000 ALTER TABLE `wlt_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_tracking_audit`
--

DROP TABLE IF EXISTS `wlt_tracking_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_tracking_audit` (
  `work_day_id` int NOT NULL,
  `activity_realization_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`work_day_id`,`activity_realization_id`,`rev`),
  KEY `rev_401b520edfe354349e84f8011843482d_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_tracking_audit`
--

LOCK TABLES `wlt_tracking_audit` WRITE;
/*!40000 ALTER TABLE `wlt_tracking_audit` DISABLE KEYS */;
INSERT INTO `wlt_tracking_audit` VALUES (1,1,9,'INS'),(1,2,9,'INS'),(2,1,9,'INS'),(2,2,9,'INS'),(3,1,9,'INS'),(3,2,9,'INS'),(4,1,9,'INS'),(4,2,9,'INS'),(5,1,9,'INS'),(5,2,9,'INS'),(6,1,9,'INS'),(6,2,9,'INS'),(7,1,9,'INS'),(7,2,9,'INS'),(8,1,9,'INS'),(8,2,9,'INS'),(9,1,9,'INS'),(9,2,9,'INS'),(10,1,9,'INS'),(10,2,9,'INS'),(11,1,9,'INS'),(11,2,9,'INS'),(12,1,9,'INS'),(12,2,9,'INS'),(13,1,9,'INS'),(13,2,9,'INS'),(14,1,9,'INS'),(14,2,9,'INS'),(15,1,9,'INS'),(15,2,9,'INS'),(16,1,9,'INS'),(16,2,9,'INS'),(17,1,9,'INS'),(17,2,9,'INS'),(18,1,9,'INS'),(18,2,9,'INS'),(19,1,9,'INS'),(19,2,9,'INS'),(20,1,9,'INS'),(20,2,9,'INS'),(21,1,9,'INS'),(21,2,9,'INS'),(22,1,9,'INS'),(22,2,9,'INS'),(23,1,9,'INS'),(23,2,9,'INS'),(24,1,9,'INS'),(24,2,9,'INS'),(25,1,9,'INS'),(25,2,9,'INS'),(26,1,9,'INS'),(26,2,9,'INS'),(27,1,9,'INS'),(27,2,9,'INS'),(28,1,9,'INS'),(28,2,9,'INS'),(29,1,9,'INS'),(29,2,9,'INS'),(30,1,9,'INS'),(30,2,9,'INS'),(31,1,9,'INS'),(31,2,9,'INS'),(32,1,9,'INS'),(32,2,9,'INS'),(33,1,9,'INS'),(33,2,9,'INS'),(34,1,9,'INS'),(34,2,9,'INS'),(35,1,9,'INS'),(35,2,9,'INS'),(36,1,9,'INS'),(36,2,9,'INS'),(37,1,9,'INS'),(37,2,9,'INS'),(38,1,9,'INS'),(38,2,9,'INS'),(39,1,9,'INS'),(39,2,9,'INS'),(40,1,9,'INS'),(40,2,9,'INS'),(41,1,9,'INS'),(41,2,9,'INS'),(42,1,9,'INS'),(42,2,9,'INS'),(43,1,9,'INS'),(43,2,9,'INS'),(44,1,9,'INS'),(44,2,9,'INS'),(45,1,9,'INS'),(45,2,9,'INS'),(46,1,9,'INS'),(46,2,9,'INS'),(47,1,9,'INS'),(47,2,9,'INS'),(48,1,9,'INS'),(48,2,9,'INS'),(49,1,9,'INS'),(49,2,9,'INS'),(50,1,9,'INS'),(50,2,9,'INS'),(51,1,9,'INS'),(51,2,9,'INS'),(52,1,9,'INS'),(52,2,9,'INS'),(53,1,9,'INS'),(53,2,9,'INS'),(54,1,9,'INS'),(54,2,9,'INS'),(55,1,9,'INS'),(55,2,9,'INS'),(56,1,9,'INS'),(56,2,9,'INS'),(57,1,9,'INS'),(57,2,9,'INS'),(58,1,9,'INS'),(58,2,9,'INS'),(59,1,9,'INS'),(59,2,9,'INS'),(60,1,9,'INS'),(60,2,9,'INS'),(61,1,9,'INS'),(61,2,9,'INS'),(62,1,9,'INS'),(62,2,9,'INS'),(63,1,9,'INS'),(63,2,9,'INS'),(64,1,9,'INS'),(64,2,9,'INS'),(65,1,9,'INS'),(65,2,9,'INS'),(66,1,9,'INS'),(66,2,9,'INS'),(67,1,9,'INS'),(67,2,9,'INS'),(68,1,9,'INS'),(68,2,9,'INS'),(69,1,9,'INS'),(69,2,9,'INS'),(70,1,9,'INS'),(70,2,9,'INS'),(71,1,9,'INS'),(71,2,9,'INS'),(72,1,9,'INS'),(72,2,9,'INS'),(73,1,9,'INS'),(73,2,9,'INS'),(74,1,9,'INS'),(74,2,9,'INS'),(75,1,9,'INS'),(75,2,9,'INS'),(76,1,9,'INS'),(76,2,9,'INS'),(77,1,9,'INS'),(77,2,9,'INS'),(78,1,9,'INS'),(78,2,9,'INS'),(79,1,9,'INS'),(79,2,9,'INS'),(80,1,9,'INS'),(80,2,9,'INS'),(81,1,9,'INS'),(81,2,9,'INS'),(82,1,9,'INS'),(82,2,9,'INS'),(83,1,9,'INS'),(83,2,9,'INS'),(84,1,9,'INS'),(84,2,9,'INS'),(85,1,9,'INS'),(85,2,9,'INS'),(86,1,9,'INS'),(86,2,9,'INS'),(87,1,9,'INS'),(87,2,9,'INS'),(88,1,9,'INS'),(88,2,9,'INS'),(89,1,9,'INS'),(89,2,9,'INS'),(90,1,9,'INS'),(90,2,9,'INS'),(91,1,9,'INS'),(91,2,9,'INS'),(92,1,9,'INS'),(92,2,9,'INS'),(93,1,9,'INS'),(93,2,9,'INS'),(94,1,9,'INS'),(94,2,9,'INS'),(95,1,9,'INS'),(95,2,9,'INS'),(96,1,9,'INS'),(96,2,9,'INS'),(97,1,9,'INS'),(97,2,9,'INS'),(98,1,9,'INS'),(98,2,9,'INS'),(99,1,9,'INS'),(99,2,9,'INS'),(100,1,9,'INS'),(100,2,9,'INS'),(101,1,9,'INS'),(101,2,9,'INS'),(102,1,9,'INS'),(102,2,9,'INS'),(103,5,9,'INS'),(103,6,9,'INS'),(104,5,9,'INS'),(104,6,9,'INS'),(105,5,9,'INS'),(105,6,9,'INS'),(106,5,9,'INS'),(106,6,9,'INS'),(107,5,9,'INS'),(107,6,9,'INS'),(108,5,9,'INS'),(108,6,9,'INS'),(109,5,9,'INS'),(109,6,9,'INS'),(110,5,9,'INS'),(110,6,9,'INS'),(111,5,9,'INS'),(111,6,9,'INS'),(112,5,9,'INS'),(112,6,9,'INS'),(113,5,9,'INS'),(113,6,9,'INS'),(114,5,9,'INS'),(114,6,9,'INS'),(115,5,9,'INS'),(115,6,9,'INS'),(116,5,9,'INS'),(116,6,9,'INS'),(117,5,9,'INS'),(117,6,9,'INS'),(118,5,9,'INS'),(118,6,9,'INS'),(119,5,9,'INS'),(119,6,9,'INS'),(120,5,9,'INS'),(120,6,9,'INS'),(121,5,9,'INS'),(121,6,9,'INS'),(122,5,9,'INS'),(122,6,9,'INS'),(123,5,9,'INS'),(123,6,9,'INS'),(124,5,9,'INS'),(124,6,9,'INS'),(125,5,9,'INS'),(125,6,9,'INS'),(126,5,9,'INS'),(126,6,9,'INS'),(127,5,9,'INS'),(127,6,9,'INS'),(128,5,9,'INS'),(128,6,9,'INS'),(129,5,9,'INS'),(129,6,9,'INS'),(130,5,9,'INS'),(130,6,9,'INS'),(131,5,9,'INS'),(131,6,9,'INS'),(132,5,9,'INS'),(132,6,9,'INS'),(133,5,9,'INS'),(133,6,9,'INS'),(134,5,9,'INS'),(134,6,9,'INS'),(135,5,9,'INS'),(135,6,9,'INS'),(136,5,9,'INS'),(136,6,9,'INS'),(137,5,9,'INS'),(137,6,9,'INS'),(138,5,9,'INS'),(138,6,9,'INS'),(139,5,9,'INS'),(139,6,9,'INS'),(140,5,9,'INS'),(140,6,9,'INS'),(141,5,9,'INS'),(141,6,9,'INS'),(142,5,9,'INS'),(142,6,9,'INS'),(143,5,9,'INS'),(143,6,9,'INS'),(144,5,9,'INS'),(144,6,9,'INS'),(145,5,9,'INS'),(145,6,9,'INS'),(146,5,9,'INS'),(146,6,9,'INS'),(147,5,9,'INS'),(147,6,9,'INS'),(148,5,9,'INS'),(148,6,9,'INS'),(149,5,9,'INS'),(149,6,9,'INS'),(150,5,9,'INS'),(150,6,9,'INS'),(151,5,9,'INS'),(151,6,9,'INS'),(152,5,9,'INS'),(152,6,9,'INS'),(153,5,9,'INS'),(153,6,9,'INS'),(154,5,9,'INS'),(154,6,9,'INS'),(155,5,9,'INS'),(155,6,9,'INS'),(156,5,9,'INS'),(156,6,9,'INS'),(157,5,9,'INS'),(157,6,9,'INS'),(158,5,9,'INS'),(158,6,9,'INS'),(159,5,9,'INS'),(159,6,9,'INS'),(160,5,9,'INS'),(160,6,9,'INS'),(161,5,9,'INS'),(161,6,9,'INS'),(162,5,9,'INS'),(162,6,9,'INS'),(163,5,9,'INS'),(163,6,9,'INS'),(164,5,9,'INS'),(164,6,9,'INS'),(165,5,9,'INS'),(165,6,9,'INS'),(166,5,9,'INS'),(166,6,9,'INS'),(167,5,9,'INS'),(167,6,9,'INS'),(168,5,9,'INS'),(168,6,9,'INS'),(169,5,9,'INS'),(169,6,9,'INS'),(170,5,9,'INS'),(170,6,9,'INS'),(171,5,9,'INS'),(171,6,9,'INS'),(172,5,9,'INS'),(172,6,9,'INS'),(173,5,9,'INS'),(173,6,9,'INS'),(174,5,9,'INS'),(174,6,9,'INS'),(175,5,9,'INS'),(175,6,9,'INS'),(176,5,9,'INS'),(176,6,9,'INS'),(177,5,9,'INS'),(177,6,9,'INS'),(178,5,9,'INS'),(178,6,9,'INS'),(179,5,9,'INS'),(179,6,9,'INS'),(180,5,9,'INS'),(180,6,9,'INS'),(181,5,9,'INS'),(181,6,9,'INS'),(182,5,9,'INS'),(182,6,9,'INS'),(183,5,9,'INS'),(183,6,9,'INS'),(184,5,9,'INS'),(184,6,9,'INS'),(185,5,9,'INS'),(185,6,9,'INS'),(186,5,9,'INS'),(186,6,9,'INS'),(187,5,9,'INS'),(187,6,9,'INS'),(188,5,9,'INS'),(188,6,9,'INS'),(189,5,9,'INS'),(189,6,9,'INS'),(190,5,9,'INS'),(190,6,9,'INS'),(191,5,9,'INS'),(191,6,9,'INS'),(192,5,9,'INS'),(192,6,9,'INS'),(193,5,9,'INS'),(193,6,9,'INS'),(194,5,9,'INS'),(194,6,9,'INS'),(195,5,9,'INS'),(195,6,9,'INS'),(196,5,9,'INS'),(196,6,9,'INS'),(197,5,9,'INS'),(197,6,9,'INS'),(198,5,9,'INS'),(198,6,9,'INS'),(199,5,9,'INS'),(199,6,9,'INS'),(200,5,9,'INS'),(200,6,9,'INS'),(201,5,9,'INS'),(201,6,9,'INS'),(202,5,9,'INS'),(202,6,9,'INS'),(203,5,9,'INS'),(203,6,9,'INS'),(204,5,9,'INS'),(204,6,9,'INS');
/*!40000 ALTER TABLE `wlt_tracking_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_travel_expense`
--

DROP TABLE IF EXISTS `wlt_travel_expense`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_travel_expense` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `travel_route_id` int NOT NULL,
  `from_date_time` datetime NOT NULL,
  `to_date_time` datetime NOT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_unicode_ci,
  `other_expenses` int NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4524F57F41807E1D` (`teacher_id`),
  KEY `IDX_4524F57F5E9DD7A3` (`travel_route_id`),
  CONSTRAINT `FK_4524F57F41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_4524F57F5E9DD7A3` FOREIGN KEY (`travel_route_id`) REFERENCES `edu_travel_route` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_travel_expense`
--

LOCK TABLES `wlt_travel_expense` WRITE;
/*!40000 ALTER TABLE `wlt_travel_expense` DISABLE KEYS */;
INSERT INTO `wlt_travel_expense` VALUES (1,4,1,'2026-10-17 09:30:00','2026-10-17 12:00:00',NULL,0,'Visita dual'),(2,9,3,'2026-10-17 09:30:00','2026-10-17 12:00:00',NULL,0,'Visita dual');
/*!40000 ALTER TABLE `wlt_travel_expense` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_travel_expense_agreement`
--

DROP TABLE IF EXISTS `wlt_travel_expense_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_travel_expense_agreement` (
  `travel_expense_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`agreement_id`),
  KEY `IDX_D3D5958BAA203AA8` (`travel_expense_id`),
  KEY `IDX_D3D5958B24890B2B` (`agreement_id`),
  CONSTRAINT `FK_D3D5958B24890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wlt_agreement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D3D5958BAA203AA8` FOREIGN KEY (`travel_expense_id`) REFERENCES `wlt_travel_expense` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_travel_expense_agreement`
--

LOCK TABLES `wlt_travel_expense_agreement` WRITE;
/*!40000 ALTER TABLE `wlt_travel_expense_agreement` DISABLE KEYS */;
INSERT INTO `wlt_travel_expense_agreement` VALUES (1,1),(2,3);
/*!40000 ALTER TABLE `wlt_travel_expense_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_travel_expense_agreement_audit`
--

DROP TABLE IF EXISTS `wlt_travel_expense_agreement_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_travel_expense_agreement_audit` (
  `travel_expense_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`agreement_id`,`rev`),
  KEY `rev_539576fd942d298158b17757be101662_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_travel_expense_agreement_audit`
--

LOCK TABLES `wlt_travel_expense_agreement_audit` WRITE;
/*!40000 ALTER TABLE `wlt_travel_expense_agreement_audit` DISABLE KEYS */;
INSERT INTO `wlt_travel_expense_agreement_audit` VALUES (1,1,9,'INS'),(2,3,9,'INS');
/*!40000 ALTER TABLE `wlt_travel_expense_agreement_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_travel_expense_audit`
--

DROP TABLE IF EXISTS `wlt_travel_expense_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_travel_expense_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `travel_route_id` int DEFAULT NULL,
  `from_date_time` datetime DEFAULT NULL,
  `to_date_time` datetime DEFAULT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_unicode_ci,
  `other_expenses` int DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_a88868b0c6712e0abd1e296b1217a283_idx` (`rev`),
  CONSTRAINT `rev_a88868b0c6712e0abd1e296b1217a283_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_travel_expense_audit`
--

LOCK TABLES `wlt_travel_expense_audit` WRITE;
/*!40000 ALTER TABLE `wlt_travel_expense_audit` DISABLE KEYS */;
INSERT INTO `wlt_travel_expense_audit` VALUES (1,9,4,1,'2026-10-17 09:30:00','2026-10-17 12:00:00',NULL,0,'Visita dual','INS'),(2,9,9,3,'2026-10-17 09:30:00','2026-10-17 12:00:00',NULL,0,'Visita dual','INS');
/*!40000 ALTER TABLE `wlt_travel_expense_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_work_day`
--

DROP TABLE IF EXISTS `wlt_work_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_work_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `hours` int NOT NULL,
  `date` date NOT NULL,
  `notes` longtext COLLATE utf8mb4_spanish_ci,
  `locked` tinyint(1) NOT NULL,
  `absence` int NOT NULL,
  `start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `other_activities` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D96CA0CB24890B2BAA9E377A` (`agreement_id`,`date`),
  KEY `IDX_D96CA0CB24890B2B` (`agreement_id`),
  CONSTRAINT `FK_D96CA0CB24890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wlt_agreement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_work_day`
--

LOCK TABLES `wlt_work_day` WRITE;
/*!40000 ALTER TABLE `wlt_work_day` DISABLE KEYS */;
INSERT INTO `wlt_work_day` VALUES (1,1,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(2,1,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(3,1,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(4,1,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(5,1,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(6,1,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(7,1,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(8,1,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(9,1,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(10,1,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(11,1,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(12,1,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(13,1,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(14,1,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(15,1,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(16,1,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(17,1,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(18,1,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(19,1,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(20,1,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(21,1,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(22,1,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(23,1,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(24,1,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(25,1,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(26,1,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(27,1,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(28,1,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(29,1,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(30,1,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(31,1,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(32,1,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(33,1,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(34,1,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(35,1,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(36,1,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(37,1,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(38,1,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(39,1,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(40,1,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(41,1,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(42,1,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(43,1,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(44,1,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(45,1,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(46,1,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(47,1,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(48,1,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(49,1,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(50,1,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(51,1,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(52,2,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(53,2,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(54,2,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(55,2,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(56,2,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(57,2,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(58,2,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(59,2,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(60,2,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(61,2,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(62,2,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(63,2,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(64,2,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(65,2,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(66,2,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(67,2,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(68,2,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(69,2,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(70,2,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(71,2,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(72,2,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(73,2,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(74,2,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(75,2,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(76,2,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(77,2,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(78,2,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(79,2,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(80,2,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(81,2,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(82,2,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(83,2,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(84,2,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(85,2,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(86,2,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(87,2,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(88,2,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(89,2,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(90,2,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(91,2,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(92,2,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(93,2,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(94,2,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(95,2,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(96,2,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(97,2,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(98,2,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(99,2,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(100,2,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(101,2,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(102,2,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(103,3,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(104,3,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(105,3,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(106,3,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(107,3,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(108,3,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(109,3,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(110,3,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(111,3,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(112,3,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(113,3,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(114,3,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(115,3,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(116,3,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(117,3,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(118,3,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(119,3,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(120,3,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(121,3,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(122,3,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(123,3,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(124,3,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(125,3,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(126,3,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(127,3,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(128,3,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(129,3,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(130,3,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(131,3,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(132,3,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(133,3,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(134,3,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(135,3,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(136,3,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(137,3,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(138,3,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(139,3,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(140,3,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(141,3,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(142,3,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(143,3,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(144,3,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(145,3,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(146,3,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(147,3,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(148,3,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(149,3,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(150,3,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(151,3,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(152,3,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(153,3,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(154,4,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(155,4,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(156,4,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(157,4,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(158,4,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(159,4,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(160,4,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(161,4,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(162,4,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(163,4,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(164,4,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(165,4,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(166,4,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(167,4,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(168,4,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(169,4,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(170,4,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(171,4,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(172,4,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(173,4,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(174,4,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(175,4,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(176,4,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(177,4,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(178,4,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(179,4,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(180,4,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(181,4,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(182,4,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(183,4,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(184,4,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,NULL),(185,4,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(186,4,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(187,4,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(188,4,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(189,4,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(190,4,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(191,4,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(192,4,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(193,4,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(194,4,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(195,4,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(196,4,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(197,4,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(198,4,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(199,4,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(200,4,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(201,4,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(202,4,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(203,4,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,NULL),(204,4,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wlt_work_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_work_day_audit`
--

DROP TABLE IF EXISTS `wlt_work_day_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_work_day_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `agreement_id` int DEFAULT NULL,
  `hours` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_spanish_ci,
  `locked` tinyint(1) DEFAULT NULL,
  `absence` int DEFAULT NULL,
  `start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `other_activities` longtext COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_a182ece7d34180bb9b41759ce4dafebc_idx` (`rev`),
  CONSTRAINT `rev_a182ece7d34180bb9b41759ce4dafebc_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_work_day_audit`
--

LOCK TABLES `wlt_work_day_audit` WRITE;
/*!40000 ALTER TABLE `wlt_work_day_audit` DISABLE KEYS */;
INSERT INTO `wlt_work_day_audit` VALUES (1,9,1,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(2,9,1,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(3,9,1,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(4,9,1,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(5,9,1,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(6,9,1,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(7,9,1,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(8,9,1,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(9,9,1,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(10,9,1,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(11,9,1,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(12,9,1,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(13,9,1,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(14,9,1,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(15,9,1,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(16,9,1,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(17,9,1,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(18,9,1,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(19,9,1,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(20,9,1,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(21,9,1,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(22,9,1,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(23,9,1,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(24,9,1,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(25,9,1,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(26,9,1,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(27,9,1,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(28,9,1,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(29,9,1,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(30,9,1,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(31,9,1,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(32,9,1,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(33,9,1,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(34,9,1,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(35,9,1,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(36,9,1,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(37,9,1,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(38,9,1,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(39,9,1,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(40,9,1,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(41,9,1,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(42,9,1,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(43,9,1,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(44,9,1,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(45,9,1,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(46,9,1,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(47,9,1,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(48,9,1,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(49,9,1,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(50,9,1,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(51,9,1,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(52,9,2,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(53,9,2,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(54,9,2,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(55,9,2,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(56,9,2,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(57,9,2,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(58,9,2,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(59,9,2,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(60,9,2,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(61,9,2,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(62,9,2,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(63,9,2,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(64,9,2,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(65,9,2,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(66,9,2,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(67,9,2,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(68,9,2,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(69,9,2,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(70,9,2,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(71,9,2,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(72,9,2,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(73,9,2,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(74,9,2,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(75,9,2,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(76,9,2,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(77,9,2,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(78,9,2,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(79,9,2,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(80,9,2,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(81,9,2,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(82,9,2,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(83,9,2,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(84,9,2,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(85,9,2,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(86,9,2,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(87,9,2,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(88,9,2,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(89,9,2,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(90,9,2,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(91,9,2,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(92,9,2,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(93,9,2,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(94,9,2,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(95,9,2,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(96,9,2,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(97,9,2,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(98,9,2,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(99,9,2,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(100,9,2,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(101,9,2,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(102,9,2,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(103,9,3,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(104,9,3,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(105,9,3,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(106,9,3,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(107,9,3,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(108,9,3,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(109,9,3,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(110,9,3,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(111,9,3,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(112,9,3,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(113,9,3,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(114,9,3,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(115,9,3,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(116,9,3,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(117,9,3,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(118,9,3,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(119,9,3,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(120,9,3,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(121,9,3,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(122,9,3,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(123,9,3,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(124,9,3,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(125,9,3,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(126,9,3,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(127,9,3,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(128,9,3,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(129,9,3,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(130,9,3,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(131,9,3,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(132,9,3,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(133,9,3,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(134,9,3,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(135,9,3,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(136,9,3,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(137,9,3,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(138,9,3,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(139,9,3,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(140,9,3,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(141,9,3,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(142,9,3,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(143,9,3,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(144,9,3,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(145,9,3,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(146,9,3,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(147,9,3,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(148,9,3,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(149,9,3,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(150,9,3,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(151,9,3,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(152,9,3,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(153,9,3,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(154,9,4,6,'2026-09-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(155,9,4,6,'2026-09-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(156,9,4,6,'2026-09-17','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(157,9,4,6,'2026-09-18','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(158,9,4,6,'2026-09-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(159,9,4,6,'2026-09-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(160,9,4,6,'2026-09-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(161,9,4,6,'2026-09-24','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(162,9,4,6,'2026-09-25','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(163,9,4,6,'2026-09-28','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(164,9,4,6,'2026-09-29','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(165,9,4,6,'2026-09-30','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(166,9,4,6,'2026-10-01','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(167,9,4,6,'2026-10-02','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(168,9,4,6,'2026-10-05','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(169,9,4,6,'2026-10-06','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(170,9,4,6,'2026-10-07','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(171,9,4,6,'2026-10-08','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(172,9,4,6,'2026-10-09','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(173,9,4,6,'2026-10-12','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(174,9,4,6,'2026-10-13','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(175,9,4,6,'2026-10-14','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(176,9,4,6,'2026-10-15','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(177,9,4,6,'2026-10-16','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(178,9,4,6,'2026-10-19','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(179,9,4,6,'2026-10-20','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(180,9,4,6,'2026-10-21','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(181,9,4,6,'2026-10-22','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(182,9,4,6,'2026-10-23','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(183,9,4,6,'2026-10-26','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(184,9,4,6,'2026-10-27','Jornada de formación dual',0,0,NULL,NULL,NULL,NULL,'INS',NULL),(185,9,4,6,'2026-10-28',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(186,9,4,6,'2026-10-29',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(187,9,4,6,'2026-10-30',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(188,9,4,6,'2026-11-02',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(189,9,4,6,'2026-11-03',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(190,9,4,6,'2026-11-04',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(191,9,4,6,'2026-11-05',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(192,9,4,6,'2026-11-06',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(193,9,4,6,'2026-11-09',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(194,9,4,6,'2026-11-10',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(195,9,4,6,'2026-11-11',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(196,9,4,6,'2026-11-12',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(197,9,4,6,'2026-11-13',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(198,9,4,6,'2026-11-16',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(199,9,4,6,'2026-11-17',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(200,9,4,6,'2026-11-18',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(201,9,4,6,'2026-11-19',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(202,9,4,6,'2026-11-20',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(203,9,4,6,'2026-11-23',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL),(204,9,4,6,'2026-11-24',NULL,0,0,NULL,NULL,NULL,NULL,'INS',NULL);
/*!40000 ALTER TABLE `wlt_work_day_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_work_tutor_answered_survey`
--

DROP TABLE IF EXISTS `wlt_work_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_work_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `work_tutor_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B4644BE3166D1F9C` (`project_id`),
  KEY `IDX_B4644BE3C54F3401` (`academic_year_id`),
  KEY `IDX_B4644BE3F53AEEAD` (`work_tutor_id`),
  KEY `IDX_B4644BE3A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_B4644BE3166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `wlt_project` (`id`),
  CONSTRAINT `FK_B4644BE3A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_B4644BE3C54F3401` FOREIGN KEY (`academic_year_id`) REFERENCES `edu_academic_year` (`id`),
  CONSTRAINT `FK_B4644BE3F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_work_tutor_answered_survey`
--

LOCK TABLES `wlt_work_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `wlt_work_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_work_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wlt_work_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `wlt_work_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wlt_work_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `academic_year_id` int DEFAULT NULL,
  `work_tutor_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_583e3ede99c93193ba43436008b164e7_idx` (`rev`),
  CONSTRAINT `rev_583e3ede99c93193ba43436008b164e7_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wlt_work_tutor_answered_survey_audit`
--

LOCK TABLES `wlt_work_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wlt_work_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wlt_work_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workcenter`
--

DROP TABLE IF EXISTS `workcenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workcenter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `manager_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E2337C97979B1AD6` (`company_id`),
  KEY `IDX_E2337C97783E3463` (`manager_id`),
  CONSTRAINT `FK_E2337C97783E3463` FOREIGN KEY (`manager_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_E2337C97979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workcenter`
--

LOCK TABLES `workcenter` WRITE;
/*!40000 ALTER TABLE `workcenter` DISABLE KEYS */;
INSERT INTO `workcenter` VALUES (1,1,NULL,'Sede central','Avenida de la Industria, 2','Linares','23700','953111111',NULL,'b23000001.workcenter.main@example.com'),(2,1,NULL,'Delegación de Jaén','Avenida de la Industria, 2','Jaén','23700','953111111',NULL,'b23000001.workcenter.branch@example.com'),(3,2,NULL,'Oficina de Úbeda','Avenida de la Industria, 2','Úbeda','23400','953111111',NULL,'a23000002.workcenter.ubeda@example.com'),(4,3,NULL,'Taller de Baeza','Avenida de la Industria, 2','Baeza','23440','953111111',NULL,'b23000003.workcenter.baeza@example.com');
/*!40000 ALTER TABLE `workcenter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workcenter_audit`
--

DROP TABLE IF EXISTS `workcenter_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workcenter_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `company_id` int DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_54a4a65186f56b9de79ff6f4b726b582_idx` (`rev`),
  CONSTRAINT `rev_54a4a65186f56b9de79ff6f4b726b582_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workcenter_audit`
--

LOCK TABLES `workcenter_audit` WRITE;
/*!40000 ALTER TABLE `workcenter_audit` DISABLE KEYS */;
INSERT INTO `workcenter_audit` VALUES (1,6,1,NULL,'Sede central','Avenida de la Industria, 2','Linares','23700','953111111',NULL,'b23000001.workcenter.main@example.com','INS'),(2,6,1,NULL,'Delegación de Jaén','Avenida de la Industria, 2','Jaén','23700','953111111',NULL,'b23000001.workcenter.branch@example.com','INS'),(3,6,2,NULL,'Oficina de Úbeda','Avenida de la Industria, 2','Úbeda','23400','953111111',NULL,'a23000002.workcenter.ubeda@example.com','INS'),(4,6,3,NULL,'Taller de Baeza','Avenida de la Industria, 2','Baeza','23440','953111111',NULL,'b23000003.workcenter.baeza@example.com','INS');
/*!40000 ALTER TABLE `workcenter_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_activity`
--

DROP TABLE IF EXISTS `wpt_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4E3A8191BB70BC0E77153098` (`shift_id`,`code`),
  KEY `IDX_4E3A8191BB70BC0E` (`shift_id`),
  CONSTRAINT `FK_4E3A8191BB70BC0E` FOREIGN KEY (`shift_id`) REFERENCES `wpt_shift` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_activity`
--

LOCK TABLES `wpt_activity` WRITE;
/*!40000 ALTER TABLE `wpt_activity` DISABLE KEYS */;
INSERT INTO `wpt_activity` VALUES (1,1,'A1','Instalación y configuración de equipos'),(2,1,'A2','Desarrollo de aplicaciones'),(3,1,'A3','Documentación y pruebas'),(4,2,'A1','Instalación y configuración de equipos'),(5,2,'A2','Desarrollo de aplicaciones'),(6,2,'A3','Documentación y pruebas');
/*!40000 ALTER TABLE `wpt_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_activity_audit`
--

DROP TABLE IF EXISTS `wpt_activity_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_activity_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_spanish_ci,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_9c2622768c8baa59674e5941fb6223f9_idx` (`rev`),
  CONSTRAINT `rev_9c2622768c8baa59674e5941fb6223f9_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_activity_audit`
--

LOCK TABLES `wpt_activity_audit` WRITE;
/*!40000 ALTER TABLE `wpt_activity_audit` DISABLE KEYS */;
INSERT INTO `wpt_activity_audit` VALUES (1,8,1,'A1','Instalación y configuración de equipos','INS'),(2,8,1,'A2','Desarrollo de aplicaciones','INS'),(3,8,1,'A3','Documentación y pruebas','INS'),(4,8,2,'A1','Instalación y configuración de equipos','INS'),(5,8,2,'A2','Desarrollo de aplicaciones','INS'),(6,8,2,'A3','Documentación y pruebas','INS');
/*!40000 ALTER TABLE `wpt_activity_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_activity_criterion`
--

DROP TABLE IF EXISTS `wpt_activity_criterion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_activity_criterion` (
  `activity_id` int NOT NULL,
  `criterion_id` int NOT NULL,
  PRIMARY KEY (`activity_id`,`criterion_id`),
  KEY `IDX_6E60352681C06096` (`activity_id`),
  KEY `IDX_6E60352697766307` (`criterion_id`),
  CONSTRAINT `FK_6E60352681C06096` FOREIGN KEY (`activity_id`) REFERENCES `wpt_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6E60352697766307` FOREIGN KEY (`criterion_id`) REFERENCES `edu_criterion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_activity_criterion`
--

LOCK TABLES `wpt_activity_criterion` WRITE;
/*!40000 ALTER TABLE `wpt_activity_criterion` DISABLE KEYS */;
INSERT INTO `wpt_activity_criterion` VALUES (1,3),(2,3),(3,3),(4,6),(5,6),(6,6);
/*!40000 ALTER TABLE `wpt_activity_criterion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_activity_criterion_audit`
--

DROP TABLE IF EXISTS `wpt_activity_criterion_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_activity_criterion_audit` (
  `activity_id` int NOT NULL,
  `criterion_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`activity_id`,`criterion_id`,`rev`),
  KEY `rev_42b2c0fbadffd3ee42fe5760da2d16a7_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_activity_criterion_audit`
--

LOCK TABLES `wpt_activity_criterion_audit` WRITE;
/*!40000 ALTER TABLE `wpt_activity_criterion_audit` DISABLE KEYS */;
INSERT INTO `wpt_activity_criterion_audit` VALUES (1,3,8,'INS'),(2,3,8,'INS'),(3,3,8,'INS'),(4,6,8,'INS'),(5,6,8,'INS'),(6,6,8,'INS');
/*!40000 ALTER TABLE `wpt_activity_criterion_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_activity_tracking`
--

DROP TABLE IF EXISTS `wpt_activity_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_activity_tracking` (
  `tracked_work_day_id` int NOT NULL,
  `activity_id` int NOT NULL,
  `notes` longtext COLLATE utf8mb4_spanish_ci,
  `hours` int NOT NULL,
  PRIMARY KEY (`tracked_work_day_id`,`activity_id`),
  KEY `IDX_FD82654581C06096` (`activity_id`),
  KEY `IDX_FD826545BE757E4C` (`tracked_work_day_id`),
  CONSTRAINT `FK_FD82654581C06096` FOREIGN KEY (`activity_id`) REFERENCES `wpt_activity` (`id`),
  CONSTRAINT `FK_FD826545BE757E4C` FOREIGN KEY (`tracked_work_day_id`) REFERENCES `wpt_tracked_work_day` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_activity_tracking`
--

LOCK TABLES `wpt_activity_tracking` WRITE;
/*!40000 ALTER TABLE `wpt_activity_tracking` DISABLE KEYS */;
INSERT INTO `wpt_activity_tracking` VALUES (1,1,'Trabajo realizado',500),(2,1,'Trabajo realizado',500),(3,1,'Trabajo realizado',500),(4,1,'Trabajo realizado',500),(5,1,'Trabajo realizado',500),(6,1,'Trabajo realizado',500),(7,1,'Trabajo realizado',500),(8,1,'Trabajo realizado',500),(11,1,'Trabajo realizado',500),(12,1,'Trabajo realizado',500),(13,1,'Trabajo realizado',500),(14,1,'Trabajo realizado',500),(15,1,'Trabajo realizado',500),(16,1,'Trabajo realizado',500),(17,1,'Trabajo realizado',500),(18,1,'Trabajo realizado',500),(21,1,'Trabajo realizado',500),(22,1,'Trabajo realizado',500),(23,1,'Trabajo realizado',500),(24,1,'Trabajo realizado',500),(25,1,'Trabajo realizado',500),(26,1,'Trabajo realizado',500),(27,1,'Trabajo realizado',500),(28,1,'Trabajo realizado',500),(29,1,'Trabajo realizado',500),(30,1,'Trabajo realizado',500),(31,1,'Trabajo realizado',500),(32,1,'Trabajo realizado',500),(35,1,'Trabajo realizado',500),(36,1,'Trabajo realizado',500),(37,1,'Trabajo realizado',500),(38,1,'Trabajo realizado',500),(39,1,'Trabajo realizado',500),(40,1,'Trabajo realizado',500),(41,1,'Trabajo realizado',500),(42,1,'Trabajo realizado',500),(45,1,'Trabajo realizado',500),(46,1,'Trabajo realizado',500),(47,1,'Trabajo realizado',500),(48,1,'Trabajo realizado',500),(49,1,'Trabajo realizado',500),(50,1,'Trabajo realizado',500),(51,1,'Trabajo realizado',500),(52,1,'Trabajo realizado',500),(55,1,'Trabajo realizado',500),(56,1,'Trabajo realizado',500),(57,1,'Trabajo realizado',500),(58,1,'Trabajo realizado',500),(59,1,'Trabajo realizado',500),(60,1,'Trabajo realizado',500),(61,1,'Trabajo realizado',500),(62,1,'Trabajo realizado',500),(63,1,'Trabajo realizado',500),(64,1,'Trabajo realizado',500),(65,1,'Trabajo realizado',500),(66,1,'Trabajo realizado',500),(68,1,'Trabajo realizado',500),(69,1,'Trabajo realizado',500),(70,1,'Trabajo realizado',500),(71,1,'Trabajo realizado',500),(73,1,'Trabajo realizado',500),(74,1,'Trabajo realizado',500),(75,1,'Trabajo realizado',500),(76,1,'Trabajo realizado',500),(77,1,'Trabajo realizado',500),(78,1,'Trabajo realizado',500),(80,1,'Trabajo realizado',500),(81,1,'Trabajo realizado',500),(82,1,'Trabajo realizado',500),(83,1,'Trabajo realizado',500),(85,1,'Trabajo realizado',500),(86,1,'Trabajo realizado',500),(87,1,'Trabajo realizado',500),(88,1,'Trabajo realizado',500),(90,1,'Trabajo realizado',500),(91,1,'Trabajo realizado',500),(92,1,'Trabajo realizado',500),(93,1,'Trabajo realizado',500),(94,4,'Trabajo realizado',500),(95,4,'Trabajo realizado',500),(96,4,'Trabajo realizado',500),(97,4,'Trabajo realizado',500),(98,4,'Trabajo realizado',500),(99,4,'Trabajo realizado',500),(100,4,'Trabajo realizado',500),(101,4,'Trabajo realizado',500),(104,4,'Trabajo realizado',500),(105,4,'Trabajo realizado',500),(106,4,'Trabajo realizado',500),(107,4,'Trabajo realizado',500),(108,4,'Trabajo realizado',500),(109,4,'Trabajo realizado',500),(110,4,'Trabajo realizado',500),(111,4,'Trabajo realizado',500),(114,4,'Trabajo realizado',500),(115,4,'Trabajo realizado',500),(116,4,'Trabajo realizado',500),(117,4,'Trabajo realizado',500),(118,4,'Trabajo realizado',500),(119,4,'Trabajo realizado',500),(120,4,'Trabajo realizado',500),(121,4,'Trabajo realizado',500),(122,4,'Trabajo realizado',500),(123,4,'Trabajo realizado',500),(124,4,'Trabajo realizado',500),(125,4,'Trabajo realizado',500),(128,4,'Trabajo realizado',500),(129,4,'Trabajo realizado',500),(130,4,'Trabajo realizado',500),(131,4,'Trabajo realizado',500),(132,4,'Trabajo realizado',500),(133,4,'Trabajo realizado',500),(134,4,'Trabajo realizado',500),(135,4,'Trabajo realizado',500),(138,4,'Trabajo realizado',500),(139,4,'Trabajo realizado',500),(140,4,'Trabajo realizado',500),(141,4,'Trabajo realizado',500),(142,4,'Trabajo realizado',500),(143,4,'Trabajo realizado',500),(144,4,'Trabajo realizado',500),(145,4,'Trabajo realizado',500),(148,4,'Trabajo realizado',500),(149,4,'Trabajo realizado',500),(150,4,'Trabajo realizado',500),(151,4,'Trabajo realizado',500),(152,4,'Trabajo realizado',500),(153,4,'Trabajo realizado',500),(154,4,'Trabajo realizado',500),(155,4,'Trabajo realizado',500),(156,4,'Trabajo realizado',500),(157,4,'Trabajo realizado',500),(158,4,'Trabajo realizado',500),(159,4,'Trabajo realizado',500),(161,4,'Trabajo realizado',500),(162,4,'Trabajo realizado',500),(163,4,'Trabajo realizado',500),(164,4,'Trabajo realizado',500),(166,4,'Trabajo realizado',500),(167,4,'Trabajo realizado',500),(168,4,'Trabajo realizado',500),(169,4,'Trabajo realizado',500),(170,4,'Trabajo realizado',500),(171,4,'Trabajo realizado',500),(173,4,'Trabajo realizado',500),(174,4,'Trabajo realizado',500),(175,4,'Trabajo realizado',500),(176,4,'Trabajo realizado',500),(178,4,'Trabajo realizado',500),(179,4,'Trabajo realizado',500),(180,4,'Trabajo realizado',500),(181,4,'Trabajo realizado',500),(183,4,'Trabajo realizado',500),(184,4,'Trabajo realizado',500),(185,4,'Trabajo realizado',500),(186,4,'Trabajo realizado',500);
/*!40000 ALTER TABLE `wpt_activity_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_agreement`
--

DROP TABLE IF EXISTS `wpt_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_agreement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `workcenter_id` int NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sign_date` date DEFAULT NULL,
  `default_start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_22310F94BB70BC0E` (`shift_id`),
  KEY `IDX_22310F94A2473C4B` (`workcenter_id`),
  CONSTRAINT `FK_22310F94A2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`),
  CONSTRAINT `FK_22310F94BB70BC0E` FOREIGN KEY (`shift_id`) REFERENCES `wpt_shift` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_agreement`
--

LOCK TABLES `wpt_agreement` WRITE;
/*!40000 ALTER TABLE `wpt_agreement` DISABLE KEYS */;
INSERT INTO `wpt_agreement` VALUES (1,1,1,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'Convenio con Tecnologías del Sur',0),(2,1,3,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'Convenio con Consultoría del Norte',0),(3,2,1,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'Convenio con Tecnologías del Sur',0),(4,2,3,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'Convenio con Consultoría del Norte',0);
/*!40000 ALTER TABLE `wpt_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_agreement_activity`
--

DROP TABLE IF EXISTS `wpt_agreement_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_agreement_activity` (
  `agreement_enrollment_id` int NOT NULL,
  `activity_id` int NOT NULL,
  PRIMARY KEY (`agreement_enrollment_id`,`activity_id`),
  KEY `IDX_889D477F81C06096` (`activity_id`),
  KEY `IDX_889D477FA32BEB28` (`agreement_enrollment_id`),
  CONSTRAINT `FK_889D477F81C06096` FOREIGN KEY (`activity_id`) REFERENCES `wpt_activity` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_889D477FA32BEB28` FOREIGN KEY (`agreement_enrollment_id`) REFERENCES `wpt_agreement_enrollment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_agreement_activity`
--

LOCK TABLES `wpt_agreement_activity` WRITE;
/*!40000 ALTER TABLE `wpt_agreement_activity` DISABLE KEYS */;
INSERT INTO `wpt_agreement_activity` VALUES (1,1),(2,1),(3,1),(1,2),(2,2),(3,2),(1,3),(2,3),(3,3),(4,4),(5,4),(6,4),(4,5),(5,5),(6,5),(4,6),(5,6),(6,6);
/*!40000 ALTER TABLE `wpt_agreement_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_agreement_audit`
--

DROP TABLE IF EXISTS `wpt_agreement_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_agreement_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `workcenter_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sign_date` date DEFAULT NULL,
  `default_start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `default_end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_c44410e99c46f4c316201779be629ca6_idx` (`rev`),
  CONSTRAINT `rev_c44410e99c46f4c316201779be629ca6_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_agreement_audit`
--

LOCK TABLES `wpt_agreement_audit` WRITE;
/*!40000 ALTER TABLE `wpt_agreement_audit` DISABLE KEYS */;
INSERT INTO `wpt_agreement_audit` VALUES (1,8,1,1,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'INS','Convenio con Tecnologías del Sur',0),(2,8,1,3,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'INS','Convenio con Consultoría del Norte',0),(3,8,2,1,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'INS','Convenio con Tecnologías del Sur',0),(4,8,2,3,'2026-09-15','2026-11-24','2026-09-08',NULL,NULL,NULL,NULL,'INS','Convenio con Consultoría del Norte',0);
/*!40000 ALTER TABLE `wpt_agreement_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_agreement_enrollment`
--

DROP TABLE IF EXISTS `wpt_agreement_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_agreement_enrollment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `student_survey_id` int DEFAULT NULL,
  `work_tutor_id` int NOT NULL,
  `educational_tutor_id` int NOT NULL,
  `company_survey_id` int DEFAULT NULL,
  `additional_work_tutor_id` int DEFAULT NULL,
  `additional_educational_tutor_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A24B4F7824890B2BDAE14AC5` (`agreement_id`,`student_enrollment_id`),
  KEY `IDX_A24B4F7824890B2B` (`agreement_id`),
  KEY `IDX_A24B4F78DAE14AC5` (`student_enrollment_id`),
  KEY `IDX_A24B4F78D490911D` (`student_survey_id`),
  KEY `IDX_A24B4F78F53AEEAD` (`work_tutor_id`),
  KEY `IDX_A24B4F78E7F72E80` (`educational_tutor_id`),
  KEY `IDX_A24B4F7880E5DA6D` (`company_survey_id`),
  KEY `IDX_A24B4F78364A8C43` (`additional_work_tutor_id`),
  KEY `IDX_A24B4F786C7EFCBC` (`additional_educational_tutor_id`),
  CONSTRAINT `FK_A24B4F7824890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wpt_agreement` (`id`),
  CONSTRAINT `FK_A24B4F78364A8C43` FOREIGN KEY (`additional_work_tutor_id`) REFERENCES `person` (`id`),
  CONSTRAINT `FK_A24B4F786C7EFCBC` FOREIGN KEY (`additional_educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_A24B4F7880E5DA6D` FOREIGN KEY (`company_survey_id`) REFERENCES `answered_survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A24B4F78D490911D` FOREIGN KEY (`student_survey_id`) REFERENCES `answered_survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A24B4F78DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`),
  CONSTRAINT `FK_A24B4F78E7F72E80` FOREIGN KEY (`educational_tutor_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_A24B4F78F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_agreement_enrollment`
--

LOCK TABLES `wpt_agreement_enrollment` WRITE;
/*!40000 ALTER TABLE `wpt_agreement_enrollment` DISABLE KEYS */;
INSERT INTO `wpt_agreement_enrollment` VALUES (1,1,1,NULL,30,4,NULL,NULL,NULL),(2,1,2,NULL,30,4,NULL,NULL,NULL),(3,2,3,NULL,31,5,NULL,NULL,NULL),(4,3,9,NULL,32,9,NULL,NULL,NULL),(5,3,10,NULL,32,9,NULL,NULL,NULL),(6,4,11,NULL,33,10,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wpt_agreement_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact`
--

DROP TABLE IF EXISTS `wpt_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `workcenter_id` int NOT NULL,
  `date_time` datetime NOT NULL,
  `detail` longtext COLLATE utf8mb4_spanish_ci,
  `method_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BA9039F141807E1D` (`teacher_id`),
  KEY `IDX_BA9039F1A2473C4B` (`workcenter_id`),
  KEY `IDX_BA9039F119883967` (`method_id`),
  CONSTRAINT `FK_BE387B1C19883967` FOREIGN KEY (`method_id`) REFERENCES `edu_contact_method` (`id`),
  CONSTRAINT `FK_BE387B1C41807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_BE387B1CA2473C4B` FOREIGN KEY (`workcenter_id`) REFERENCES `workcenter` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact`
--

LOCK TABLES `wpt_contact` WRITE;
/*!40000 ALTER TABLE `wpt_contact` DISABLE KEYS */;
INSERT INTO `wpt_contact` VALUES (1,4,1,'2026-10-13 10:00:00','<p>Visita de seguimiento al <b>centro de trabajo</b>.</p>',1),(2,9,1,'2026-10-13 10:00:00','<p>Visita de seguimiento al <b>centro de trabajo</b>.</p>',3);
/*!40000 ALTER TABLE `wpt_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact_agreement`
--

DROP TABLE IF EXISTS `wpt_contact_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact_agreement` (
  `contact_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`agreement_id`),
  KEY `IDX_B560CE08E7A1254A` (`contact_id`),
  KEY `IDX_B560CE0824890B2B` (`agreement_id`),
  CONSTRAINT `FK_AFFBB6224890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wpt_agreement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B560CE08E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `wpt_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact_agreement`
--

LOCK TABLES `wpt_contact_agreement` WRITE;
/*!40000 ALTER TABLE `wpt_contact_agreement` DISABLE KEYS */;
INSERT INTO `wpt_contact_agreement` VALUES (1,1),(2,3);
/*!40000 ALTER TABLE `wpt_contact_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact_agreement_audit`
--

DROP TABLE IF EXISTS `wpt_contact_agreement_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact_agreement_audit` (
  `contact_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`agreement_id`,`rev`),
  KEY `rev_670f76fa408fe3e42c32679f18c3bbe7_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact_agreement_audit`
--

LOCK TABLES `wpt_contact_agreement_audit` WRITE;
/*!40000 ALTER TABLE `wpt_contact_agreement_audit` DISABLE KEYS */;
INSERT INTO `wpt_contact_agreement_audit` VALUES (1,1,8,'INS'),(2,3,8,'INS');
/*!40000 ALTER TABLE `wpt_contact_agreement_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact_audit`
--

DROP TABLE IF EXISTS `wpt_contact_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `workcenter_id` int DEFAULT NULL,
  `method_id` int DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `detail` longtext COLLATE utf8mb4_unicode_ci,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_14a73b1dc586567879897aac041a9700_idx` (`rev`),
  CONSTRAINT `rev_14a73b1dc586567879897aac041a9700_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact_audit`
--

LOCK TABLES `wpt_contact_audit` WRITE;
/*!40000 ALTER TABLE `wpt_contact_audit` DISABLE KEYS */;
INSERT INTO `wpt_contact_audit` VALUES (1,8,4,1,1,'2026-10-13 10:00:00','<p>Visita de seguimiento al <b>centro de trabajo</b>.</p>','INS'),(2,8,9,1,3,'2026-10-13 10:00:00','<p>Visita de seguimiento al <b>centro de trabajo</b>.</p>','INS');
/*!40000 ALTER TABLE `wpt_contact_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact_student_enrollment`
--

DROP TABLE IF EXISTS `wpt_contact_student_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact_student_enrollment` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`),
  KEY `IDX_DC95389BE7A1254A` (`contact_id`),
  KEY `IDX_DC95389BDAE14AC5` (`student_enrollment_id`),
  CONSTRAINT `FK_3740B20DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_DC95389BE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `wpt_contact` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact_student_enrollment`
--

LOCK TABLES `wpt_contact_student_enrollment` WRITE;
/*!40000 ALTER TABLE `wpt_contact_student_enrollment` DISABLE KEYS */;
INSERT INTO `wpt_contact_student_enrollment` VALUES (1,1),(1,2),(2,9),(2,10);
/*!40000 ALTER TABLE `wpt_contact_student_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_contact_student_enrollment_audit`
--

DROP TABLE IF EXISTS `wpt_contact_student_enrollment_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_contact_student_enrollment_audit` (
  `contact_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`contact_id`,`student_enrollment_id`,`rev`),
  KEY `rev_676fe97e25939c1e21812b83094a2efa_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_contact_student_enrollment_audit`
--

LOCK TABLES `wpt_contact_student_enrollment_audit` WRITE;
/*!40000 ALTER TABLE `wpt_contact_student_enrollment_audit` DISABLE KEYS */;
INSERT INTO `wpt_contact_student_enrollment_audit` VALUES (1,1,8,'INS'),(1,2,8,'INS'),(2,9,8,'INS'),(2,10,8,'INS');
/*!40000 ALTER TABLE `wpt_contact_student_enrollment_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_educational_tutor_answered_survey`
--

DROP TABLE IF EXISTS `wpt_educational_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_educational_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_62609090BB70BC0E` (`shift_id`),
  KEY `IDX_6260909041807E1D` (`teacher_id`),
  KEY `IDX_62609090A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_6260909041807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_62609090A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_62609090BB70BC0E` FOREIGN KEY (`shift_id`) REFERENCES `wpt_shift` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_educational_tutor_answered_survey`
--

LOCK TABLES `wpt_educational_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `wpt_educational_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_educational_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_educational_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `wpt_educational_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_educational_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_d3a1a7f9086d9bd8a93c67e81b8519eb_idx` (`rev`),
  CONSTRAINT `rev_d3a1a7f9086d9bd8a93c67e81b8519eb_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_educational_tutor_answered_survey_audit`
--

LOCK TABLES `wpt_educational_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wpt_educational_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_educational_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_report`
--

DROP TABLE IF EXISTS `wpt_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_report` (
  `agreement_enrollment_id` int NOT NULL,
  `work_activities` longtext COLLATE utf8mb4_spanish_ci NOT NULL,
  `professional_competence` int NOT NULL,
  `organizational_competence` int NOT NULL,
  `relational_competence` int NOT NULL,
  `contingency_response` int NOT NULL,
  `other_description1` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `other1` int DEFAULT NULL,
  `other_description2` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `other2` int DEFAULT NULL,
  `proposed_changes` longtext COLLATE utf8mb4_spanish_ci,
  `sign_date` date NOT NULL,
  PRIMARY KEY (`agreement_enrollment_id`),
  CONSTRAINT `FK_8FD6E551A32BEB28` FOREIGN KEY (`agreement_enrollment_id`) REFERENCES `wpt_agreement_enrollment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_report`
--

LOCK TABLES `wpt_report` WRITE;
/*!40000 ALTER TABLE `wpt_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_shift`
--

DROP TABLE IF EXISTS `wpt_shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_shift` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `final_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `hours` int NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `quarter` int NOT NULL,
  `activity_summary_report_template_id` int DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_584DA96023EDC87` (`subject_id`),
  KEY `IDX_584DA960D490911D` (`student_survey_id`),
  KEY `IDX_584DA96080E5DA6D` (`company_survey_id`),
  KEY `IDX_584DA96068F6798B` (`educational_tutor_survey_id`),
  KEY `IDX_584DA96013E6472B` (`attendance_report_template_id`),
  KEY `IDX_584DA96010BDE131` (`final_report_template_id`),
  KEY `IDX_584DA9603BBF8EEC` (`weekly_activity_report_template_id`),
  KEY `IDX_584DA9607791D10` (`activity_summary_report_template_id`),
  CONSTRAINT `FK_584DA96010BDE131` FOREIGN KEY (`final_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_584DA96013E6472B` FOREIGN KEY (`attendance_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_584DA96023EDC87` FOREIGN KEY (`subject_id`) REFERENCES `edu_subject` (`id`),
  CONSTRAINT `FK_584DA9603BBF8EEC` FOREIGN KEY (`weekly_activity_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_584DA96068F6798B` FOREIGN KEY (`educational_tutor_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_584DA9607791D10` FOREIGN KEY (`activity_summary_report_template_id`) REFERENCES `edu_report_template` (`id`),
  CONSTRAINT `FK_584DA96080E5DA6D` FOREIGN KEY (`company_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_584DA960D490911D` FOREIGN KEY (`student_survey_id`) REFERENCES `survey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_shift`
--

LOCK TABLES `wpt_shift` WRITE;
/*!40000 ALTER TABLE `wpt_shift` DISABLE KEYS */;
INSERT INTO `wpt_shift` VALUES (1,3,NULL,NULL,NULL,NULL,NULL,NULL,'Formación en centros de trabajo',41000,'FCT',3,NULL,0),(2,6,NULL,NULL,NULL,NULL,NULL,NULL,'Formación en centros de trabajo',41000,'FCT',3,NULL,0);
/*!40000 ALTER TABLE `wpt_shift` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_shift_audit`
--

DROP TABLE IF EXISTS `wpt_shift_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_shift_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `student_survey_id` int DEFAULT NULL,
  `company_survey_id` int DEFAULT NULL,
  `educational_tutor_survey_id` int DEFAULT NULL,
  `attendance_report_template_id` int DEFAULT NULL,
  `final_report_template_id` int DEFAULT NULL,
  `weekly_activity_report_template_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `hours` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `quarter` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  `activity_summary_report_template_id` int DEFAULT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_71f73a2d4dc70e8d55eaad661689e458_idx` (`rev`),
  CONSTRAINT `rev_71f73a2d4dc70e8d55eaad661689e458_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_shift_audit`
--

LOCK TABLES `wpt_shift_audit` WRITE;
/*!40000 ALTER TABLE `wpt_shift_audit` DISABLE KEYS */;
INSERT INTO `wpt_shift_audit` VALUES (1,8,3,NULL,NULL,NULL,NULL,NULL,NULL,'Formación en centros de trabajo',41000,'FCT',3,'INS',NULL,0),(2,8,6,NULL,NULL,NULL,NULL,NULL,NULL,'Formación en centros de trabajo',41000,'FCT',3,'INS',NULL,0);
/*!40000 ALTER TABLE `wpt_shift_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_student_answered_survey`
--

DROP TABLE IF EXISTS `wpt_student_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_student_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `student_enrollment_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3115CDA6BB70BC0E` (`shift_id`),
  KEY `IDX_3115CDA6DAE14AC5` (`student_enrollment_id`),
  KEY `IDX_3115CDA6A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_3115CDA6A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_3115CDA6BB70BC0E` FOREIGN KEY (`shift_id`) REFERENCES `wpt_shift` (`id`),
  CONSTRAINT `FK_3115CDA6DAE14AC5` FOREIGN KEY (`student_enrollment_id`) REFERENCES `edu_student_enrollment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_student_answered_survey`
--

LOCK TABLES `wpt_student_answered_survey` WRITE;
/*!40000 ALTER TABLE `wpt_student_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_student_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_student_answered_survey_audit`
--

DROP TABLE IF EXISTS `wpt_student_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_student_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `student_enrollment_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_18ec14100e728ec30e97808504c4bcae_idx` (`rev`),
  CONSTRAINT `rev_18ec14100e728ec30e97808504c4bcae_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_student_answered_survey_audit`
--

LOCK TABLES `wpt_student_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wpt_student_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_student_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_tracked_work_day`
--

DROP TABLE IF EXISTS `wpt_tracked_work_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_tracked_work_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `work_day_id` int NOT NULL,
  `agreement_enrollment_id` int NOT NULL,
  `notes` longtext COLLATE utf8mb4_spanish_ci,
  `other_activities` longtext COLLATE utf8mb4_spanish_ci,
  `locked` tinyint(1) NOT NULL,
  `absence` int NOT NULL,
  `start_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time1` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `start_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `end_time2` varchar(5) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E7E67EDAA32BEB28A23B8704` (`agreement_enrollment_id`,`work_day_id`),
  KEY `IDX_E7E67EDAA23B8704` (`work_day_id`),
  KEY `IDX_E7E67EDAA32BEB28` (`agreement_enrollment_id`),
  CONSTRAINT `FK_E7E67EDAA23B8704` FOREIGN KEY (`work_day_id`) REFERENCES `wpt_work_day` (`id`),
  CONSTRAINT `FK_E7E67EDAA32BEB28` FOREIGN KEY (`agreement_enrollment_id`) REFERENCES `wpt_agreement_enrollment` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_tracked_work_day`
--

LOCK TABLES `wpt_tracked_work_day` WRITE;
/*!40000 ALTER TABLE `wpt_tracked_work_day` DISABLE KEYS */;
INSERT INTO `wpt_tracked_work_day` VALUES (1,1,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(2,1,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(3,2,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(4,2,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(5,3,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(6,3,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(7,4,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(8,4,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(9,5,1,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(10,5,2,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(11,6,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(12,6,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(13,7,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(14,7,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(15,8,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(16,8,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(17,9,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(18,9,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(19,10,1,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(20,10,2,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(21,11,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(22,11,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(23,12,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(24,12,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(25,13,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(26,13,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(27,14,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(28,14,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(29,15,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(30,15,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(31,16,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(32,16,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(33,17,1,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(34,17,2,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(35,18,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(36,18,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(37,19,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(38,19,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(39,20,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(40,20,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(41,21,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(42,21,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(43,22,1,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(44,22,2,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(45,23,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(46,23,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(47,24,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(48,24,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(49,25,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(50,25,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(51,26,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(52,26,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(53,27,1,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(54,27,2,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(55,28,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(56,28,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(57,29,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(58,29,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(59,30,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(60,30,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(61,31,1,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(62,31,2,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(63,52,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(64,53,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(65,54,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(66,55,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(67,56,3,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(68,57,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(69,58,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(70,59,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(71,60,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(72,61,3,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(73,62,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(74,63,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(75,64,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(76,65,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(77,66,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(78,67,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(79,68,3,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(80,69,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(81,70,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(82,71,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(83,72,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(84,73,3,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(85,74,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(86,75,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(87,76,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(88,77,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(89,78,3,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(90,79,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(91,80,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(92,81,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(93,82,3,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(94,103,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(95,103,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(96,104,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(97,104,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(98,105,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(99,105,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(100,106,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(101,106,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(102,107,4,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(103,107,5,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(104,108,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(105,108,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(106,109,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(107,109,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(108,110,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(109,110,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(110,111,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(111,111,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(112,112,4,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(113,112,5,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(114,113,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(115,113,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(116,114,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(117,114,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(118,115,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(119,115,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(120,116,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(121,116,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(122,117,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(123,117,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(124,118,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(125,118,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(126,119,4,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(127,119,5,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(128,120,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(129,120,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(130,121,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(131,121,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(132,122,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(133,122,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(134,123,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(135,123,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(136,124,4,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(137,124,5,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(138,125,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(139,125,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(140,126,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(141,126,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(142,127,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(143,127,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(144,128,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(145,128,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(146,129,4,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(147,129,5,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(148,130,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(149,130,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(150,131,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(151,131,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(152,132,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(153,132,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(154,133,4,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(155,133,5,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(156,154,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(157,155,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(158,156,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(159,157,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(160,158,6,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(161,159,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(162,160,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(163,161,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(164,162,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(165,163,6,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(166,164,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(167,165,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(168,166,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(169,167,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(170,168,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(171,169,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(172,170,6,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(173,171,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(174,172,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(175,173,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(176,174,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(177,175,6,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(178,176,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(179,177,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(180,178,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(181,179,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(182,180,6,NULL,NULL,0,2,NULL,NULL,NULL,NULL),(183,181,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(184,182,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(185,183,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL),(186,184,6,'Jornada de prácticas','Reunión de equipo',0,0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `wpt_tracked_work_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_travel_expense`
--

DROP TABLE IF EXISTS `wpt_travel_expense`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_travel_expense` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `travel_route_id` int NOT NULL,
  `from_date_time` datetime NOT NULL,
  `to_date_time` datetime NOT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_spanish_ci,
  `other_expenses` int NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C76D045341807E1D` (`teacher_id`),
  KEY `IDX_C76D04535E9DD7A3` (`travel_route_id`),
  CONSTRAINT `FK_C76D045341807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `edu_teacher` (`id`),
  CONSTRAINT `FK_C76D04535E9DD7A3` FOREIGN KEY (`travel_route_id`) REFERENCES `edu_travel_route` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_travel_expense`
--

LOCK TABLES `wpt_travel_expense` WRITE;
/*!40000 ALTER TABLE `wpt_travel_expense` DISABLE KEYS */;
INSERT INTO `wpt_travel_expense` VALUES (1,4,1,'2026-10-13 09:30:00','2026-10-13 12:00:00',NULL,0,'Visita de seguimiento'),(2,9,3,'2026-10-13 09:30:00','2026-10-13 12:00:00',NULL,0,'Visita de seguimiento');
/*!40000 ALTER TABLE `wpt_travel_expense` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_travel_expense_agreement`
--

DROP TABLE IF EXISTS `wpt_travel_expense_agreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_travel_expense_agreement` (
  `travel_expense_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`agreement_id`),
  KEY `IDX_2E628311AA203AA8` (`travel_expense_id`),
  KEY `IDX_2E62831124890B2B` (`agreement_id`),
  CONSTRAINT `FK_2E62831124890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wpt_agreement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2E628311AA203AA8` FOREIGN KEY (`travel_expense_id`) REFERENCES `wpt_travel_expense` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_travel_expense_agreement`
--

LOCK TABLES `wpt_travel_expense_agreement` WRITE;
/*!40000 ALTER TABLE `wpt_travel_expense_agreement` DISABLE KEYS */;
INSERT INTO `wpt_travel_expense_agreement` VALUES (1,1),(2,3);
/*!40000 ALTER TABLE `wpt_travel_expense_agreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_travel_expense_agreement_audit`
--

DROP TABLE IF EXISTS `wpt_travel_expense_agreement_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_travel_expense_agreement_audit` (
  `travel_expense_id` int NOT NULL,
  `agreement_id` int NOT NULL,
  `rev` int NOT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`travel_expense_id`,`agreement_id`,`rev`),
  KEY `rev_bcc2b7829a2e57f8c109e7da204f5623_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_travel_expense_agreement_audit`
--

LOCK TABLES `wpt_travel_expense_agreement_audit` WRITE;
/*!40000 ALTER TABLE `wpt_travel_expense_agreement_audit` DISABLE KEYS */;
INSERT INTO `wpt_travel_expense_agreement_audit` VALUES (1,1,8,'INS'),(2,3,8,'INS');
/*!40000 ALTER TABLE `wpt_travel_expense_agreement_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_travel_expense_audit`
--

DROP TABLE IF EXISTS `wpt_travel_expense_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_travel_expense_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `travel_route_id` int DEFAULT NULL,
  `from_date_time` datetime DEFAULT NULL,
  `to_date_time` datetime DEFAULT NULL,
  `other_expenses_description` longtext COLLATE utf8mb4_unicode_ci,
  `other_expenses` int DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_27f57b88f7b19e7f0da66637d23c7c37_idx` (`rev`),
  CONSTRAINT `rev_27f57b88f7b19e7f0da66637d23c7c37_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_travel_expense_audit`
--

LOCK TABLES `wpt_travel_expense_audit` WRITE;
/*!40000 ALTER TABLE `wpt_travel_expense_audit` DISABLE KEYS */;
INSERT INTO `wpt_travel_expense_audit` VALUES (1,8,4,1,'2026-10-13 09:30:00','2026-10-13 12:00:00',NULL,0,'Visita de seguimiento','INS'),(2,8,9,3,'2026-10-13 09:30:00','2026-10-13 12:00:00',NULL,0,'Visita de seguimiento','INS');
/*!40000 ALTER TABLE `wpt_travel_expense_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_work_day`
--

DROP TABLE IF EXISTS `wpt_work_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_work_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agreement_id` int NOT NULL,
  `hours` int NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7D80F6C724890B2BAA9E377A` (`agreement_id`,`date`),
  KEY `IDX_7D80F6C724890B2B` (`agreement_id`),
  CONSTRAINT `FK_7D80F6C724890B2B` FOREIGN KEY (`agreement_id`) REFERENCES `wpt_agreement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_work_day`
--

LOCK TABLES `wpt_work_day` WRITE;
/*!40000 ALTER TABLE `wpt_work_day` DISABLE KEYS */;
INSERT INTO `wpt_work_day` VALUES (1,1,500,'2026-09-15'),(2,1,500,'2026-09-16'),(3,1,500,'2026-09-17'),(4,1,500,'2026-09-18'),(5,1,500,'2026-09-21'),(6,1,500,'2026-09-22'),(7,1,500,'2026-09-23'),(8,1,500,'2026-09-24'),(9,1,500,'2026-09-25'),(10,1,500,'2026-09-28'),(11,1,500,'2026-09-29'),(12,1,500,'2026-09-30'),(13,1,500,'2026-10-01'),(14,1,500,'2026-10-02'),(15,1,500,'2026-10-05'),(16,1,500,'2026-10-06'),(17,1,500,'2026-10-07'),(18,1,500,'2026-10-08'),(19,1,500,'2026-10-09'),(20,1,500,'2026-10-12'),(21,1,500,'2026-10-13'),(22,1,500,'2026-10-14'),(23,1,500,'2026-10-15'),(24,1,500,'2026-10-16'),(25,1,500,'2026-10-19'),(26,1,500,'2026-10-20'),(27,1,500,'2026-10-21'),(28,1,500,'2026-10-22'),(29,1,500,'2026-10-23'),(30,1,500,'2026-10-26'),(31,1,500,'2026-10-27'),(32,1,500,'2026-10-28'),(33,1,500,'2026-10-29'),(34,1,500,'2026-10-30'),(35,1,500,'2026-11-02'),(36,1,500,'2026-11-03'),(37,1,500,'2026-11-04'),(38,1,500,'2026-11-05'),(39,1,500,'2026-11-06'),(40,1,500,'2026-11-09'),(41,1,500,'2026-11-10'),(42,1,500,'2026-11-11'),(43,1,500,'2026-11-12'),(44,1,500,'2026-11-13'),(45,1,500,'2026-11-16'),(46,1,500,'2026-11-17'),(47,1,500,'2026-11-18'),(48,1,500,'2026-11-19'),(49,1,500,'2026-11-20'),(50,1,500,'2026-11-23'),(51,1,500,'2026-11-24'),(52,2,500,'2026-09-15'),(53,2,500,'2026-09-16'),(54,2,500,'2026-09-17'),(55,2,500,'2026-09-18'),(56,2,500,'2026-09-21'),(57,2,500,'2026-09-22'),(58,2,500,'2026-09-23'),(59,2,500,'2026-09-24'),(60,2,500,'2026-09-25'),(61,2,500,'2026-09-28'),(62,2,500,'2026-09-29'),(63,2,500,'2026-09-30'),(64,2,500,'2026-10-01'),(65,2,500,'2026-10-02'),(66,2,500,'2026-10-05'),(67,2,500,'2026-10-06'),(68,2,500,'2026-10-07'),(69,2,500,'2026-10-08'),(70,2,500,'2026-10-09'),(71,2,500,'2026-10-12'),(72,2,500,'2026-10-13'),(73,2,500,'2026-10-14'),(74,2,500,'2026-10-15'),(75,2,500,'2026-10-16'),(76,2,500,'2026-10-19'),(77,2,500,'2026-10-20'),(78,2,500,'2026-10-21'),(79,2,500,'2026-10-22'),(80,2,500,'2026-10-23'),(81,2,500,'2026-10-26'),(82,2,500,'2026-10-27'),(83,2,500,'2026-10-28'),(84,2,500,'2026-10-29'),(85,2,500,'2026-10-30'),(86,2,500,'2026-11-02'),(87,2,500,'2026-11-03'),(88,2,500,'2026-11-04'),(89,2,500,'2026-11-05'),(90,2,500,'2026-11-06'),(91,2,500,'2026-11-09'),(92,2,500,'2026-11-10'),(93,2,500,'2026-11-11'),(94,2,500,'2026-11-12'),(95,2,500,'2026-11-13'),(96,2,500,'2026-11-16'),(97,2,500,'2026-11-17'),(98,2,500,'2026-11-18'),(99,2,500,'2026-11-19'),(100,2,500,'2026-11-20'),(101,2,500,'2026-11-23'),(102,2,500,'2026-11-24'),(103,3,500,'2026-09-15'),(104,3,500,'2026-09-16'),(105,3,500,'2026-09-17'),(106,3,500,'2026-09-18'),(107,3,500,'2026-09-21'),(108,3,500,'2026-09-22'),(109,3,500,'2026-09-23'),(110,3,500,'2026-09-24'),(111,3,500,'2026-09-25'),(112,3,500,'2026-09-28'),(113,3,500,'2026-09-29'),(114,3,500,'2026-09-30'),(115,3,500,'2026-10-01'),(116,3,500,'2026-10-02'),(117,3,500,'2026-10-05'),(118,3,500,'2026-10-06'),(119,3,500,'2026-10-07'),(120,3,500,'2026-10-08'),(121,3,500,'2026-10-09'),(122,3,500,'2026-10-12'),(123,3,500,'2026-10-13'),(124,3,500,'2026-10-14'),(125,3,500,'2026-10-15'),(126,3,500,'2026-10-16'),(127,3,500,'2026-10-19'),(128,3,500,'2026-10-20'),(129,3,500,'2026-10-21'),(130,3,500,'2026-10-22'),(131,3,500,'2026-10-23'),(132,3,500,'2026-10-26'),(133,3,500,'2026-10-27'),(134,3,500,'2026-10-28'),(135,3,500,'2026-10-29'),(136,3,500,'2026-10-30'),(137,3,500,'2026-11-02'),(138,3,500,'2026-11-03'),(139,3,500,'2026-11-04'),(140,3,500,'2026-11-05'),(141,3,500,'2026-11-06'),(142,3,500,'2026-11-09'),(143,3,500,'2026-11-10'),(144,3,500,'2026-11-11'),(145,3,500,'2026-11-12'),(146,3,500,'2026-11-13'),(147,3,500,'2026-11-16'),(148,3,500,'2026-11-17'),(149,3,500,'2026-11-18'),(150,3,500,'2026-11-19'),(151,3,500,'2026-11-20'),(152,3,500,'2026-11-23'),(153,3,500,'2026-11-24'),(154,4,500,'2026-09-15'),(155,4,500,'2026-09-16'),(156,4,500,'2026-09-17'),(157,4,500,'2026-09-18'),(158,4,500,'2026-09-21'),(159,4,500,'2026-09-22'),(160,4,500,'2026-09-23'),(161,4,500,'2026-09-24'),(162,4,500,'2026-09-25'),(163,4,500,'2026-09-28'),(164,4,500,'2026-09-29'),(165,4,500,'2026-09-30'),(166,4,500,'2026-10-01'),(167,4,500,'2026-10-02'),(168,4,500,'2026-10-05'),(169,4,500,'2026-10-06'),(170,4,500,'2026-10-07'),(171,4,500,'2026-10-08'),(172,4,500,'2026-10-09'),(173,4,500,'2026-10-12'),(174,4,500,'2026-10-13'),(175,4,500,'2026-10-14'),(176,4,500,'2026-10-15'),(177,4,500,'2026-10-16'),(178,4,500,'2026-10-19'),(179,4,500,'2026-10-20'),(180,4,500,'2026-10-21'),(181,4,500,'2026-10-22'),(182,4,500,'2026-10-23'),(183,4,500,'2026-10-26'),(184,4,500,'2026-10-27'),(185,4,500,'2026-10-28'),(186,4,500,'2026-10-29'),(187,4,500,'2026-10-30'),(188,4,500,'2026-11-02'),(189,4,500,'2026-11-03'),(190,4,500,'2026-11-04'),(191,4,500,'2026-11-05'),(192,4,500,'2026-11-06'),(193,4,500,'2026-11-09'),(194,4,500,'2026-11-10'),(195,4,500,'2026-11-11'),(196,4,500,'2026-11-12'),(197,4,500,'2026-11-13'),(198,4,500,'2026-11-16'),(199,4,500,'2026-11-17'),(200,4,500,'2026-11-18'),(201,4,500,'2026-11-19'),(202,4,500,'2026-11-20'),(203,4,500,'2026-11-23'),(204,4,500,'2026-11-24');
/*!40000 ALTER TABLE `wpt_work_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_work_day_audit`
--

DROP TABLE IF EXISTS `wpt_work_day_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_work_day_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `agreement_id` int DEFAULT NULL,
  `hours` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_5078b049add7547b5901b17a05bf1608_idx` (`rev`),
  CONSTRAINT `rev_5078b049add7547b5901b17a05bf1608_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_work_day_audit`
--

LOCK TABLES `wpt_work_day_audit` WRITE;
/*!40000 ALTER TABLE `wpt_work_day_audit` DISABLE KEYS */;
INSERT INTO `wpt_work_day_audit` VALUES (1,8,1,500,'2026-09-15','INS'),(2,8,1,500,'2026-09-16','INS'),(3,8,1,500,'2026-09-17','INS'),(4,8,1,500,'2026-09-18','INS'),(5,8,1,500,'2026-09-21','INS'),(6,8,1,500,'2026-09-22','INS'),(7,8,1,500,'2026-09-23','INS'),(8,8,1,500,'2026-09-24','INS'),(9,8,1,500,'2026-09-25','INS'),(10,8,1,500,'2026-09-28','INS'),(11,8,1,500,'2026-09-29','INS'),(12,8,1,500,'2026-09-30','INS'),(13,8,1,500,'2026-10-01','INS'),(14,8,1,500,'2026-10-02','INS'),(15,8,1,500,'2026-10-05','INS'),(16,8,1,500,'2026-10-06','INS'),(17,8,1,500,'2026-10-07','INS'),(18,8,1,500,'2026-10-08','INS'),(19,8,1,500,'2026-10-09','INS'),(20,8,1,500,'2026-10-12','INS'),(21,8,1,500,'2026-10-13','INS'),(22,8,1,500,'2026-10-14','INS'),(23,8,1,500,'2026-10-15','INS'),(24,8,1,500,'2026-10-16','INS'),(25,8,1,500,'2026-10-19','INS'),(26,8,1,500,'2026-10-20','INS'),(27,8,1,500,'2026-10-21','INS'),(28,8,1,500,'2026-10-22','INS'),(29,8,1,500,'2026-10-23','INS'),(30,8,1,500,'2026-10-26','INS'),(31,8,1,500,'2026-10-27','INS'),(32,8,1,500,'2026-10-28','INS'),(33,8,1,500,'2026-10-29','INS'),(34,8,1,500,'2026-10-30','INS'),(35,8,1,500,'2026-11-02','INS'),(36,8,1,500,'2026-11-03','INS'),(37,8,1,500,'2026-11-04','INS'),(38,8,1,500,'2026-11-05','INS'),(39,8,1,500,'2026-11-06','INS'),(40,8,1,500,'2026-11-09','INS'),(41,8,1,500,'2026-11-10','INS'),(42,8,1,500,'2026-11-11','INS'),(43,8,1,500,'2026-11-12','INS'),(44,8,1,500,'2026-11-13','INS'),(45,8,1,500,'2026-11-16','INS'),(46,8,1,500,'2026-11-17','INS'),(47,8,1,500,'2026-11-18','INS'),(48,8,1,500,'2026-11-19','INS'),(49,8,1,500,'2026-11-20','INS'),(50,8,1,500,'2026-11-23','INS'),(51,8,1,500,'2026-11-24','INS'),(52,8,2,500,'2026-09-15','INS'),(53,8,2,500,'2026-09-16','INS'),(54,8,2,500,'2026-09-17','INS'),(55,8,2,500,'2026-09-18','INS'),(56,8,2,500,'2026-09-21','INS'),(57,8,2,500,'2026-09-22','INS'),(58,8,2,500,'2026-09-23','INS'),(59,8,2,500,'2026-09-24','INS'),(60,8,2,500,'2026-09-25','INS'),(61,8,2,500,'2026-09-28','INS'),(62,8,2,500,'2026-09-29','INS'),(63,8,2,500,'2026-09-30','INS'),(64,8,2,500,'2026-10-01','INS'),(65,8,2,500,'2026-10-02','INS'),(66,8,2,500,'2026-10-05','INS'),(67,8,2,500,'2026-10-06','INS'),(68,8,2,500,'2026-10-07','INS'),(69,8,2,500,'2026-10-08','INS'),(70,8,2,500,'2026-10-09','INS'),(71,8,2,500,'2026-10-12','INS'),(72,8,2,500,'2026-10-13','INS'),(73,8,2,500,'2026-10-14','INS'),(74,8,2,500,'2026-10-15','INS'),(75,8,2,500,'2026-10-16','INS'),(76,8,2,500,'2026-10-19','INS'),(77,8,2,500,'2026-10-20','INS'),(78,8,2,500,'2026-10-21','INS'),(79,8,2,500,'2026-10-22','INS'),(80,8,2,500,'2026-10-23','INS'),(81,8,2,500,'2026-10-26','INS'),(82,8,2,500,'2026-10-27','INS'),(83,8,2,500,'2026-10-28','INS'),(84,8,2,500,'2026-10-29','INS'),(85,8,2,500,'2026-10-30','INS'),(86,8,2,500,'2026-11-02','INS'),(87,8,2,500,'2026-11-03','INS'),(88,8,2,500,'2026-11-04','INS'),(89,8,2,500,'2026-11-05','INS'),(90,8,2,500,'2026-11-06','INS'),(91,8,2,500,'2026-11-09','INS'),(92,8,2,500,'2026-11-10','INS'),(93,8,2,500,'2026-11-11','INS'),(94,8,2,500,'2026-11-12','INS'),(95,8,2,500,'2026-11-13','INS'),(96,8,2,500,'2026-11-16','INS'),(97,8,2,500,'2026-11-17','INS'),(98,8,2,500,'2026-11-18','INS'),(99,8,2,500,'2026-11-19','INS'),(100,8,2,500,'2026-11-20','INS'),(101,8,2,500,'2026-11-23','INS'),(102,8,2,500,'2026-11-24','INS'),(103,8,3,500,'2026-09-15','INS'),(104,8,3,500,'2026-09-16','INS'),(105,8,3,500,'2026-09-17','INS'),(106,8,3,500,'2026-09-18','INS'),(107,8,3,500,'2026-09-21','INS'),(108,8,3,500,'2026-09-22','INS'),(109,8,3,500,'2026-09-23','INS'),(110,8,3,500,'2026-09-24','INS'),(111,8,3,500,'2026-09-25','INS'),(112,8,3,500,'2026-09-28','INS'),(113,8,3,500,'2026-09-29','INS'),(114,8,3,500,'2026-09-30','INS'),(115,8,3,500,'2026-10-01','INS'),(116,8,3,500,'2026-10-02','INS'),(117,8,3,500,'2026-10-05','INS'),(118,8,3,500,'2026-10-06','INS'),(119,8,3,500,'2026-10-07','INS'),(120,8,3,500,'2026-10-08','INS'),(121,8,3,500,'2026-10-09','INS'),(122,8,3,500,'2026-10-12','INS'),(123,8,3,500,'2026-10-13','INS'),(124,8,3,500,'2026-10-14','INS'),(125,8,3,500,'2026-10-15','INS'),(126,8,3,500,'2026-10-16','INS'),(127,8,3,500,'2026-10-19','INS'),(128,8,3,500,'2026-10-20','INS'),(129,8,3,500,'2026-10-21','INS'),(130,8,3,500,'2026-10-22','INS'),(131,8,3,500,'2026-10-23','INS'),(132,8,3,500,'2026-10-26','INS'),(133,8,3,500,'2026-10-27','INS'),(134,8,3,500,'2026-10-28','INS'),(135,8,3,500,'2026-10-29','INS'),(136,8,3,500,'2026-10-30','INS'),(137,8,3,500,'2026-11-02','INS'),(138,8,3,500,'2026-11-03','INS'),(139,8,3,500,'2026-11-04','INS'),(140,8,3,500,'2026-11-05','INS'),(141,8,3,500,'2026-11-06','INS'),(142,8,3,500,'2026-11-09','INS'),(143,8,3,500,'2026-11-10','INS'),(144,8,3,500,'2026-11-11','INS'),(145,8,3,500,'2026-11-12','INS'),(146,8,3,500,'2026-11-13','INS'),(147,8,3,500,'2026-11-16','INS'),(148,8,3,500,'2026-11-17','INS'),(149,8,3,500,'2026-11-18','INS'),(150,8,3,500,'2026-11-19','INS'),(151,8,3,500,'2026-11-20','INS'),(152,8,3,500,'2026-11-23','INS'),(153,8,3,500,'2026-11-24','INS'),(154,8,4,500,'2026-09-15','INS'),(155,8,4,500,'2026-09-16','INS'),(156,8,4,500,'2026-09-17','INS'),(157,8,4,500,'2026-09-18','INS'),(158,8,4,500,'2026-09-21','INS'),(159,8,4,500,'2026-09-22','INS'),(160,8,4,500,'2026-09-23','INS'),(161,8,4,500,'2026-09-24','INS'),(162,8,4,500,'2026-09-25','INS'),(163,8,4,500,'2026-09-28','INS'),(164,8,4,500,'2026-09-29','INS'),(165,8,4,500,'2026-09-30','INS'),(166,8,4,500,'2026-10-01','INS'),(167,8,4,500,'2026-10-02','INS'),(168,8,4,500,'2026-10-05','INS'),(169,8,4,500,'2026-10-06','INS'),(170,8,4,500,'2026-10-07','INS'),(171,8,4,500,'2026-10-08','INS'),(172,8,4,500,'2026-10-09','INS'),(173,8,4,500,'2026-10-12','INS'),(174,8,4,500,'2026-10-13','INS'),(175,8,4,500,'2026-10-14','INS'),(176,8,4,500,'2026-10-15','INS'),(177,8,4,500,'2026-10-16','INS'),(178,8,4,500,'2026-10-19','INS'),(179,8,4,500,'2026-10-20','INS'),(180,8,4,500,'2026-10-21','INS'),(181,8,4,500,'2026-10-22','INS'),(182,8,4,500,'2026-10-23','INS'),(183,8,4,500,'2026-10-26','INS'),(184,8,4,500,'2026-10-27','INS'),(185,8,4,500,'2026-10-28','INS'),(186,8,4,500,'2026-10-29','INS'),(187,8,4,500,'2026-10-30','INS'),(188,8,4,500,'2026-11-02','INS'),(189,8,4,500,'2026-11-03','INS'),(190,8,4,500,'2026-11-04','INS'),(191,8,4,500,'2026-11-05','INS'),(192,8,4,500,'2026-11-06','INS'),(193,8,4,500,'2026-11-09','INS'),(194,8,4,500,'2026-11-10','INS'),(195,8,4,500,'2026-11-11','INS'),(196,8,4,500,'2026-11-12','INS'),(197,8,4,500,'2026-11-13','INS'),(198,8,4,500,'2026-11-16','INS'),(199,8,4,500,'2026-11-17','INS'),(200,8,4,500,'2026-11-18','INS'),(201,8,4,500,'2026-11-19','INS'),(202,8,4,500,'2026-11-20','INS'),(203,8,4,500,'2026-11-23','INS'),(204,8,4,500,'2026-11-24','INS');
/*!40000 ALTER TABLE `wpt_work_day_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_work_tutor_answered_survey`
--

DROP TABLE IF EXISTS `wpt_work_tutor_answered_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_work_tutor_answered_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `work_tutor_id` int NOT NULL,
  `answered_survey_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CB1E6195BB70BC0E` (`shift_id`),
  KEY `IDX_CB1E6195F53AEEAD` (`work_tutor_id`),
  KEY `IDX_CB1E6195A97283E6` (`answered_survey_id`),
  CONSTRAINT `FK_CB1E6195A97283E6` FOREIGN KEY (`answered_survey_id`) REFERENCES `answered_survey` (`id`),
  CONSTRAINT `FK_CB1E6195BB70BC0E` FOREIGN KEY (`shift_id`) REFERENCES `wpt_shift` (`id`),
  CONSTRAINT `FK_CB1E6195F53AEEAD` FOREIGN KEY (`work_tutor_id`) REFERENCES `person` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_work_tutor_answered_survey`
--

LOCK TABLES `wpt_work_tutor_answered_survey` WRITE;
/*!40000 ALTER TABLE `wpt_work_tutor_answered_survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_work_tutor_answered_survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wpt_work_tutor_answered_survey_audit`
--

DROP TABLE IF EXISTS `wpt_work_tutor_answered_survey_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wpt_work_tutor_answered_survey_audit` (
  `id` int NOT NULL,
  `rev` int NOT NULL,
  `shift_id` int DEFAULT NULL,
  `work_tutor_id` int DEFAULT NULL,
  `answered_survey_id` int DEFAULT NULL,
  `revtype` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`rev`),
  KEY `rev_9b088cb8b5a15bf14b41b058e4fb67c5_idx` (`rev`),
  CONSTRAINT `rev_9b088cb8b5a15bf14b41b058e4fb67c5_fk` FOREIGN KEY (`rev`) REFERENCES `revisions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wpt_work_tutor_answered_survey_audit`
--

LOCK TABLES `wpt_work_tutor_answered_survey_audit` WRITE;
/*!40000 ALTER TABLE `wpt_work_tutor_answered_survey_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `wpt_work_tutor_answered_survey_audit` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-28  0:57:33
