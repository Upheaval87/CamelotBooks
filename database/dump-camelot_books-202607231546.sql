-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: camelot_books
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Table structure for table `account_audit_logs`
--

DROP TABLE IF EXISTS `account_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `journalable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journalable_id` bigint unsigned NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_audit_logs_user_id_foreign` (`user_id`),
  KEY `aal_polymorphic_idx` (`company_id`,`journalable_type`,`journalable_id`),
  KEY `account_audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `account_audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_audit_logs`
--

LOCK TABLES `account_audit_logs` WRITE;
/*!40000 ALTER TABLE `account_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_periods`
--

DROP TABLE IF EXISTS `accounting_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounting_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `fiscal_year_id` bigint unsigned DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_periods_company_id_start_date_end_date_unique` (`company_id`,`start_date`,`end_date`),
  KEY `accounting_periods_closed_by_foreign` (`closed_by`),
  KEY `accounting_periods_company_id_status_index` (`company_id`,`status`),
  KEY `accounting_periods_fiscal_year_id_index` (`fiscal_year_id`),
  CONSTRAINT `accounting_periods_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_periods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_periods`
--

LOCK TABLES `accounting_periods` WRITE;
/*!40000 ALTER TABLE `accounting_periods` DISABLE KEYS */;
INSERT INTO `accounting_periods` VALUES (1,1,NULL,'January 2026','2026-01-01','2026-01-31','open',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,1,NULL,'February 2026','2026-02-01','2026-02-28','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(3,1,NULL,'March 2026','2026-03-01','2026-03-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(4,1,NULL,'April 2026','2026-04-01','2026-04-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(5,1,NULL,'May 2026','2026-05-01','2026-05-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(6,1,NULL,'June 2026','2026-06-01','2026-06-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(7,1,NULL,'July 2026','2026-07-01','2026-07-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(8,1,NULL,'August 2026','2026-08-01','2026-08-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(9,1,NULL,'September 2026','2026-09-01','2026-09-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(10,1,NULL,'October 2026','2026-10-01','2026-10-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(11,1,NULL,'November 2026','2026-11-01','2026-11-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(12,1,NULL,'December 2026','2026-12-01','2026-12-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(13,2,NULL,'January 2026','2026-01-01','2026-01-31','open',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(14,2,NULL,'February 2026','2026-02-01','2026-02-28','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(15,2,NULL,'March 2026','2026-03-01','2026-03-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(16,2,NULL,'April 2026','2026-04-01','2026-04-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(17,2,NULL,'May 2026','2026-05-01','2026-05-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(18,2,NULL,'June 2026','2026-06-01','2026-06-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(19,2,NULL,'July 2026','2026-07-01','2026-07-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(20,2,NULL,'August 2026','2026-08-01','2026-08-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(21,2,NULL,'September 2026','2026-09-01','2026-09-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(22,2,NULL,'October 2026','2026-10-01','2026-10-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(23,2,NULL,'November 2026','2026-11-01','2026-11-30','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(24,2,NULL,'December 2026','2026-12-01','2026-12-31','locked',NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `accounting_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `opening_balance_date` date DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cash_flow_section` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_non_cash` tinyint(1) NOT NULL DEFAULT '0',
  `is_bank_account` tinyint(1) NOT NULL DEFAULT '0',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_routing_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_company_id_code_unique` (`company_id`,`code`),
  KEY `accounts_parent_id_foreign` (`parent_id`),
  KEY `accounts_company_id_type_index` (`company_id`,`type`),
  KEY `accounts_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `accounts_is_bank_account_index` (`is_bank_account`),
  CONSTRAINT `accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,1,NULL,'1000','Cash and Cash Equivalents','asset','current_asset','Cash on hand and in banks',0.00,NULL,'USD',0.00,1,NULL,0,1,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,1,1,'1010','Petty Cash','asset','current_asset',NULL,0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(3,1,NULL,'1100','Accounts Receivable','asset','current_asset','Amounts owed by customers',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(4,1,NULL,'1200','Inventory','asset','current_asset','Goods held for sale',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(5,1,NULL,'1300','Prepaid Expenses','asset','current_asset','Expenses paid in advance',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(6,1,NULL,'1150','Tax Receivable','asset','current_asset','Input tax / VAT receivable',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(7,1,NULL,'1500','Property, Plant and Equipment','asset','non_current_asset','Fixed assets',0.00,NULL,'USD',0.00,1,'investing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(8,1,7,'1600','Accumulated Depreciation','asset','non_current_asset','Total depreciation of fixed assets',0.00,NULL,'USD',0.00,1,NULL,1,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(9,1,NULL,'2000','Accounts Payable','liability','current_liability','Amounts owed to suppliers',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(10,1,NULL,'2100','Accrued Expenses','liability','current_liability','Expenses incurred but not yet paid',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(11,1,NULL,'2200','Unearned Revenue','liability','current_liability','Revenue received but not yet earned',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(12,1,NULL,'2300','Sales Tax Payable','liability','current_liability','Sales tax / VAT collected',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(13,1,NULL,'2500','Long-term Liabilities','liability','non_current_liability','Liabilities due beyond one year',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(14,1,NULL,'3000','Owner Equity','equity','equity','Owner investment in the business',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(15,1,NULL,'3100','Retained Earnings','equity','equity','Accumulated profits retained in the business',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(16,1,NULL,'3200','Current Year Earnings','equity','equity','Net income for the current period',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(17,1,NULL,'4000','Sales Revenue','income','operating_revenue','Revenue from primary business activities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(18,1,NULL,'4100','Service Revenue','income','operating_revenue','Revenue from services rendered',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(19,1,NULL,'4200','Other Income','income','non_operating_revenue','Income from non-primary activities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(20,1,NULL,'5000','Cost of Goods Sold','expense','cost_of_goods_sold','Direct costs of goods sold',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(21,1,NULL,'6000','Salary Expense','expense','operating_expense','Employee salaries and wages',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(22,1,NULL,'6100','Rent Expense','expense','operating_expense','Rental payments for office space',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(23,1,NULL,'6200','Utilities Expense','expense','operating_expense','Electricity, water, and other utilities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(24,1,NULL,'6300','Office Supplies Expense','expense','operating_expense','Stationery and office materials',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(25,1,NULL,'6400','Depreciation Expense','expense','operating_expense','Depreciation of fixed assets',0.00,NULL,'USD',0.00,1,NULL,1,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(26,1,NULL,'6500','Insurance Expense','expense','operating_expense','Insurance premiums',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(27,1,NULL,'6600','Professional Fees','expense','operating_expense','Legal, accounting, and consulting fees',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(28,1,NULL,'7000','Interest Expense','expense','non_operating_expense','Interest on borrowings',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(29,2,NULL,'1000','Cash and Cash Equivalents','asset','current_asset','Cash on hand and in banks',0.00,NULL,'USD',0.00,1,NULL,0,1,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(30,2,29,'1010','Petty Cash','asset','current_asset',NULL,0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(31,2,NULL,'1100','Accounts Receivable','asset','current_asset','Amounts owed by customers',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(32,2,NULL,'1200','Inventory','asset','current_asset','Goods held for sale',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(33,2,NULL,'1300','Prepaid Expenses','asset','current_asset','Expenses paid in advance',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(34,2,NULL,'1150','Tax Receivable','asset','current_asset','Input tax / VAT receivable',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(35,2,NULL,'1500','Property, Plant and Equipment','asset','non_current_asset','Fixed assets',0.00,NULL,'USD',0.00,1,'investing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(36,2,35,'1600','Accumulated Depreciation','asset','non_current_asset','Total depreciation of fixed assets',0.00,NULL,'USD',0.00,1,NULL,1,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(37,2,NULL,'2000','Accounts Payable','liability','current_liability','Amounts owed to suppliers',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(38,2,NULL,'2100','Accrued Expenses','liability','current_liability','Expenses incurred but not yet paid',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(39,2,NULL,'2200','Unearned Revenue','liability','current_liability','Revenue received but not yet earned',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(40,2,NULL,'2300','Sales Tax Payable','liability','current_liability','Sales tax / VAT collected',0.00,NULL,'USD',0.00,1,'operating',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(41,2,NULL,'2500','Long-term Liabilities','liability','non_current_liability','Liabilities due beyond one year',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(42,2,NULL,'3000','Owner Equity','equity','equity','Owner investment in the business',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(43,2,NULL,'3100','Retained Earnings','equity','equity','Accumulated profits retained in the business',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(44,2,NULL,'3200','Current Year Earnings','equity','equity','Net income for the current period',0.00,NULL,'USD',0.00,1,'financing',0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(45,2,NULL,'4000','Sales Revenue','income','operating_revenue','Revenue from primary business activities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(46,2,NULL,'4100','Service Revenue','income','operating_revenue','Revenue from services rendered',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(47,2,NULL,'4200','Other Income','income','non_operating_revenue','Income from non-primary activities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(48,2,NULL,'5000','Cost of Goods Sold','expense','cost_of_goods_sold','Direct costs of goods sold',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(49,2,NULL,'6000','Salary Expense','expense','operating_expense','Employee salaries and wages',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(50,2,NULL,'6100','Rent Expense','expense','operating_expense','Rental payments for office space',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(51,2,NULL,'6200','Utilities Expense','expense','operating_expense','Electricity, water, and other utilities',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(52,2,NULL,'6300','Office Supplies Expense','expense','operating_expense','Stationery and office materials',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(53,2,NULL,'6400','Depreciation Expense','expense','operating_expense','Depreciation of fixed assets',0.00,NULL,'USD',0.00,1,NULL,1,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(54,2,NULL,'6500','Insurance Expense','expense','operating_expense','Insurance premiums',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(55,2,NULL,'6600','Professional Fees','expense','operating_expense','Legal, accounting, and consulting fees',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(56,2,NULL,'7000','Interest Expense','expense','non_operating_expense','Interest on borrowings',0.00,NULL,'USD',0.00,1,NULL,0,0,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_settings`
--

DROP TABLE IF EXISTS `approval_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `threshold_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_settings_company_id_unique` (`company_id`),
  CONSTRAINT `approval_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_settings`
--

LOCK TABLES `approval_settings` WRITE;
/*!40000 ALTER TABLE `approval_settings` DISABLE KEYS */;
INSERT INTO `approval_settings` VALUES (1,1,0,0.00,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,2,0,0.00,'2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `approval_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliation_items`
--

DROP TABLE IF EXISTS `bank_reconciliation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_id` bigint unsigned NOT NULL,
  `bank_statement_line_id` bigint unsigned DEFAULT NULL,
  `bank_transaction_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliation_items_reconciliation_id_index` (`reconciliation_id`),
  KEY `bank_reconciliation_items_bank_statement_line_id_index` (`bank_statement_line_id`),
  KEY `bank_reconciliation_items_bank_transaction_id_index` (`bank_transaction_id`),
  CONSTRAINT `bank_reconciliation_items_bank_statement_line_id_foreign` FOREIGN KEY (`bank_statement_line_id`) REFERENCES `bank_statement_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_reconciliation_items_bank_transaction_id_foreign` FOREIGN KEY (`bank_transaction_id`) REFERENCES `bank_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_reconciliation_items_reconciliation_id_foreign` FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation_items`
--

LOCK TABLES `bank_reconciliation_items` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `bank_account_id` bigint unsigned NOT NULL,
  `import_id` bigint unsigned DEFAULT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `cleared_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `completed_by` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_reconciliations_bank_account_id_foreign` (`bank_account_id`),
  KEY `bank_reconciliations_import_id_foreign` (`import_id`),
  KEY `bank_reconciliations_completed_by_foreign` (`completed_by`),
  KEY `br_co_ba_status_idx` (`company_id`,`bank_account_id`,`status`),
  CONSTRAINT `bank_reconciliations_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `bank_reconciliations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliations_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_reconciliations_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `bank_statement_imports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_imports`
--

DROP TABLE IF EXISTS `bank_statement_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_statement_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `bank_account_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statement_date` date NOT NULL,
  `statement_end_balance` decimal(15,2) NOT NULL,
  `line_count` int unsigned NOT NULL DEFAULT '0',
  `imported_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_imports_bank_account_id_foreign` (`bank_account_id`),
  KEY `bank_statement_imports_imported_by_foreign` (`imported_by`),
  KEY `bank_statement_imports_company_id_bank_account_id_index` (`company_id`,`bank_account_id`),
  CONSTRAINT `bank_statement_imports_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `bank_statement_imports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_statement_imports_imported_by_foreign` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_imports`
--

LOCK TABLES `bank_statement_imports` WRITE;
/*!40000 ALTER TABLE `bank_statement_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_statement_lines`
--

DROP TABLE IF EXISTS `bank_statement_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_statement_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_id` bigint unsigned NOT NULL,
  `bank_account_id` bigint unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `is_matched` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_lines_import_id_index` (`import_id`),
  KEY `bank_statement_lines_bank_account_id_is_matched_index` (`bank_account_id`,`is_matched`),
  CONSTRAINT `bank_statement_lines_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `bank_statement_lines_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `bank_statement_imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_statement_lines`
--

LOCK TABLES `bank_statement_lines` WRITE;
/*!40000 ALTER TABLE `bank_statement_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_statement_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_transactions`
--

DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `bank_account_id` bigint unsigned NOT NULL,
  `journal_entry_id` bigint unsigned NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `foreign_amount` decimal(18,2) DEFAULT NULL,
  `foreign_currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `linked_transaction_id` bigint unsigned DEFAULT NULL,
  `is_reconciled` tinyint(1) NOT NULL DEFAULT '0',
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `bank_reconciliation_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_transactions_bank_account_id_foreign` (`bank_account_id`),
  KEY `bank_transactions_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `bank_transactions_linked_transaction_id_foreign` (`linked_transaction_id`),
  KEY `bank_transactions_created_by_foreign` (`created_by`),
  KEY `bank_transactions_company_id_bank_account_id_date_index` (`company_id`,`bank_account_id`,`date`),
  KEY `bank_transactions_company_id_bank_account_id_is_reconciled_index` (`company_id`,`bank_account_id`,`is_reconciled`),
  KEY `bank_transactions_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `bank_transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `bank_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bank_transactions_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`),
  CONSTRAINT `bank_transactions_linked_transaction_id_foreign` FOREIGN KEY (`linked_transaction_id`) REFERENCES `bank_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bill_lines`
--

DROP TABLE IF EXISTS `bill_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `expense_account_id` bigint unsigned NOT NULL,
  `cost_center_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_lines_product_id_foreign` (`product_id`),
  KEY `bill_lines_expense_account_id_foreign` (`expense_account_id`),
  KEY `bill_lines_bill_id_index` (`bill_id`),
  KEY `bill_lines_cost_center_id_foreign` (`cost_center_id`),
  CONSTRAINT `bill_lines_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bill_lines_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bill_lines_expense_account_id_foreign` FOREIGN KEY (`expense_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `bill_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bill_lines`
--

LOCK TABLES `bill_lines` WRITE;
/*!40000 ALTER TABLE `bill_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `bill_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `bill_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `internal_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `base_amount` decimal(15,2) DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `recurring_template_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_company_id_internal_number_unique` (`company_id`,`internal_number`),
  KEY `bills_vendor_id_foreign` (`vendor_id`),
  KEY `bills_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `bills_created_by_foreign` (`created_by`),
  KEY `bills_approved_by_foreign` (`approved_by`),
  KEY `bills_posted_by_foreign` (`posted_by`),
  KEY `bills_voided_by_foreign` (`voided_by`),
  KEY `bills_company_id_status_index` (`company_id`,`status`),
  KEY `bills_company_id_vendor_id_index` (`company_id`,`vendor_id`),
  KEY `bills_company_id_due_date_status_index` (`company_id`,`due_date`,`status`),
  CONSTRAINT `bills_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bills_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bills_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,1,'Headquarters','HQ','123 Business Ave',1,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,1,'West Branch','WB','789 West St',1,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(3,2,'Main Office','MO','456 Corporate Blvd',1,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(4,2,'East Branch','EB','321 East Rd',1,'2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `fiscal_year_start_month` tinyint NOT NULL DEFAULT '1',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `allow_negative_stock` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_company_code_unique` (`company_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Acme Corporation','Acme Corporation Ltd.','ACME','123456789','123 Business Ave','New York','NY','US','10001','+1-555-0100','info@acme.test','USD',1,NULL,1,0,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,'Beta Industries','Beta Industries Inc.','BETA','987654321','456 Corporate Blvd','Los Angeles','CA','US','90001','+1-555-0200','info@beta.test','USD',1,NULL,1,0,'2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_allowances`
--

DROP TABLE IF EXISTS `company_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_allowances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('allowance','deduction') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'allowance',
  `gl_account_id` bigint unsigned DEFAULT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `allowance_company_code` (`company_id`,`code`),
  KEY `company_allowances_gl_account_id_foreign` (`gl_account_id`),
  CONSTRAINT `company_allowances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_allowances_gl_account_id_foreign` FOREIGN KEY (`gl_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_allowances`
--

LOCK TABLES `company_allowances` WRITE;
/*!40000 ALTER TABLE `company_allowances` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_user`
--

DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_company_id_user_id_unique` (`company_id`,`user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_user`
--

LOCK TABLES `company_user` WRITE;
/*!40000 ALTER TABLE `company_user` DISABLE KEYS */;
INSERT INTO `company_user` VALUES (1,1,1,'company_admin','2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,2,1,'company_admin','2026-07-23 05:57:19','2026-07-23 05:57:19'),(3,1,2,'accountant','2026-07-23 05:57:19','2026-07-23 05:57:19'),(4,2,2,'accountant','2026-07-23 05:57:19','2026-07-23 05:57:19');
/*!40000 ALTER TABLE `company_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cost_centers_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `cost_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_note_allocations`
--

DROP TABLE IF EXISTS `credit_note_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_allocations_credit_note_id_index` (`credit_note_id`),
  KEY `credit_note_allocations_invoice_id_index` (`invoice_id`),
  CONSTRAINT `credit_note_allocations_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_note_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_note_allocations`
--

LOCK TABLES `credit_note_allocations` WRITE;
/*!40000 ALTER TABLE `credit_note_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_note_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_note_lines`
--

DROP TABLE IF EXISTS `credit_note_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `income_account_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_lines_product_id_foreign` (`product_id`),
  KEY `credit_note_lines_income_account_id_foreign` (`income_account_id`),
  KEY `credit_note_lines_credit_note_id_index` (`credit_note_id`),
  CONSTRAINT `credit_note_lines_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_note_lines_income_account_id_foreign` FOREIGN KEY (`income_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `credit_note_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_note_lines`
--

LOCK TABLES `credit_note_lines` WRITE;
/*!40000 ALTER TABLE `credit_note_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_note_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `credit_note_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_note_date` date NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_applied` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_refunded` decimal(15,2) NOT NULL DEFAULT '0.00',
  `invoice_id` bigint unsigned DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_notes_company_id_credit_note_number_unique` (`company_id`,`credit_note_number`),
  KEY `credit_notes_customer_id_foreign` (`customer_id`),
  KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  KEY `credit_notes_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `credit_notes_created_by_foreign` (`created_by`),
  KEY `credit_notes_posted_by_foreign` (`posted_by`),
  KEY `credit_notes_voided_by_foreign` (`voided_by`),
  KEY `credit_notes_company_id_status_index` (`company_id`,`status`),
  KEY `credit_notes_company_id_customer_id_index` (`company_id`,`customer_id`),
  CONSTRAINT `credit_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_notes_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_notes_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_notes_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_notes`
--

LOCK TABLES `credit_notes` WRITE;
/*!40000 ALTER TABLE `credit_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_payment_allocations`
--

DROP TABLE IF EXISTS `customer_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_payment_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_payment_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_payment_allocations_customer_payment_id_index` (`customer_payment_id`),
  KEY `customer_payment_allocations_invoice_id_index` (`invoice_id`),
  CONSTRAINT `customer_payment_allocations_customer_payment_id_foreign` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_payment_allocations`
--

LOCK TABLES `customer_payment_allocations` WRITE;
/*!40000 ALTER TABLE `customer_payment_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_payments`
--

DROP TABLE IF EXISTS `customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `payment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `foreign_amount` decimal(18,2) DEFAULT NULL,
  `foreign_currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'bank_transfer',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `bank_account_id` bigint unsigned NOT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_payments_company_id_payment_number_unique` (`company_id`,`payment_number`),
  KEY `customer_payments_customer_id_foreign` (`customer_id`),
  KEY `customer_payments_bank_account_id_foreign` (`bank_account_id`),
  KEY `customer_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `customer_payments_created_by_foreign` (`created_by`),
  KEY `customer_payments_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `customer_payments_company_id_payment_date_index` (`company_id`,`payment_date`),
  CONSTRAINT `customer_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `customer_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `customer_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_payments`
--

LOCK TABLES `customer_payments` WRITE;
/*!40000 ALTER TABLE `customer_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'net_30',
  `payment_terms_days` smallint DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `opening_balance_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `customers_company_id_name_index` (`company_id`,`name`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_payments`
--

DROP TABLE IF EXISTS `employee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `payroll_run_id` bigint unsigned DEFAULT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `payment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_type` enum('salary','paye_remittance','pension_remittance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'salary',
  `bank_account_id` bigint unsigned DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `epmt_company_number` (`company_id`,`payment_number`),
  KEY `employee_payments_payroll_run_id_foreign` (`payroll_run_id`),
  KEY `employee_payments_employee_id_foreign` (`employee_id`),
  KEY `employee_payments_bank_account_id_foreign` (`bank_account_id`),
  KEY `employee_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `employee_payments_created_by_foreign` (`created_by`),
  CONSTRAINT `employee_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_payments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_payments_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payments`
--

LOCK TABLES `employee_payments` WRITE;
/*!40000 ALTER TABLE `employee_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salary_items`
--

DROP TABLE IF EXISTS `employee_salary_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salary_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `salary_structure_id` bigint unsigned NOT NULL,
  `company_allowance_id` bigint unsigned DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_salary_items_salary_structure_id_foreign` (`salary_structure_id`),
  KEY `employee_salary_items_company_allowance_id_foreign` (`company_allowance_id`),
  CONSTRAINT `employee_salary_items_company_allowance_id_foreign` FOREIGN KEY (`company_allowance_id`) REFERENCES `company_allowances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_salary_items_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `employee_salary_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salary_items`
--

LOCK TABLES `employee_salary_items` WRITE;
/*!40000 ALTER TABLE `employee_salary_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salary_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salary_structures`
--

DROP TABLE IF EXISTS `employee_salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_salary_structures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `basic_pay` decimal(15,2) NOT NULL DEFAULT '0.00',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_current_unique` (`company_id`,`employee_id`,`is_current`),
  KEY `employee_salary_structures_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_salary_structures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salary_structures`
--

LOCK TABLES `employee_salary_structures` WRITE;
/*!40000 ALTER TABLE `employee_salary_structures` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salary_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `cost_center_id` bigint unsigned DEFAULT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `position` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hire_date` date NOT NULL,
  `termination_date` date DEFAULT NULL,
  `employment_status` enum('active','terminated','suspended','on_leave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `tax_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pension_member_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pension_scheme_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_company_number` (`company_id`,`employee_number`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_cost_center_id_foreign` (`cost_center_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `currency_from` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_to` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(18,8) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fx_rates_lookup` (`company_id`,`currency_from`,`currency_to`,`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_years`
--

DROP TABLE IF EXISTS `fiscal_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiscal_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `closed_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closing_entry_id` bigint unsigned DEFAULT NULL,
  `reopen_reason` text COLLATE utf8mb4_unicode_ci,
  `reopened_by` bigint unsigned DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fiscal_years_company_id_label_unique` (`company_id`,`label`),
  KEY `fiscal_years_company_id_status_index` (`company_id`,`status`),
  KEY `fiscal_years_company_id_start_date_end_date_index` (`company_id`,`start_date`,`end_date`),
  CONSTRAINT `fiscal_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_years`
--

LOCK TABLES `fiscal_years` WRITE;
/*!40000 ALTER TABLE `fiscal_years` DISABLE KEYS */;
/*!40000 ALTER TABLE `fiscal_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_adjustments`
--

DROP TABLE IF EXISTS `inventory_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `adjustment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `type` enum('increase','decrease') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `reason_code` enum('found_in_count','damage','shrinkage','correction','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `unit_cost` decimal(15,4) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `status` enum('draft','posted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adj_company_number` (`company_id`,`adjustment_number`),
  KEY `inventory_adjustments_product_id_foreign` (`product_id`),
  KEY `inventory_adjustments_branch_id_foreign` (`branch_id`),
  KEY `inventory_adjustments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `inventory_adjustments_created_by_foreign` (`created_by`),
  CONSTRAINT `inventory_adjustments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_adjustments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_adjustments`
--

LOCK TABLES `inventory_adjustments` WRITE;
/*!40000 ALTER TABLE `inventory_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_cost_layers`
--

DROP TABLE IF EXISTS `inventory_cost_layers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_cost_layers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `quantity_remaining` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_cost_layers_product_id_foreign` (`product_id`),
  KEY `inventory_cost_layers_branch_id_foreign` (`branch_id`),
  KEY `inventory_cost_layers_company_id_product_id_branch_id_index` (`company_id`,`product_id`,`branch_id`),
  KEY `inventory_cost_layers_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `inventory_cost_layers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_cost_layers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_cost_layers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_cost_layers`
--

LOCK TABLES `inventory_cost_layers` WRITE;
/*!40000 ALTER TABLE `inventory_cost_layers` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_cost_layers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock`
--

DROP TABLE IF EXISTS `inventory_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `quantity_on_hand` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_stock_company_product_branch` (`company_id`,`product_id`,`branch_id`),
  KEY `inventory_stock_product_id_foreign` (`product_id`),
  KEY `inventory_stock_branch_id_foreign` (`branch_id`),
  CONSTRAINT `inventory_stock_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_stock_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transfers`
--

DROP TABLE IF EXISTS `inventory_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `from_branch_id` bigint unsigned DEFAULT NULL,
  `to_branch_id` bigint unsigned DEFAULT NULL,
  `transfer_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `xfer_company_number` (`company_id`,`transfer_number`),
  KEY `inventory_transfers_product_id_foreign` (`product_id`),
  KEY `inventory_transfers_from_branch_id_foreign` (`from_branch_id`),
  KEY `inventory_transfers_to_branch_id_foreign` (`to_branch_id`),
  KEY `inventory_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `inventory_transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transfers_from_branch_id_foreign` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transfers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transfers_to_branch_id_foreign` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transfers`
--

LOCK TABLES `inventory_transfers` WRITE;
/*!40000 ALTER TABLE `inventory_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_lines`
--

DROP TABLE IF EXISTS `invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `income_account_id` bigint unsigned NOT NULL,
  `cost_center_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_lines_product_id_foreign` (`product_id`),
  KEY `invoice_lines_income_account_id_foreign` (`income_account_id`),
  KEY `invoice_lines_invoice_id_index` (`invoice_id`),
  KEY `invoice_lines_cost_center_id_foreign` (`cost_center_id`),
  CONSTRAINT `invoice_lines_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_lines_income_account_id_foreign` FOREIGN KEY (`income_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_lines`
--

LOCK TABLES `invoice_lines` WRITE;
/*!40000 ALTER TABLE `invoice_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `base_amount` decimal(15,2) DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `recurring_template_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_company_id_invoice_number_unique` (`company_id`,`invoice_number`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  KEY `invoices_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_posted_by_foreign` (`posted_by`),
  KEY `invoices_voided_by_foreign` (`voided_by`),
  KEY `invoices_company_id_status_index` (`company_id`,`status`),
  KEY `invoices_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `invoices_company_id_invoice_date_index` (`company_id`,`invoice_date`),
  KEY `invoices_company_id_due_date_status_index` (`company_id`,`due_date`,`status`),
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `journal_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_adjusting_entry` tinyint(1) NOT NULL DEFAULT '0',
  `source_module` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linked_entry_id` bigint unsigned DEFAULT NULL,
  `recurring_template_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_entries_company_id_journal_number_unique` (`company_id`,`journal_number`),
  KEY `journal_entries_branch_id_foreign` (`branch_id`),
  KEY `journal_entries_linked_entry_id_foreign` (`linked_entry_id`),
  KEY `journal_entries_recurring_template_id_foreign` (`recurring_template_id`),
  KEY `journal_entries_created_by_foreign` (`created_by`),
  KEY `journal_entries_posted_by_foreign` (`posted_by`),
  KEY `journal_entries_approved_by_foreign` (`approved_by`),
  KEY `journal_entries_rejected_by_foreign` (`rejected_by`),
  KEY `journal_entries_company_id_status_index` (`company_id`,`status`),
  KEY `journal_entries_company_id_date_index` (`company_id`,`date`),
  KEY `journal_entries_company_id_branch_id_index` (`company_id`,`branch_id`),
  CONSTRAINT `journal_entries_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_entries_linked_entry_id_foreign` FOREIGN KEY (`linked_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_recurring_template_id_foreign` FOREIGN KEY (`recurring_template_id`) REFERENCES `recurring_journal_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES (1,1,1,'JE-2026-0001','2026-01-06','INV-001','Cash sales revenue','posted',0,'seed',NULL,NULL,1,1,'2026-07-23 05:57:19',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,1,1,'JE-2026-0002','2026-01-09','INV-002','Service revenue on account','posted',0,'seed',NULL,NULL,2,2,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(3,1,1,'JE-2026-0003','2026-01-12','CHK-001','Monthly office rent payment','posted',0,'seed',NULL,NULL,1,1,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(4,1,1,'JE-2026-0004','2026-01-15','UTIL-001','Utilities bill received','posted',0,'seed',NULL,NULL,2,2,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(5,1,1,'JE-2026-0005','2026-01-18','PAY-001','Biweekly payroll','posted',0,'seed',NULL,NULL,1,1,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(6,1,1,'JE-2026-0006','2026-01-21','PO-001','Inventory purchase','posted',0,'seed',NULL,NULL,2,2,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(7,1,1,'JE-2026-0007','2026-01-24','REC-001','Collection from customer','posted',0,'seed',NULL,NULL,1,1,'2026-07-23 05:57:20',NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entry_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `cost_center_id` bigint unsigned DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `foreign_amount` decimal(18,2) DEFAULT NULL,
  `foreign_currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `entity_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entry_lines_branch_id_foreign` (`branch_id`),
  KEY `journal_entry_lines_journal_entry_id_index` (`journal_entry_id`),
  KEY `journal_entry_lines_account_id_index` (`account_id`),
  KEY `journal_entry_lines_cost_center_id_foreign` (`cost_center_id`),
  CONSTRAINT `journal_entry_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `journal_entry_lines_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entry_lines_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entry_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entry_lines`
--

LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
INSERT INTO `journal_entry_lines` VALUES (1,1,1,1,NULL,10000.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(2,1,17,1,NULL,0.00,10000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19'),(3,2,3,1,NULL,5000.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(4,2,18,1,NULL,0.00,5000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(5,3,22,1,NULL,2500.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(6,3,1,1,NULL,0.00,2500.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(7,4,23,1,NULL,500.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(8,4,9,1,NULL,0.00,500.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(9,5,21,1,NULL,8000.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(10,5,1,1,NULL,0.00,8000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(11,6,20,1,NULL,3000.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(12,6,1,1,NULL,0.00,3000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(13,7,1,1,NULL,7500.00,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20'),(14,7,3,1,NULL,0.00,7500.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-23 05:57:20','2026-07-23 05:57:20');
/*!40000 ALTER TABLE `journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_01_000010_create_companies_table',1),(5,'2026_01_01_000020_create_branches_table',1),(6,'2026_01_01_000030_create_company_user_table',1),(7,'2026_01_01_000040_add_current_company_to_users_table',1),(8,'2026_01_01_000050_create_accounts_table',1),(9,'2026_01_01_000060_create_accounting_periods_table',1),(10,'2026_01_01_000070_create_recurring_journal_templates_table',1),(11,'2026_01_01_000080_create_journal_entries_table',1),(12,'2026_01_01_000090_create_journal_entry_lines_table',1),(13,'2026_01_01_000100_create_recurring_journal_template_lines_table',1),(14,'2026_01_01_000110_create_account_audit_logs_table',1),(15,'2026_01_01_000120_create_approval_settings_table',1),(16,'2026_02_01_000010_add_bank_fields_to_accounts_table',1),(17,'2026_02_01_000020_create_customers_table',1),(18,'2026_02_01_000030_create_vendors_table',1),(19,'2026_02_01_000040_create_products_table',1),(20,'2026_02_01_000050_create_invoices_table',1),(21,'2026_02_01_000060_create_invoice_lines_table',1),(22,'2026_02_01_000070_create_recurring_invoice_templates_table',1),(23,'2026_02_01_000080_create_recurring_invoice_template_lines_table',1),(24,'2026_02_01_000090_create_credit_notes_table',1),(25,'2026_02_01_000095_create_credit_note_lines_table',1),(26,'2026_02_01_000100_create_credit_note_allocations_table',1),(27,'2026_02_01_000100_create_customer_payments_table',1),(28,'2026_02_01_000110_create_customer_payment_allocations_table',1),(29,'2026_02_01_000120_create_bills_table',1),(30,'2026_02_01_000130_create_bill_lines_table',1),(31,'2026_02_01_000140_create_recurring_bill_templates_table',1),(32,'2026_02_01_000150_create_recurring_bill_template_lines_table',1),(33,'2026_02_01_000160_create_vendor_payments_table',1),(34,'2026_02_01_000170_create_vendor_payment_allocations_table',1),(35,'2026_02_01_000180_create_vendor_credits_table',1),(36,'2026_02_01_000185_create_vendor_credit_lines_table',1),(37,'2026_02_01_000188_create_bank_transactions_table',1),(38,'2026_02_01_000190_create_vendor_credit_allocations_table',1),(39,'2026_02_01_000200_create_bank_statement_imports_table',1),(40,'2026_02_01_000210_create_bank_statement_lines_table',1),(41,'2026_02_01_000220_create_bank_reconciliations_table',1),(42,'2026_02_01_000230_create_bank_reconciliation_items_table',1),(43,'2026_03_01_000010_add_cash_flow_fields_to_accounts_table',1),(44,'2026_04_01_000010_create_fiscal_years_table',1),(45,'2026_04_01_000015_add_fiscal_year_id_to_accounting_periods_table',1),(46,'2026_05_01_000010_create_cost_centers_table',1),(47,'2026_05_01_000015_add_cost_center_id_to_journal_entry_lines_table',1),(48,'2026_05_01_000020_create_exchange_rates_table',1),(49,'2026_05_01_000025_add_fx_fields_to_tables',1),(50,'2026_06_01_000010_add_inventory_fields_to_products_table',1),(51,'2026_06_01_000020_create_inventory_cost_layers_table',1),(52,'2026_06_01_000030_create_inventory_stock_table',1),(53,'2026_06_01_000040_create_inventory_adjustments_table',1),(54,'2026_06_01_000050_create_inventory_transfers_table',1),(55,'2026_06_01_000060_add_allow_negative_stock_to_companies_table',1),(56,'2026_07_01_000010_create_employees_table',1),(57,'2026_07_01_000015_create_employee_salary_structures_table',1),(58,'2026_07_01_000025_create_company_allowances_table',1),(59,'2026_07_01_000027_create_employee_salary_items_table',1),(60,'2026_07_01_000030_create_paye_tables_table',1),(61,'2026_07_01_000035_create_pension_schemes_table',1),(62,'2026_07_01_000040_create_payroll_runs_table',1),(63,'2026_07_01_000045_create_payroll_run_items_table',1),(64,'2026_07_01_000050_create_employee_payments_table',1),(65,'2026_07_01_000060_add_cost_center_to_invoice_lines_table',1),(66,'2026_07_01_000065_add_cost_center_to_bill_lines_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paye_table_bands`
--

DROP TABLE IF EXISTS `paye_table_bands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paye_table_bands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paye_table_id` bigint unsigned NOT NULL,
  `threshold` decimal(15,2) NOT NULL,
  `upper_limit` decimal(15,2) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paye_table_bands_paye_table_id_foreign` (`paye_table_id`),
  CONSTRAINT `paye_table_bands_paye_table_id_foreign` FOREIGN KEY (`paye_table_id`) REFERENCES `paye_tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paye_table_bands`
--

LOCK TABLES `paye_table_bands` WRITE;
/*!40000 ALTER TABLE `paye_table_bands` DISABLE KEYS */;
/*!40000 ALTER TABLE `paye_table_bands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paye_tables`
--

DROP TABLE IF EXISTS `paye_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paye_tables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `version_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paye_current_unique` (`company_id`,`is_current`),
  CONSTRAINT `paye_tables_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paye_tables`
--

LOCK TABLES `paye_tables` WRITE;
/*!40000 ALTER TABLE `paye_tables` DISABLE KEYS */;
/*!40000 ALTER TABLE `paye_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_run_items`
--

DROP TABLE IF EXISTS `payroll_run_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_run_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `basic_pay` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_allowances` decimal(15,2) NOT NULL DEFAULT '0.00',
  `gross_pay` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paye` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pension_ee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pension_er` decimal(15,2) NOT NULL DEFAULT '0.00',
  `employer_pension_expense` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payslip_data` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_run_items_payroll_run_id_foreign` (`payroll_run_id`),
  KEY `payroll_run_items_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_run_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_run_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_run_items`
--

LOCK TABLES `payroll_run_items` WRITE;
/*!40000 ALTER TABLE `payroll_run_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_run_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `run_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pay_date` date NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('draft','calculated','posted','partially_paid','fully_paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_gross` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_paye` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_pension_ee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_pension_er` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_net_pay` decimal(15,2) NOT NULL DEFAULT '0.00',
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `paye_table_id` bigint unsigned DEFAULT NULL,
  `pension_scheme_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prun_company_number` (`company_id`,`run_number`),
  KEY `payroll_runs_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `payroll_runs_paye_table_id_foreign` (`paye_table_id`),
  KEY `payroll_runs_pension_scheme_id_foreign` (`pension_scheme_id`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  CONSTRAINT `payroll_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_paye_table_id_foreign` FOREIGN KEY (`paye_table_id`) REFERENCES `paye_tables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_pension_scheme_id_foreign` FOREIGN KEY (`pension_scheme_id`) REFERENCES `pension_schemes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pension_schemes`
--

DROP TABLE IF EXISTS `pension_schemes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pension_schemes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_rate` decimal(5,2) NOT NULL,
  `employer_rate` decimal(5,2) NOT NULL,
  `max_contributory_salary` decimal(15,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pension_current_unique` (`company_id`,`is_current`),
  CONSTRAINT `pension_schemes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pension_schemes`
--

LOCK TABLES `pension_schemes` WRITE;
/*!40000 ALTER TABLE `pension_schemes` DISABLE KEYS */;
/*!40000 ALTER TABLE `pension_schemes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `tracked_as_inventory` tinyint(1) NOT NULL DEFAULT '0',
  `sales_price` decimal(15,2) NOT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `reorder_point` decimal(15,2) DEFAULT NULL,
  `unit_of_measure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_account_id` bigint unsigned NOT NULL,
  `expense_account_id` bigint unsigned DEFAULT NULL,
  `inventory_asset_account_id` bigint unsigned DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_taxable` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_company_id_sku_unique` (`company_id`,`sku`),
  KEY `products_income_account_id_foreign` (`income_account_id`),
  KEY `products_expense_account_id_foreign` (`expense_account_id`),
  KEY `products_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `products_inventory_asset_account_id_foreign` (`inventory_asset_account_id`),
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_expense_account_id_foreign` FOREIGN KEY (`expense_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `products_income_account_id_foreign` FOREIGN KEY (`income_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `products_inventory_asset_account_id_foreign` FOREIGN KEY (`inventory_asset_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_bill_template_lines`
--

DROP TABLE IF EXISTS `recurring_bill_template_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_bill_template_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rbt_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `expense_account_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_bill_template_lines_product_id_foreign` (`product_id`),
  KEY `recurring_bill_template_lines_expense_account_id_foreign` (`expense_account_id`),
  KEY `recurring_bill_template_lines_rbt_id_index` (`rbt_id`),
  CONSTRAINT `recurring_bill_template_lines_expense_account_id_foreign` FOREIGN KEY (`expense_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `recurring_bill_template_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurring_bill_template_lines_rbt_id_foreign` FOREIGN KEY (`rbt_id`) REFERENCES `recurring_bill_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_bill_template_lines`
--

LOCK TABLES `recurring_bill_template_lines` WRITE;
/*!40000 ALTER TABLE `recurring_bill_template_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_bill_template_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_bill_templates`
--

DROP TABLE IF EXISTS `recurring_bill_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_bill_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_month` tinyint DEFAULT NULL,
  `day_of_week` tinyint DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_run_date` date NOT NULL,
  `auto_post` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_bill_templates_vendor_id_foreign` (`vendor_id`),
  KEY `recurring_bill_templates_created_by_foreign` (`created_by`),
  KEY `rbt_active_next_run_idx` (`company_id`,`is_active`,`next_run_date`),
  CONSTRAINT `recurring_bill_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_bill_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `recurring_bill_templates_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_bill_templates`
--

LOCK TABLES `recurring_bill_templates` WRITE;
/*!40000 ALTER TABLE `recurring_bill_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_bill_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_invoice_template_lines`
--

DROP TABLE IF EXISTS `recurring_invoice_template_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_invoice_template_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rit_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `income_account_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_invoice_template_lines_product_id_foreign` (`product_id`),
  KEY `recurring_invoice_template_lines_income_account_id_foreign` (`income_account_id`),
  KEY `recurring_invoice_template_lines_rit_id_index` (`rit_id`),
  CONSTRAINT `recurring_invoice_template_lines_income_account_id_foreign` FOREIGN KEY (`income_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `recurring_invoice_template_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurring_invoice_template_lines_rit_id_foreign` FOREIGN KEY (`rit_id`) REFERENCES `recurring_invoice_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_invoice_template_lines`
--

LOCK TABLES `recurring_invoice_template_lines` WRITE;
/*!40000 ALTER TABLE `recurring_invoice_template_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_invoice_template_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_invoice_templates`
--

DROP TABLE IF EXISTS `recurring_invoice_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_invoice_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_month` tinyint DEFAULT NULL,
  `day_of_week` tinyint DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_run_date` date NOT NULL,
  `auto_post` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_invoice_templates_customer_id_foreign` (`customer_id`),
  KEY `recurring_invoice_templates_created_by_foreign` (`created_by`),
  KEY `rit_active_next_run_idx` (`company_id`,`is_active`,`next_run_date`),
  CONSTRAINT `recurring_invoice_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_invoice_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `recurring_invoice_templates_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_invoice_templates`
--

LOCK TABLES `recurring_invoice_templates` WRITE;
/*!40000 ALTER TABLE `recurring_invoice_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_invoice_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_journal_template_lines`
--

DROP TABLE IF EXISTS `recurring_journal_template_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_journal_template_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rjt_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `memo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_journal_template_lines_account_id_foreign` (`account_id`),
  KEY `recurring_journal_template_lines_branch_id_foreign` (`branch_id`),
  KEY `recurring_journal_template_lines_rjt_id_index` (`rjt_id`),
  CONSTRAINT `recurring_journal_template_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `recurring_journal_template_lines_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rjt_lines_rjt_fk` FOREIGN KEY (`rjt_id`) REFERENCES `recurring_journal_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_journal_template_lines`
--

LOCK TABLES `recurring_journal_template_lines` WRITE;
/*!40000 ALTER TABLE `recurring_journal_template_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_journal_template_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_journal_templates`
--

DROP TABLE IF EXISTS `recurring_journal_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_journal_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_month` tinyint DEFAULT NULL,
  `day_of_week` tinyint DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_run_date` date NOT NULL,
  `auto_post` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recurring_journal_templates_branch_id_foreign` (`branch_id`),
  KEY `recurring_journal_templates_created_by_foreign` (`created_by`),
  KEY `rjt_active_next_run_idx` (`company_id`,`is_active`,`next_run_date`),
  CONSTRAINT `recurring_journal_templates_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recurring_journal_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recurring_journal_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_journal_templates`
--

LOCK TABLES `recurring_journal_templates` WRITE;
/*!40000 ALTER TABLE `recurring_journal_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_journal_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('ZxqFR7qooBouqid4glnp76ayZNyDYwkY4nNBXR7C',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0','YTo2OntzOjY6Il90b2tlbiI7czo0MDoicXprM3J0NWNnaG9BNEhWSGJOaEVxVnpyTnBaS1lLeU9KRHcxZk10SCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTc6Imh0dHA6Ly9jYW1lbG90Ym9va3MubG9jYWw6ODA4MC9hY2NvdW50aW5nL3Byb2R1Y3RzL2NyZWF0ZSI7czo1OiJyb3V0ZSI7czoyNjoiYWNjb3VudGluZy5wcm9kdWN0cy5jcmVhdGUiO31zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE4OiJjdXJyZW50X2NvbXBhbnlfaWQiO2k6MTt9',1784813559);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_company_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_current_company_id_foreign` (`current_company_id`),
  CONSTRAINT `users_current_company_id_foreign` FOREIGN KEY (`current_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@test.com','2026-07-23 05:57:19','$2y$12$2lgTC1PYMtWbUE4OoMrJguJ1BZAxb2RYoENXlPtsK9CGbGRC8.eZS',NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19',1),(2,'Accountant User','accountant@test.com','2026-07-23 05:57:19','$2y$12$cEpozfbT.2g6oGah2WRQgODwyvmyk9fBDnhJP.Fbw8dqUH4rcU9mO',NULL,'2026-07-23 05:57:19','2026-07-23 05:57:19',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_credit_allocations`
--

DROP TABLE IF EXISTS `vendor_credit_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_credit_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_credit_id` bigint unsigned NOT NULL,
  `bill_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_credit_allocations_vendor_credit_id_index` (`vendor_credit_id`),
  KEY `vendor_credit_allocations_bill_id_index` (`bill_id`),
  CONSTRAINT `vendor_credit_allocations_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_credit_allocations_vendor_credit_id_foreign` FOREIGN KEY (`vendor_credit_id`) REFERENCES `vendor_credits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_credit_allocations`
--

LOCK TABLES `vendor_credit_allocations` WRITE;
/*!40000 ALTER TABLE `vendor_credit_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_credit_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_credit_lines`
--

DROP TABLE IF EXISTS `vendor_credit_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_credit_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_credit_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `expense_account_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_credit_lines_product_id_foreign` (`product_id`),
  KEY `vendor_credit_lines_expense_account_id_foreign` (`expense_account_id`),
  KEY `vendor_credit_lines_vendor_credit_id_index` (`vendor_credit_id`),
  CONSTRAINT `vendor_credit_lines_expense_account_id_foreign` FOREIGN KEY (`expense_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `vendor_credit_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_credit_lines_vendor_credit_id_foreign` FOREIGN KEY (`vendor_credit_id`) REFERENCES `vendor_credits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_credit_lines`
--

LOCK TABLES `vendor_credit_lines` WRITE;
/*!40000 ALTER TABLE `vendor_credit_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_credit_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_credits`
--

DROP TABLE IF EXISTS `vendor_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_credits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `credit_note_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_note_date` date NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_applied` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_refunded` decimal(15,2) NOT NULL DEFAULT '0.00',
  `bill_id` bigint unsigned DEFAULT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `posted_by` bigint unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_credits_company_id_credit_note_number_unique` (`company_id`,`credit_note_number`),
  KEY `vendor_credits_vendor_id_foreign` (`vendor_id`),
  KEY `vendor_credits_bill_id_foreign` (`bill_id`),
  KEY `vendor_credits_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `vendor_credits_created_by_foreign` (`created_by`),
  KEY `vendor_credits_posted_by_foreign` (`posted_by`),
  KEY `vendor_credits_voided_by_foreign` (`voided_by`),
  KEY `vendor_credits_company_id_status_index` (`company_id`,`status`),
  KEY `vendor_credits_company_id_vendor_id_index` (`company_id`,`vendor_id`),
  CONSTRAINT `vendor_credits_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_credits_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_credits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `vendor_credits_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_credits_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_credits_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_credits_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_credits`
--

LOCK TABLES `vendor_credits` WRITE;
/*!40000 ALTER TABLE `vendor_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payment_allocations`
--

DROP TABLE IF EXISTS `vendor_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payment_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_payment_id` bigint unsigned NOT NULL,
  `bill_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_payment_allocations_vendor_payment_id_index` (`vendor_payment_id`),
  KEY `vendor_payment_allocations_bill_id_index` (`bill_id`),
  CONSTRAINT `vendor_payment_allocations_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_payment_allocations_vendor_payment_id_foreign` FOREIGN KEY (`vendor_payment_id`) REFERENCES `vendor_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payment_allocations`
--

LOCK TABLES `vendor_payment_allocations` WRITE;
/*!40000 ALTER TABLE `vendor_payment_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payments`
--

DROP TABLE IF EXISTS `vendor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `payment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `foreign_amount` decimal(18,2) DEFAULT NULL,
  `foreign_currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(18,8) DEFAULT NULL,
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'bank_transfer',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memo` text COLLATE utf8mb4_unicode_ci,
  `bank_account_id` bigint unsigned NOT NULL,
  `journal_entry_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_payments_company_id_payment_number_unique` (`company_id`,`payment_number`),
  KEY `vendor_payments_vendor_id_foreign` (`vendor_id`),
  KEY `vendor_payments_bank_account_id_foreign` (`bank_account_id`),
  KEY `vendor_payments_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `vendor_payments_created_by_foreign` (`created_by`),
  KEY `vendor_payments_company_id_vendor_id_index` (`company_id`,`vendor_id`),
  KEY `vendor_payments_company_id_payment_date_index` (`company_id`,`payment_date`),
  CONSTRAINT `vendor_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `vendor_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `vendor_payments_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_payments_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payments`
--

LOCK TABLES `vendor_payments` WRITE;
/*!40000 ALTER TABLE `vendor_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `remit_to_address` text COLLATE utf8mb4_unicode_ci,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `payment_terms` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'net_30',
  `payment_terms_days` smallint DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `opening_balance_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendors_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `vendors_company_id_name_index` (`company_id`,`name`),
  CONSTRAINT `vendors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'camelot_books'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-23 15:46:59
