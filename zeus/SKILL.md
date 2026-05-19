# Zeus Dashboard — Đặc tả Cơ sở Dữ liệu & SQL

> Tài liệu tập trung duy nhất vào các trường dữ liệu của database zeus_core.sql và toàn bộ câu lệnh SQL
> bao quát mọi tính năng đã thực hiện trong hệ thống Zeus Dashboard.

---

## Mục lục

1. [Database Schema — Bảng & Trường dữ liệu](#1-database-schema--bảng--trường-dữ-liệu)
2. [Quan hệ giữa các Bảng (FK)](#2-quan-hệ-giữa-các-bảng-fk)
3. [Hằng số & Business Logic](#3-hằng-số--business-logic)
4. [SQL Queries — Theo tính năng](#4-sql-queries--theo-tính-năng)

---

## 1. Database Schema — Bảng & Trường dữ liệu

Zeus Core Database (MySQL) chứa ~250+ bảng. Dashboard sử dụng **53 bảng** được liệt kê đầy đủ bên dưới.

### 1.1 Người dùng & Xác thực

#### `tbl_users` — Người dùng (Học viên, Giáo viên, Phụ huynh)
```sql
CREATE TABLE `tbl_users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `icanid_id` varchar(255) DEFAULT NULL,
  `user_first_name` varchar(50) NOT NULL,
  `user_last_name` varchar(50) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_username` varchar(100) DEFAULT NULL,
  `user_password` varchar(100) DEFAULT NULL,
  `user_timezone` varchar(50) DEFAULT NULL,
  `user_gender` tinyint(1) DEFAULT NULL,
  `user_lang_id` int NOT NULL,
  `user_currency_id` int NOT NULL,
  `user_country_id` int NOT NULL DEFAULT '0',
  `user_is_teacher` tinyint(1) DEFAULT NULL,
  `user_is_affiliate` tinyint(1) NOT NULL DEFAULT '0',
  `user_lastseen` datetime DEFAULT NULL,
  `user_featured` tinyint NOT NULL DEFAULT '0',
  `user_offline_sessions` tinyint NOT NULL DEFAULT '0',
  `user_active` tinyint(1) NOT NULL,
  `user_source` varchar(50) DEFAULT 'ZEUS',
  `user_source_domain` varchar(100) DEFAULT 'https://zeus.icanwork.vn',
  `user_verified` datetime DEFAULT NULL,
  `user_created` datetime NOT NULL,
  `user_deleted` datetime DEFAULT NULL,
  `user_password_updated` tinyint DEFAULT NULL,
  `user_must_change_password` tinyint NOT NULL DEFAULT '0',
  `user_referral_code` varchar(255) DEFAULT NULL,
  `user_is_parent` tinyint NOT NULL DEFAULT '0',
  `supplier_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_email` (`user_email`),
  UNIQUE KEY `user_username` (`user_username`),
  UNIQUE KEY `tbl_users_icanid_id_unique` (`icanid_id`),
  KEY `tbl_users_user_source_domain_index` (`user_source_domain`),
  KEY `tbl_users_supplier_id_index` (`supplier_id`),
  FULLTEXT KEY (`user_first_name`),
  FULLTEXT KEY (`user_last_name`)
) ENGINE=InnoDB;
```

#### `tbl_user_settings` — Cài đặt người dùng
```sql
CREATE TABLE `tbl_user_settings` (
  `user_id` int NOT NULL,
  `user_dashboard` tinyint(1) NOT NULL,
  `user_registered_as` tinyint(1) NOT NULL,
  `user_trial_enabled` tinyint(1) DEFAULT NULL,
  `user_trial_only` tinyint NOT NULL DEFAULT '0',
  `user_availability_date` datetime DEFAULT NULL,
  `user_book_before` tinyint(1) DEFAULT NULL,
  `user_phone_code` int DEFAULT NULL,
  `user_phone_number` varchar(20) DEFAULT NULL,
  `user_wallet_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `user_video_link` varchar(255) DEFAULT NULL,
  `user_google_id` varchar(255) DEFAULT NULL,
  `user_facebook_id` varchar(255) DEFAULT NULL,
  `user_apple_id` varchar(255) DEFAULT NULL,
  `user_google_token` longtext,
  `user_google_refresh_token` varchar(255) DEFAULT NULL,
  `user_google_event_sync_token` varchar(255) DEFAULT NULL,
  `user_google_event_sync_date` datetime DEFAULT NULL,
  `user_google_event_watch_id` varchar(255) DEFAULT NULL,
  `user_google_event_watch_resource_id` varchar(255) DEFAULT NULL,
  `user_google_event_watch_expiration` datetime DEFAULT NULL,
  `user_facebook_token` varchar(255) DEFAULT NULL,
  `user_apple_token` varchar(255) DEFAULT NULL,
  `user_device_type` int DEFAULT NULL,
  `user_device_token` varchar(255) DEFAULT NULL,
  `user_slots` longtext,
  `user_zoom_status` tinyint(1) NOT NULL DEFAULT '0',
  `user_autorenew_subscription` tinyint NOT NULL DEFAULT '1',
  `user_reward_points` int NOT NULL DEFAULT '0',
  `user_referral_code` varchar(20) DEFAULT NULL,
  `user_referred_by` int DEFAULT NULL,
  `user_birthday` date DEFAULT NULL,
  `user_birth_precision` enum('year','month','day') NOT NULL DEFAULT 'year',
  `user_region_id` int DEFAULT NULL,
  `user_ms_teams_email` varchar(200) DEFAULT NULL,
  `user_school_name` varchar(255) DEFAULT NULL,
  `user_school_grade` tinyint NOT NULL DEFAULT '-1',
  `user_address_text` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB;
```

#### `tbl_user_extras` — Thông tin mở rộng
```sql
CREATE TABLE `tbl_user_extras` (
  `usrextra_id` int unsigned NOT NULL AUTO_INCREMENT,
  `usrextra_user_id` int NOT NULL,
  `usrextra_css_id` int DEFAULT NULL COMMENT 'admin_id của CSS đang phụ trách user',
  `usrextra_info` longtext,
  `usrextra_user_source_code` varchar(50) DEFAULT NULL,
  `usrextra_user_birth_date` date DEFAULT NULL,
  `usrextra_sale_code` varchar(50) DEFAULT NULL,
  `usrextra_sale_email` varchar(255) DEFAULT NULL,
  `usrextra_sale_notes` text,
  `usrextra_lead_id` varchar(100) DEFAULT NULL,
  `usrextra_contact_phone` varchar(50) DEFAULT NULL,
  `usrextra_contact_dial_code` varchar(10) DEFAULT NULL,
  `usrextra_contact_email` varchar(255) DEFAULT NULL,
  `usrextra_province_id` int DEFAULT NULL,
  `usrextra_contact_name` varchar(255) DEFAULT NULL,
  `usrextra_sale_name` varchar(255) DEFAULT NULL,
  `usrextra_customer_id` int DEFAULT NULL COMMENT 'customer id của AMS',
  `usrextra_created` datetime DEFAULT NULL,
  `usrextra_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`usrextra_id`),
  KEY (`usrextra_sale_code`),
  KEY (`usrextra_user_source_code`),
  KEY (`usrextra_contact_phone`),
  KEY (`usrextra_contact_email`),
  KEY (`usrextra_province_id`),
  KEY (`usrextra_contact_name`),
  KEY (`usrextra_sale_name`),
  KEY (`usrextra_customer_id`),
  KEY `idx_usrextra_css_id` (`usrextra_css_id`)
) ENGINE=InnoDB;
```

#### `tbl_user_auth_token` — Token đăng nhập
```sql
CREATE TABLE `tbl_user_auth_token` (
  `usrtok_id` int NOT NULL AUTO_INCREMENT,
  `usrtok_user_id` int NOT NULL,
  `usrtok_token` varchar(32) NOT NULL,
  `usrtok_expiry` datetime NOT NULL,
  `usrtok_browser` text NOT NULL,
  `usrtok_last_ip` varchar(128) NOT NULL,
  `usrtok_last_access` datetime NOT NULL,
  PRIMARY KEY (`usrtok_id`),
  KEY `usrtok_user_id` (`usrtok_user_id`),
  KEY `usrtok_token` (`usrtok_token`)
) ENGINE=InnoDB;
```

#### `tbl_user_teach_languages` — Môn dạy của giáo viên
```sql
CREATE TABLE `tbl_user_teach_languages` (
  `utlang_id` int NOT NULL AUTO_INCREMENT,
  `utlang_user_id` int NOT NULL,
  `utlang_tlang_id` int NOT NULL,
  `utlang_price` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`utlang_id`),
  UNIQUE KEY `utlang_user_id` (`utlang_user_id`,`utlang_tlang_id`)
) ENGINE=InnoDB;
```

### 1.2 Admin

#### `tbl_admin` — Quản trị viên
```sql
CREATE TABLE `tbl_admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(100) NOT NULL,
  `admin_password` varchar(100) NOT NULL,
  `admin_email` varchar(150) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `admin_timezone` varchar(50) NOT NULL,
  `admin_active` tinyint NOT NULL,
  `admin_password_update` tinyint DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB;
```

#### `tbl_admin_permissions` — Phân quyền admin
```sql
CREATE TABLE `tbl_admin_permissions` (
  `admperm_admin_id` int NOT NULL,
  `admperm_section_id` int NOT NULL,
  `admperm_value` int NOT NULL,
  PRIMARY KEY (`admperm_admin_id`,`admperm_section_id`)
) ENGINE=InnoDB;
```

#### `tbl_admin_roles` — Vai trò admin
```sql
CREATE TABLE `tbl_admin_roles` (
  `admrole_admin_id` bigint unsigned NOT NULL,
  `admrole_role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`admrole_admin_id`,`admrole_role_id`)
) ENGINE=InnoDB;
```

#### `tbl_roles_lang` — Tên vai trò (đa ngôn ngữ)
```sql
CREATE TABLE `tbl_roles_lang` (
  `rolelang_role_id` bigint unsigned NOT NULL,
  `rolelang_lang_id` int unsigned NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `role_description` text,
  PRIMARY KEY (`rolelang_role_id`,`rolelang_lang_id`)
) ENGINE=InnoDB;
```

### 1.3 Đơn hàng & Buổi học

#### `tbl_orders` — Đơn hàng
```sql
CREATE TABLE `tbl_orders` (
  `order_id` bigint NOT NULL AUTO_INCREMENT,
  `order_display_id` varchar(50) DEFAULT NULL,
  `order_type` tinyint NOT NULL,
  `order_user_id` int NOT NULL,
  `order_item_count` int NOT NULL,
  `order_pmethod_id` int DEFAULT NULL,
  `order_pmethod_channel_id` varchar(255) DEFAULT NULL,
  `order_redirect_url` varchar(500) DEFAULT NULL,
  `order_discount_value` decimal(10,2) DEFAULT NULL,
  `order_credit_discount` decimal(6,2) NOT NULL DEFAULT '0.00',
  `order_reward_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `order_currency_code` varchar(10) DEFAULT NULL,
  `order_currency_value` decimal(10,8) DEFAULT NULL,
  `order_payment_status` tinyint NOT NULL,
  `order_status` tinyint NOT NULL,
  `order_total_amount` decimal(20,8) NOT NULL,
  `order_net_amount` decimal(20,8) NOT NULL,
  `order_addedon` datetime NOT NULL,
  `order_related_order_id` bigint DEFAULT NULL,
  `order_extra_data` json DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `order_user_id` (`order_user_id`),
  KEY (`order_pmethod_channel_id`),
  KEY (`order_redirect_url`),
  KEY `idx_orders_status_payment_id` (`order_status`,`order_payment_status`,`order_id`),
  KEY `order_addedon` (`order_addedon`)
) ENGINE=InnoDB;
```

#### `tbl_order_lessons` — Buổi học 1-1 (core table cho hầu hết thống kê)
```sql
CREATE TABLE `tbl_order_lessons` (
  `ordles_id` bigint NOT NULL AUTO_INCREMENT,
  `ordles_type` int NOT NULL,
  `ordles_offline` tinyint NOT NULL DEFAULT '0',
  `ordles_address` text NOT NULL,
  `ordles_order_id` bigint NOT NULL,
  `ordles_ordsplan_id` int DEFAULT NULL,
  `ordles_teacher_id` int NOT NULL,
  `ordles_tlang_id` int DEFAULT NULL,
  `ordles_duration` int NOT NULL,
  `ordles_lesson_starttime` datetime DEFAULT NULL,
  `ordles_lesson_endtime` datetime DEFAULT NULL,
  `ordles_teacher_starttime` datetime DEFAULT NULL,
  `ordles_teacher_endtime` datetime DEFAULT NULL,
  `ordles_student_starttime` datetime DEFAULT NULL,
  `ordles_student_endtime` datetime DEFAULT NULL,
  `ordles_teacher_paid` decimal(10,2) DEFAULT NULL,
  `ordles_commission` decimal(10,2) DEFAULT NULL,
  `ordles_commission_amount` decimal(10,2) NOT NULL,
  `ordles_earnings` decimal(10,2) DEFAULT NULL,
  `ordles_amount` decimal(20,2) NOT NULL,
  `ordles_discount` decimal(10,2) DEFAULT NULL,
  `ordles_credit_discount` decimal(6,2) DEFAULT '0.00',
  `ordles_reward_discount` decimal(10,2) DEFAULT '0.00',
  `ordles_refund` decimal(10,2) DEFAULT NULL,
  `ordles_status` int NOT NULL,
  `ordles_updated` datetime DEFAULT NULL,
  `ordles_reviewed` tinyint NOT NULL,
  `ordles_ended_by` int DEFAULT NULL,
  `ordles_metool_id` int NOT NULL,
  `ordles_affiliate_commission` decimal(10,2) NOT NULL,
  `ordles_beneficiary_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`ordles_id`),
  KEY `ordles_order_id` (`ordles_order_id`),
  KEY (`ordles_beneficiary_id`),
  KEY (`ordles_tlang_id`),
  KEY `ordles_lesson_endtime` (`ordles_lesson_endtime`),
  KEY `ordles_status` (`ordles_status`),
  KEY `ordles_lesson_starttime` (`ordles_lesson_starttime`),
  KEY `ordles_teacher_id` (`ordles_teacher_id`),
  KEY `idx_ordles_availability_check` (`ordles_teacher_id`,`ordles_status`,`ordles_lesson_starttime`,`ordles_lesson_endtime`,`ordles_order_id`),
  KEY `idx_ordles_report_tlang_order` (`ordles_tlang_id`,`ordles_order_id`,`ordles_status`,`ordles_lesson_starttime`,`ordles_updated`)
) ENGINE=InnoDB;
```

> ⚠️ **Quan trọng:** `ordles_beneficiary_id` là **người học thực tế** (beneficiary), không nhất thiết là người mua đơn hàng (`order_user_id`). Phụ huynh mua cho con → `order_user_id` = phụ huynh, `ordles_beneficiary_id` = con.

#### `tbl_order_lessons_extras` — Dữ liệu ClassIn (acceptance code)
```sql
CREATE TABLE `tbl_order_lessons_extras` (
  `ole_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ole_ordles_id` int NOT NULL,
  `ole_acceptance_code` int NOT NULL DEFAULT '0',
  `ole_teacher_first_join` datetime DEFAULT NULL,
  `ole_teacher_last_leave` datetime DEFAULT NULL,
  `ole_student_first_join` datetime DEFAULT NULL,
  `ole_student_last_leave` datetime DEFAULT NULL,
  `ole_overlapped_duration` int NOT NULL DEFAULT '0',
  `ole_processed_lesson_id` int DEFAULT NULL,
  `ole_audio_url` varchar(255) DEFAULT NULL,
  `ole_transcript_url` varchar(255) DEFAULT NULL,
  `ai_feedback_json` longtext,
  `ai_feedback_request_id` varchar(100) DEFAULT NULL,
  `ai_feedback_status` varchar(20) DEFAULT NULL,
  `ai_feedback_retry` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ole_id`),
  KEY (`ole_ordles_id`),
  KEY (`ole_acceptance_code`),
  KEY (`ole_teacher_first_join`),
  KEY (`ole_teacher_last_leave`),
  KEY (`ole_student_first_join`),
  KEY (`ole_student_last_leave`),
  KEY (`ole_processed_lesson_id`),
  KEY `idx_ole_ai_feedback_request_id` (`ai_feedback_request_id`)
) ENGINE=InnoDB;
```

#### `tbl_order_classes` — Đăng ký lớp nhóm
```sql
CREATE TABLE `tbl_order_classes` (
  `ordcls_id` bigint NOT NULL AUTO_INCREMENT,
  `ordcls_type` int NOT NULL,
  `ordcls_order_id` bigint NOT NULL,
  `ordcls_grpcls_id` int NOT NULL,
  `ordcls_address` text NOT NULL,
  `ordcls_starttime` datetime DEFAULT NULL,
  `ordcls_endtime` datetime DEFAULT NULL,
  `ordcls_teacher_paid` decimal(10,2) DEFAULT NULL,
  `ordcls_commission` decimal(10,2) NOT NULL,
  `ordcls_commission_amount` decimal(10,2) NOT NULL,
  `ordcls_earnings` decimal(10,2) DEFAULT NULL,
  `ordcls_amount` decimal(12,2) NOT NULL,
  `ordcls_discount` decimal(10,2) DEFAULT NULL,
  `ordcls_credit_discount` decimal(6,2) DEFAULT '0.00',
  `ordcls_reward_discount` decimal(10,2) DEFAULT '0.00',
  `ordcls_refund` decimal(10,2) DEFAULT NULL,
  `ordcls_status` int NOT NULL,
  `ordcls_reviewed` int NOT NULL,
  `ordcls_updated` datetime DEFAULT NULL,
  `ordcls_ended_by` tinyint DEFAULT NULL,
  `ordcls_affiliate_commission` decimal(10,2) NOT NULL,
  `ordcls_beneficiary_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`ordcls_id`),
  KEY `ordcls_order_id` (`ordcls_order_id`),
  KEY (`ordcls_beneficiary_id`)
) ENGINE=InnoDB;
```

#### `tbl_order_payments` — Thanh toán
```sql
CREATE TABLE `tbl_order_payments` (
  `ordpay_id` bigint NOT NULL AUTO_INCREMENT,
  `ordpay_order_id` bigint NOT NULL,
  `ordpay_amount` decimal(20,8) NOT NULL,
  `ordpay_pmethod_id` int NOT NULL,
  `ordpay_txn_id` varchar(100) NOT NULL,
  `ordpay_status` int NOT NULL,
  `ordpay_response` text NOT NULL,
  `ordpay_extra_data` json DEFAULT NULL,
  `ordpay_datetime` datetime NOT NULL,
  PRIMARY KEY (`ordpay_id`),
  KEY `ordpay_order_id` (`ordpay_order_id`)
) ENGINE=InnoDB;
```

#### `tbl_order_subscription_plans` — Đăng ký gói
```sql
CREATE TABLE `tbl_order_subscription_plans` (
  `ordsplan_id` int NOT NULL AUTO_INCREMENT,
  `ordsplan_user_id` int NOT NULL,
  `ordsplan_plan_id` int NOT NULL,
  `ordsplan_order_id` int NOT NULL,
  `ordsplan_payment` tinyint DEFAULT NULL,
  `ordsplan_validity` int NOT NULL,
  `ordsplan_duration` int NOT NULL,
  `ordsplan_amount` decimal(12,2) DEFAULT NULL,
  `ordsplan_lesson_amount` decimal(10,2) NOT NULL,
  `ordsplan_lessons` int DEFAULT NULL,
  `ordsplan_discount` decimal(10,2) DEFAULT NULL,
  `ordsplan_earnings` decimal(10,2) DEFAULT NULL,
  `ordsplan_reward_discount` decimal(10,2) DEFAULT NULL,
  `ordsplan_used_lesson_count` int NOT NULL,
  `ordsplan_start_date` datetime DEFAULT NULL,
  `ordsplan_end_date` datetime DEFAULT NULL,
  `ordsplan_status` tinyint NOT NULL,
  `ordsplan_created` datetime NOT NULL,
  `ordsplan_updated` datetime DEFAULT NULL,
  `ordsplan_refund` decimal(10,2) DEFAULT NULL,
  `ordsplan_beneficiary_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`ordsplan_id`),
  KEY (`ordsplan_beneficiary_id`)
) ENGINE=InnoDB;
```

### 1.4 Lớp nhóm

#### `tbl_group_classes` — Lớp nhóm
```sql
CREATE TABLE `tbl_group_classes` (
  `grpcls_id` int NOT NULL AUTO_INCREMENT,
  `grpcls_type` int NOT NULL,
  `grpcls_parent` int NOT NULL DEFAULT '0',
  `grpcls_slug` varchar(255) NOT NULL,
  `grpcls_title` varchar(255) NOT NULL,
  `grpcls_description` text NOT NULL,
  `grpcls_teacher_id` int NOT NULL,
  `grpcls_tlang_id` int NOT NULL,
  `grpcls_duration` int NOT NULL,
  `grpcls_start_datetime` datetime NOT NULL,
  `grpcls_end_datetime` datetime NOT NULL,
  `grpcls_teacher_starttime` datetime DEFAULT NULL,
  `grpcls_teacher_endtime` datetime DEFAULT NULL,
  `grpcls_total_seats` int NOT NULL,
  `grpcls_booked_seats` int NOT NULL DEFAULT '0',
  `grpcls_entry_fee` decimal(12,2) NOT NULL,
  `grpcls_status` int NOT NULL,
  `grpcls_metool_id` int NOT NULL DEFAULT '0',
  `grpcls_added_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grpcls_address_id` int DEFAULT NULL,
  `grpcls_offline` tinyint DEFAULT '0',
  PRIMARY KEY (`grpcls_id`),
  KEY `grpcls_parent` (`grpcls_parent`)
) ENGINE=InnoDB;
```

### 1.5 Tài chính

#### `tbl_user_transactions` — Giao dịch ví
```sql
CREATE TABLE `tbl_user_transactions` (
  `usrtxn_id` bigint NOT NULL AUTO_INCREMENT,
  `usrtxn_type` int NOT NULL,
  `usrtxn_user_id` int NOT NULL,
  `usrtxn_amount` decimal(12,2) NOT NULL,
  `usrtxn_datetime` datetime NOT NULL,
  `usrtxn_comment` text NOT NULL,
  PRIMARY KEY (`usrtxn_id`)
) ENGINE=InnoDB;
```

#### `tbl_payment_methods` — Phương thức thanh toán
```sql
CREATE TABLE `tbl_payment_methods` (
  `pmethod_id` int NOT NULL AUTO_INCREMENT,
  `pmethod_type` tinyint NOT NULL,
  `pmethod_code` varchar(100) NOT NULL,
  `pmethod_active` tinyint(1) NOT NULL,
  `pmethod_order` int NOT NULL,
  `pmethod_info` text NOT NULL,
  `pmethod_fees` longtext,
  `pmethod_settings` longtext,
  PRIMARY KEY (`pmethod_id`),
  UNIQUE KEY `pmethod_code` (`pmethod_code`)
) ENGINE=InnoDB;
```

#### `tbl_subscription_plans` — Gói đăng ký
```sql
CREATE TABLE `tbl_subscription_plans` (
  `subplan_id` int NOT NULL AUTO_INCREMENT,
  `subplan_title` varchar(255) NOT NULL,
  `subplan_validity` int NOT NULL,
  `subplan_lesson_duration` int NOT NULL,
  `subplan_lesson_count` int NOT NULL,
  `subplan_price` decimal(12,2) NOT NULL,
  `subplan_active` tinyint NOT NULL,
  `subplan_order` int NOT NULL DEFAULT '0',
  `subplan_created` datetime DEFAULT NULL,
  `subplan_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`subplan_id`)
) ENGINE=InnoDB;
```

### 1.6 Môn học / Ngôn ngữ

#### `tbl_teach_languages` — Môn học/Ngôn ngữ dạy
```sql
CREATE TABLE `tbl_teach_languages` (
  `tlang_id` int NOT NULL AUTO_INCREMENT,
  `tlang_order` int NOT NULL,
  `tlang_parent` int NOT NULL DEFAULT '0',
  `tlang_featured` int DEFAULT NULL,
  `tlang_subcategories` int NOT NULL DEFAULT '0',
  `tlang_level` int NOT NULL DEFAULT '0',
  `tlang_userrecords` int NOT NULL DEFAULT '0',
  `tlang_parentids` text,
  `tlang_active` tinyint(1) NOT NULL,
  `tlang_identifier` varchar(100) NOT NULL,
  `tlang_slug` varchar(255) DEFAULT NULL,
  `tlang_min_price` decimal(12,2) NOT NULL DEFAULT '1.00',
  `tlang_max_price` decimal(12,2) NOT NULL DEFAULT '9999.00',
  `tlang_hourly_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tlang_available` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`tlang_id`),
  UNIQUE KEY `tlang_slug` (`tlang_slug`)
) ENGINE=InnoDB;
```

### 1.7 Quản lý Giáo viên

#### `tbl_teacher_stats` — Thống kê giáo viên
```sql
CREATE TABLE `tbl_teacher_stats` (
  `testat_user_id` int NOT NULL,
  `testat_ratings` decimal(10,2) NOT NULL,
  `testat_reviewes` int NOT NULL,
  `testat_students` int NOT NULL,
  `testat_lessons` int NOT NULL,
  `testat_classes` int NOT NULL,
  `testat_courses` int NOT NULL,
  `testat_minprice` decimal(12,2) NOT NULL,
  `testat_maxprice` decimal(12,2) NOT NULL,
  `testat_preference` int NOT NULL,
  `testat_qualification` int NOT NULL,
  `testat_teachlang` int NOT NULL,
  `testat_speaklang` int NOT NULL,
  `testat_availability` int NOT NULL,
  PRIMARY KEY (`testat_user_id`),
  KEY `testat_minprice` (`testat_minprice`),
  KEY `testat_maxprice` (`testat_maxprice`)
) ENGINE=InnoDB;
```

#### `tbl_teacher_feedbacks` — Nhật ký giáo viên
```sql
CREATE TABLE `tbl_teacher_feedbacks` (
  `teafeed_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teafeed_lang_id` int unsigned DEFAULT NULL,
  `teafeed_teacher_id` bigint unsigned NOT NULL,
  `teafeed_learner_id` bigint unsigned NOT NULL,
  `teafeed_record_id` bigint unsigned NOT NULL,
  `teafeed_record_type` tinyint NOT NULL COMMENT '1=lớp 1:1, 2=lớp nhóm',
  `teafeed_record_status` tinyint NOT NULL DEFAULT '0' COMMENT '0=pending, 1=incomplete, 2=completed',
  `teafeed_type` tinyint NOT NULL DEFAULT '1' COMMENT '1=session feedback, 2=learning recommendation, 3=other',
  `teafeed_form_id` bigint unsigned NOT NULL,
  `teafeed_values` json NOT NULL,
  `teafeed_status` tinyint NOT NULL DEFAULT '0' COMMENT '0=draft, 1=pending, 2=approved, 3=rejected, 4=hidden',
  `teafeed_notify_status` tinyint NOT NULL DEFAULT '0' COMMENT '0=chưa gửi, 1=thành công, 2=lỗi',
  `teafeed_notify_at` timestamp NULL DEFAULT NULL,
  `teafeed_crm` tinyint NOT NULL DEFAULT '0',
  `teafeed_crm_contact` varchar(50) DEFAULT NULL,
  `teafeed_assessment_type` varchar(255) DEFAULT NULL,
  `teafeed_assessment` varchar(50) DEFAULT NULL,
  `teafeed_assessment_result` int NOT NULL DEFAULT '0',
  `teafeed_assessment_detail` text,
  `teafeed_created_at` timestamp NULL DEFAULT NULL,
  `teafeed_updated_at` timestamp NULL DEFAULT NULL,
  `teafeed_ignore_feedback` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`teafeed_id`),
  KEY `idx_teafeed_record` (`teafeed_record_id`,`teafeed_record_type`),
  KEY `idx_teafeed_status` (`teafeed_status`),
  KEY (`teafeed_teacher_id`),
  KEY (`teafeed_learner_id`),
  KEY (`teafeed_form_id`),
  KEY `idx_teafeed_lang_id` (`teafeed_lang_id`),
  KEY `idx_teafeed_notify_at` (`teafeed_notify_at`),
  KEY `idx_teafeed_notify_status_at` (`teafeed_notify_status`,`teafeed_notify_at`)
) ENGINE=InnoDB;
```

#### `tbl_teacher_leave_requests` — Yêu cầu nghỉ phép
```sql
CREATE TABLE `tbl_teacher_leave_requests` (
  `tlr_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tlr_teacher_id` bigint unsigned NOT NULL,
  `tlr_start_date` datetime NOT NULL,
  `tlr_end_date` datetime NOT NULL,
  `tlr_total_days` int unsigned NOT NULL DEFAULT '0',
  `tlr_advance_notice_days` smallint unsigned DEFAULT NULL,
  `tlr_leave_type` smallint unsigned NOT NULL COMMENT '1=Short Leave (≤6d), 2=Long Leave (≥7d)',
  `tlr_reason` text,
  `tlr_status` smallint unsigned NOT NULL DEFAULT '0' COMMENT '0=Draft, 1=Pending, 2=Auto Approved, 3=Approved, 4=Rejected, 5=More Info, 6=Canceled',
  `tlr_is_valid` tinyint unsigned NOT NULL DEFAULT '0',
  `tlr_validation_result` json DEFAULT NULL,
  `tlr_penalty_calculation` json DEFAULT NULL,
  `tlr_reason_type` smallint unsigned NOT NULL DEFAULT '1' COMMENT '1=Personal, 2=Force Majeure',
  `tlr_approved_by` bigint unsigned DEFAULT NULL,
  `tlr_approved_at` datetime DEFAULT NULL,
  `tlr_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tlr_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlr_id`),
  KEY (`tlr_teacher_id`),
  KEY (`tlr_status`),
  KEY (`tlr_start_date`),
  KEY (`tlr_end_date`),
  KEY (`tlr_leave_type`),
  KEY `idx_tlr_teacher_status` (`tlr_teacher_id`,`tlr_status`),
  KEY (`tlr_is_valid`),
  KEY (`tlr_reason_type`)
) ENGINE=InnoDB;
```

#### `tbl_teacher_leave_request_sessions` — Buổi học bị ảnh hưởng
```sql
CREATE TABLE `tbl_teacher_leave_request_sessions` (
  `tlrs_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tlrs_leave_request_id` bigint unsigned NOT NULL,
  `tlrs_session_id` bigint unsigned NOT NULL,
  `tlrs_session_type` smallint unsigned NOT NULL COMMENT '1=Lesson (1-1), 2=Class (1-n)',
  `tlrs_session_date` date NOT NULL,
  `tlrs_need_replacement` tinyint unsigned NOT NULL DEFAULT '0',
  `tlrs_session_info` json DEFAULT NULL,
  `tlrs_replacement_type` smallint unsigned DEFAULT NULL COMMENT '1=Substitute, 2=Replace',
  `tlrs_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlrs_id`),
  KEY (`tlrs_leave_request_id`),
  KEY (`tlrs_session_id`),
  KEY (`tlrs_session_type`),
  KEY (`tlrs_session_date`),
  KEY `idx_tlrs_unique` (`tlrs_leave_request_id`,`tlrs_session_id`,`tlrs_session_type`)
) ENGINE=InnoDB;
```

#### `tbl_teacher_leave_quotas` — Hạn mức nghỉ phép
```sql
CREATE TABLE `tbl_teacher_leave_quotas` (
  `tlq_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tlq_teacher_id` bigint unsigned NOT NULL,
  `tlq_year` smallint unsigned NOT NULL,
  `tlq_quarter` smallint unsigned DEFAULT NULL,
  `tlq_month` smallint unsigned DEFAULT NULL,
  `tlq_join_date` date NOT NULL,
  `tlq_months_worked` smallint unsigned NOT NULL DEFAULT '0',
  `tlq_base_quota` int unsigned NOT NULL DEFAULT '0',
  `tlq_used_days` int unsigned NOT NULL DEFAULT '0',
  `tlq_used_this_month` int unsigned NOT NULL DEFAULT '0',
  `tlq_used_this_quarter` int unsigned NOT NULL DEFAULT '0',
  `tlq_used_this_year` int unsigned NOT NULL DEFAULT '0',
  `tlq_remaining_quota` int unsigned NOT NULL DEFAULT '0',
  `tlq_remaining_monthly` int unsigned NOT NULL DEFAULT '0',
  `tlq_remaining_quarterly` int unsigned NOT NULL DEFAULT '0',
  `tlq_quarterly_limit` int unsigned NOT NULL DEFAULT '12',
  `tlq_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tlq_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlq_id`),
  KEY (`tlq_teacher_id`),
  KEY (`tlq_year`),
  KEY (`tlq_quarter`),
  KEY (`tlq_month`),
  KEY `idx_tlq_teacher_year` (`tlq_teacher_id`,`tlq_year`),
  KEY `idx_tlq_teacher_year_quarter` (`tlq_teacher_id`,`tlq_year`,`tlq_quarter`),
  KEY `idx_tlq_teacher_year_month` (`tlq_teacher_id`,`tlq_year`,`tlq_month`)
) ENGINE=InnoDB;
```

#### `tbl_teacher_leave_violations` — Vi phạm nghỉ phép
```sql
CREATE TABLE `tbl_teacher_leave_violations` (
  `tlv_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tlv_teacher_id` bigint unsigned NOT NULL,
  `tlv_leave_request_id` bigint unsigned DEFAULT NULL,
  `tlv_violation_type` smallint unsigned NOT NULL COMMENT '1=Quota, 2=Deadline, 3=No-show, 4=Class Impact',
  `tlv_violation_level` smallint unsigned NOT NULL DEFAULT '1',
  `tlv_exceeded_days` int unsigned NOT NULL DEFAULT '0',
  `tlv_penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tlv_penalty_percentage` tinyint unsigned NOT NULL DEFAULT '0',
  `tlv_affected_sessions` int unsigned NOT NULL DEFAULT '0',
  `tlv_affected_students` int unsigned NOT NULL DEFAULT '0',
  `tlv_affected_classes` json DEFAULT NULL,
  `tlv_description` text,
  `tlv_status` smallint unsigned NOT NULL DEFAULT '1' COMMENT '1=Pending, 2=Processed, 3=Cancelled',
  `tlv_processed_by` bigint unsigned DEFAULT NULL,
  `tlv_processed_at` datetime DEFAULT NULL,
  `tlv_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tlv_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlv_id`),
  KEY (`tlv_teacher_id`),
  KEY (`tlv_leave_request_id`),
  KEY (`tlv_violation_type`),
  KEY (`tlv_violation_level`),
  KEY (`tlv_status`),
  KEY (`tlv_created_at`),
  KEY `idx_tlv_teacher_type` (`tlv_teacher_id`,`tlv_violation_type`)
) ENGINE=InnoDB;
```

#### `tbl_availability` — Lịch rảnh giáo viên
```sql
CREATE TABLE `tbl_availability` (
  `avail_id` int NOT NULL AUTO_INCREMENT,
  `avail_user_id` int NOT NULL,
  `avail_starttime` datetime NOT NULL,
  `avail_endtime` datetime NOT NULL,
  PRIMARY KEY (`avail_id`),
  KEY `avail_user_id` (`avail_user_id`),
  KEY `idx_time_range_optimized` (`avail_starttime`,`avail_endtime`,`avail_user_id`)
) ENGINE=InnoDB;
```

### 1.8 Feedback, Đánh giá & Logs

#### `tbl_rating_reviews` — Đánh giá giáo viên
```sql
CREATE TABLE `tbl_rating_reviews` (
  `ratrev_id` int NOT NULL AUTO_INCREMENT,
  `ratrev_type` int NOT NULL,
  `ratrev_type_id` int NOT NULL,
  `ratrev_lang_id` int NOT NULL,
  `ratrev_user_id` int NOT NULL,
  `ratrev_teacher_id` int NOT NULL,
  `ratrev_overall` float(4,1) DEFAULT NULL,
  `ratrev_title` varchar(255) DEFAULT NULL,
  `ratrev_detail` text,
  `ratrev_status` tinyint NOT NULL,
  `ratrev_teacher_notify` tinyint(1) NOT NULL,
  `ratrev_created` datetime NOT NULL,
  PRIMARY KEY (`ratrev_id`)
) ENGINE=InnoDB;
```

#### `tbl_reported_issues` — Báo cáo vấn đề
```sql
CREATE TABLE `tbl_reported_issues` (
  `repiss_id` int NOT NULL AUTO_INCREMENT,
  `repiss_title` varchar(255) NOT NULL,
  `repiss_record_id` int NOT NULL,
  `repiss_record_type` int NOT NULL,
  `repiss_reported_on` datetime NOT NULL,
  `repiss_reported_by` int NOT NULL,
  `repiss_status` int NOT NULL,
  `repiss_last_action` tinyint DEFAULT NULL,
  `repiss_comment` text NOT NULL,
  `repiss_updated_on` datetime DEFAULT NULL,
  `repiss_reported_to` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`repiss_id`)
) ENGINE=InnoDB;
```

#### `tbl_lesson_notes` — Ghi chú buổi học
```sql
CREATE TABLE `tbl_lesson_notes` (
  `lesnote_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lesnote_ordles_id` bigint unsigned NOT NULL,
  `lesnote_user_id` bigint unsigned NOT NULL,
  `lesnote_content` text NOT NULL,
  `lesnote_is_published` tinyint NOT NULL DEFAULT '0',
  `lesnote_created_at` timestamp NULL DEFAULT NULL,
  `lesnote_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`lesnote_id`),
  KEY `idx_lesnote_lesson_user` (`lesnote_ordles_id`,`lesnote_user_id`),
  KEY (`lesnote_is_published`),
  KEY (`lesnote_ordles_id`),
  KEY (`lesnote_user_id`)
) ENGINE=InnoDB;
```

#### `tbl_session_logs` — Log thay đổi trạng thái buổi học
```sql
CREATE TABLE `tbl_session_logs` (
  `sesslog_id` int NOT NULL AUTO_INCREMENT,
  `sesslog_record_id` int NOT NULL,
  `sesslog_record_type` int NOT NULL,
  `sesslog_user_id` int NOT NULL,
  `sesslog_user_type` int NOT NULL,
  `sesslog_prev_status` int NOT NULL,
  `sesslog_changed_status` int NOT NULL,
  `sesslog_prev_starttime` datetime DEFAULT NULL,
  `sesslog_prev_endtime` datetime DEFAULT NULL,
  `sesslog_changed_starttime` datetime DEFAULT NULL,
  `sesslog_changed_endtime` datetime DEFAULT NULL,
  `sesslog_comment` text NOT NULL,
  `sesslog_created` datetime NOT NULL,
  `sesslog_admin_id` int NOT NULL DEFAULT '0',
  `sesslog_prev_tlang_id` int NOT NULL DEFAULT '0',
  `sesslog_changed_tlang_id` int NOT NULL DEFAULT '0',
  `sesslog_prev_teacher_id` int NOT NULL DEFAULT '0',
  `sesslog_changed_teacher_id` int NOT NULL DEFAULT '0',
  `sesslog_action_code` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`sesslog_id`),
  KEY (`sesslog_record_id`),
  KEY (`sesslog_user_id`),
  KEY (`sesslog_record_type`),
  KEY (`sesslog_user_type`),
  KEY (`sesslog_changed_status`),
  KEY (`sesslog_prev_status`),
  KEY (`sesslog_created`),
  KEY `idx_sesslog_record_type_id` (`sesslog_record_type`,`sesslog_record_id`),
  KEY (`sesslog_prev_tlang_id`),
  KEY (`sesslog_changed_tlang_id`),
  KEY (`sesslog_prev_teacher_id`),
  KEY (`sesslog_changed_teacher_id`),
  KEY (`sesslog_action_code`)
) ENGINE=InnoDB;
```

#### `tbl_sales_lead_logs` — Nhật ký đăng nhập
```sql
CREATE TABLE `tbl_sales_lead_logs` (
  `sllg_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sllg_actor_type` varchar(50) NOT NULL,
  `sllg_actor_id` varchar(50) NOT NULL,
  `sllg_user_id` bigint unsigned DEFAULT NULL,
  `sllg_action_type` varchar(50) NOT NULL,
  `sllg_action` varchar(50) NOT NULL,
  `sllg_metadata` json DEFAULT NULL,
  `sllg_occurred_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sllg_source` varchar(50) DEFAULT NULL,
  `sllg_status` tinyint NOT NULL DEFAULT '0' COMMENT '0=pending,1=processed,2=error',
  `sllg_crm_type` tinyint NOT NULL DEFAULT '1',
  `sllg_crm_contact_id` varchar(100) DEFAULT NULL,
  `sllg_crm_request` json DEFAULT NULL,
  `sllg_crm_response` json DEFAULT NULL,
  `sllg_crm_synced` tinyint NOT NULL DEFAULT '0',
  `sllg_crm_synced_at` datetime DEFAULT NULL,
  `sllg_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sllg_updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sllg_id`),
  KEY `idx_actor` (`sllg_actor_type`,`sllg_actor_id`),
  KEY (`sllg_user_id`),
  KEY `idx_action_type_action` (`sllg_action_type`,`sllg_action`),
  KEY (`sllg_occurred_at`),
  KEY (`sllg_crm_type`),
  KEY (`sllg_status`),
  KEY (`sllg_created_at`),
  KEY `idx_status_synced` (`sllg_status`,`sllg_crm_synced`),
  KEY `idx_user_action` (`sllg_user_id`,`sllg_action`),
  KEY `idx_crm_type_contact` (`sllg_crm_type`,`sllg_crm_contact_id`)
) ENGINE=InnoDB;
```

### 1.9 Chương trình & Giáo trình

#### `tbl_program` — Chương trình học
```sql
CREATE TABLE `tbl_program` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `type_item_id` varchar(64) NOT NULL DEFAULT '',
  `tag` varchar(255) NOT NULL DEFAULT '',
  `parent` int NOT NULL DEFAULT '0',
  `after` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(32) NOT NULL DEFAULT '',
  `video` varchar(2048) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY (`type`),
  KEY (`type_item_id`),
  KEY (`parent`),
  KEY (`after`),
  FULLTEXT KEY (`title`),
  FULLTEXT KEY (`tag`)
) ENGINE=InnoDB;
```

#### `tbl_program_curriculum` — Liên kết chương trình - giáo trình
```sql
CREATE TABLE `tbl_program_curriculum` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL DEFAULT '0',
  `curriculum_id` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY (`program_id`,`curriculum_id`)
) ENGINE=InnoDB;
```

#### `tbl_program_user` — Đăng ký chương trình
```sql
CREATE TABLE `tbl_program_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `teacher_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`program_id`,`user_id`),
  KEY (`teacher_id`)
) ENGINE=InnoDB;
```

#### `tbl_curriculum` — Giáo trình
```sql
CREATE TABLE `tbl_curriculum` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `lang_id` int NOT NULL DEFAULT '0',
  `status` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FULLTEXT KEY (`title`)
) ENGINE=InnoDB;
```

#### `tbl_curriculum_section` — Phần giáo trình
```sql
CREATE TABLE `tbl_curriculum_section` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `after` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```

#### `tbl_curriculum_lecture` — Bài giảng
```sql
CREATE TABLE `tbl_curriculum_lecture` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` int unsigned NOT NULL DEFAULT '0',
  `curriculum_section_id` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `after` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `weight` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
```

#### `tbl_curriculum_session` — Buổi giáo trình
```sql
CREATE TABLE `tbl_curriculum_session` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_lecture_id` int NOT NULL DEFAULT '0',
  `session_type` varchar(255) NOT NULL DEFAULT '',
  `session_item_id` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(32) NOT NULL DEFAULT '',
  `clazz_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY (`curriculum_lecture_id`),
  KEY (`session_type`),
  KEY (`session_item_id`),
  KEY (`status`),
  KEY (`clazz_id`)
) ENGINE=InnoDB;
```

### 1.10 Quiz

#### `tbl_quizzes` — Bài kiểm tra
```sql
CREATE TABLE `tbl_quizzes` (
  `quiz_id` int NOT NULL AUTO_INCREMENT,
  `quiz_type` tinyint NOT NULL,
  `quiz_title` varchar(120) NOT NULL,
  `quiz_detail` text,
  `quiz_user_id` int NOT NULL,
  `quiz_duration` bigint DEFAULT NULL COMMENT 'In Seconds',
  `quiz_attempts` tinyint DEFAULT NULL,
  `quiz_marks` decimal(8,2) NOT NULL,
  `quiz_passmark` decimal(8,2) DEFAULT NULL COMMENT 'In percent',
  `quiz_validity` int NOT NULL,
  `quiz_certificate` tinyint(1) NOT NULL,
  `quiz_questions` int NOT NULL,
  `quiz_failmsg` varchar(255) NOT NULL,
  `quiz_passmsg` varchar(255) NOT NULL,
  `quiz_active` tinyint(1) NOT NULL,
  `quiz_status` tinyint NOT NULL,
  `quiz_created` datetime NOT NULL,
  `quiz_updated` datetime DEFAULT NULL,
  `quiz_deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`quiz_id`)
) ENGINE=InnoDB;
```

#### `tbl_quiz_attempts` — Lần làm bài
```sql
CREATE TABLE `tbl_quiz_attempts` (
  `quizat_id` int NOT NULL AUTO_INCREMENT,
  `quizat_quilin_id` int NOT NULL,
  `quizat_user_id` int NOT NULL,
  `quizat_scored` decimal(8,2) DEFAULT NULL,
  `quizat_marks` decimal(8,2) NOT NULL,
  `quizat_progress` decimal(8,2) NOT NULL,
  `quizat_qulinqu_id` int NOT NULL,
  `quizat_evaluation` tinyint(1) NOT NULL,
  `quizat_certificate_number` varchar(25) NOT NULL,
  `quizat_status` tinyint NOT NULL,
  `quizat_active` tinyint(1) NOT NULL,
  `quizat_started` datetime NOT NULL,
  `quizat_created` datetime NOT NULL,
  `quizat_updated` datetime NOT NULL,
  `quizat_count` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`quizat_id`)
) ENGINE=InnoDB;
```

### 1.11 Voucher & Coupon

#### `tbl_coupons` — Mã giảm giá
```sql
CREATE TABLE `tbl_coupons` (
  `coupon_id` int NOT NULL AUTO_INCREMENT,
  `coupon_identifier` varchar(200) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `coupon_min_order` decimal(12,2) NOT NULL,
  `coupon_max_discount` decimal(12,2) NOT NULL,
  `coupon_discount_type` int NOT NULL,
  `coupon_discount_value` decimal(12,2) DEFAULT NULL,
  `coupon_max_uses` int NOT NULL,
  `coupon_user_uses` int NOT NULL,
  `coupon_used_uses` int NOT NULL,
  `coupon_start_date` datetime NOT NULL,
  `coupon_end_date` datetime NOT NULL,
  `coupon_active` tinyint(1) NOT NULL,
  `coupon_apply_on` tinyint NOT NULL DEFAULT '1' COMMENT '1=Order Total, 2=Remaining After Wallet',
  `coupon_created` datetime NOT NULL,
  `coupon_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `coupon_code` (`coupon_code`),
  UNIQUE KEY `coupon_identifier` (`coupon_identifier`)
) ENGINE=InnoDB;
```

#### `tbl_coupons_history` — Lịch sử sử dụng coupon
```sql
CREATE TABLE `tbl_coupons_history` (
  `couhis_id` int NOT NULL AUTO_INCREMENT,
  `couhis_order_id` int NOT NULL,
  `couhis_coupon_id` int NOT NULL,
  `couhis_coupon` longtext,
  `couhis_created` datetime NOT NULL,
  `couhis_released` datetime DEFAULT NULL,
  PRIMARY KEY (`couhis_id`),
  UNIQUE KEY (`couhis_order_id`,`couhis_coupon_id`)
) ENGINE=InnoDB;
```

#### `tbl_coupon_logs` — Log chi tiết coupon
```sql
CREATE TABLE `tbl_coupon_logs` (
  `clog_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clog_coupon_id` bigint unsigned NOT NULL,
  `clog_action` tinyint NOT NULL,
  `clog_actor_type` enum('user','admin','system') NOT NULL DEFAULT 'system',
  `clog_actor_id` bigint unsigned DEFAULT NULL,
  `clog_beneficiary_id` bigint unsigned DEFAULT NULL,
  `clog_related_object_id` bigint unsigned DEFAULT NULL,
  `clog_related_object_type` smallint unsigned DEFAULT NULL,
  `clog_note` text,
  `clog_details` json DEFAULT NULL,
  `clog_created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`clog_id`),
  KEY (`clog_coupon_id`),
  KEY `idx_coupon_action` (`clog_coupon_id`,`clog_action`),
  KEY `idx_coupon_beneficiary` (`clog_coupon_id`,`clog_beneficiary_id`),
  KEY `idx_coupon_related_object` (`clog_coupon_id`,`clog_related_object_id`,`clog_related_object_type`),
  KEY (`clog_action`),
  KEY (`clog_actor_id`),
  KEY (`clog_beneficiary_id`)
) ENGINE=InnoDB;
```

#### `tbl_zcoupon_transactions` — Giao dịch Z-Coupon
```sql
CREATE TABLE `tbl_zcoupon_transactions` (
  `zctran_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zctran_usrtxn_id` bigint unsigned NOT NULL DEFAULT '0',
  `zctran_coupon_id` bigint unsigned NOT NULL,
  `zctran_user_id` int unsigned NOT NULL,
  `zctran_tenant_id` int unsigned NOT NULL DEFAULT '1',
  `zctran_action` smallint unsigned NOT NULL COMMENT '1=created, 2=used, 3=expired',
  `zctran_value_change` decimal(20,8) NOT NULL DEFAULT '0.00000000',
  `zctran_related_object_type` smallint unsigned NOT NULL,
  `zctran_related_object_field` varchar(50) DEFAULT NULL,
  `zctran_related_object_id` bigint unsigned NOT NULL DEFAULT '0',
  `zctran_related_extra` text,
  `zctran_comment` text,
  `zctran_created` datetime NOT NULL,
  PRIMARY KEY (`zctran_id`),
  KEY (`zctran_coupon_id`),
  KEY (`zctran_user_id`),
  KEY `zctran_related_object_type_id` (`zctran_related_object_type`,`zctran_related_object_id`),
  KEY `idx_zctran_usrtxn_id` (`zctran_usrtxn_id`)
) ENGINE=InnoDB;
```

### 1.12 LCMS (Learning Content Management System)

#### `lcms_courses` — Học liệu (cây phân cấp)
```sql
CREATE TABLE `lcms_courses` (
  `cou_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cou_visible` tinyint(1) DEFAULT '1',
  `cou_name` varchar(255) DEFAULT NULL,
  `cou_type` varchar(255) DEFAULT NULL,
  `cou_parent_id` bigint DEFAULT NULL,
  `cou_total_round` int DEFAULT NULL,
  `cou_last_round` int DEFAULT NULL,
  `cou_section_type` int DEFAULT NULL,
  `cou_quiz_setting_type` bigint DEFAULT NULL,
  `cou_type_id` bigint DEFAULT NULL,
  `cou_is_sync` int NOT NULL DEFAULT '0',
  `cou_created_at` timestamp NULL DEFAULT NULL,
  `cou_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cou_id`),
  KEY (`cou_type`),
  KEY (`cou_parent_id`),
  KEY (`cou_type_id`),
  KEY (`cou_is_sync`)
) ENGINE=InnoDB;
```

#### `lcms_students` — Học sinh LCMS
```sql
CREATE TABLE `lcms_students` (
  `stu_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stu_name` varchar(255) DEFAULT NULL,
  `stu_email` varchar(255) DEFAULT NULL,
  `stu_username` varchar(255) DEFAULT NULL,
  `stu_user_id` varchar(255) DEFAULT NULL COMMENT 'Zeus user_id',
  `stu_phone` varchar(255) DEFAULT NULL,
  `stu_dob` varchar(255) DEFAULT NULL,
  `stu_gender` varchar(255) DEFAULT NULL,
  `stu_is_sync` int NOT NULL DEFAULT '0',
  `stu_created_at` timestamp NULL DEFAULT NULL,
  `stu_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`stu_id`),
  KEY (`stu_email`),
  KEY (`stu_username`),
  KEY (`stu_user_id`),
  KEY (`stu_is_sync`)
) ENGINE=InnoDB;
```

#### `lcms_student_scores` — Điểm bài kiểm tra LCMS
```sql
CREATE TABLE `lcms_student_scores` (
  `stusco_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stusco_student_id` bigint NOT NULL,
  `stusco_user_id` varchar(255) DEFAULT NULL COMMENT 'Zeus user_id',
  `stusco_course_id` bigint NOT NULL,
  `stusco_mock_contest_id` varchar(255) DEFAULT NULL,
  `stusco_score_status` int DEFAULT NULL,
  `stusco_history_contest_id` varchar(255) DEFAULT NULL,
  `stusco_overall_score` varchar(255) DEFAULT NULL,
  `stusco_is_sync` int NOT NULL DEFAULT '0',
  `stusco_created_at` timestamp NULL DEFAULT NULL,
  `stusco_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`stusco_id`),
  KEY (`stusco_student_id`),
  KEY (`stusco_course_id`),
  KEY (`stusco_mock_contest_id`),
  KEY (`stusco_history_contest_id`),
  KEY (`stusco_is_sync`)
) ENGINE=InnoDB;
```

#### `lcms_user_assignments` — Tiến độ học (section-level)
```sql
CREATE TABLE `lcms_user_assignments` (
  `usrasi_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usrasi_course_id` bigint NOT NULL,
  `usrasi_section_id` bigint NOT NULL,
  `usrasi_zeus_id` varchar(255) DEFAULT NULL,
  `usrasi_student_id` bigint DEFAULT NULL,
  `usrasi_completion_time` timestamp NULL DEFAULT NULL,
  `usrasi_completion_state` int DEFAULT NULL,
  `usrasi_payload_data` json DEFAULT NULL,
  `usrasi_response_data` json DEFAULT NULL,
  `usrasi_is_cron` tinyint(1) NOT NULL DEFAULT '0',
  `usrasi_is_sync` int NOT NULL DEFAULT '0',
  `usrasi_created_at` timestamp NULL DEFAULT NULL,
  `usrasi_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`usrasi_id`),
  KEY (`usrasi_course_id`),
  KEY (`usrasi_section_id`),
  KEY (`usrasi_zeus_id`),
  KEY (`usrasi_student_id`),
  KEY (`usrasi_is_sync`)
) ENGINE=InnoDB;
```

#### `lcms_course_student` — Đăng ký khoá học LCMS
```sql
CREATE TABLE `lcms_course_student` (
  `coustu_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coustu_student_id` bigint NOT NULL,
  `coustu_user_id` varchar(255) DEFAULT NULL COMMENT 'Zeus user_id',
  `coustu_course_id` bigint NOT NULL,
  `coustu_course_end` varchar(255) DEFAULT NULL,
  `coustu_is_sync` int NOT NULL DEFAULT '0',
  `coustu_created_at` timestamp NULL DEFAULT NULL,
  `coustu_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`coustu_id`),
  KEY (`coustu_student_id`),
  KEY (`coustu_course_id`),
  KEY (`coustu_is_sync`)
) ENGINE=InnoDB;
```

### 1.13 Cấu hình & Hỗ trợ

#### `tbl_configurations` — Cấu hình động
```sql
CREATE TABLE `tbl_configurations` (
  `conf_name` varchar(50) NOT NULL,
  `conf_val` longtext NOT NULL,
  `conf_common` tinyint NOT NULL,
  PRIMARY KEY (`conf_name`)
) ENGINE=InnoDB;
```

#### `tbl_countries` — Quốc gia
```sql
CREATE TABLE `tbl_countries` (
  `country_id` int unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) NOT NULL,
  `country_identifier` varchar(255) NOT NULL,
  `country_dial_code` varchar(10) NOT NULL,
  `country_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`country_id`),
  UNIQUE KEY `country_code` (`country_code`),
  UNIQUE KEY `country_identifier` (`country_identifier`)
) ENGINE=InnoDB;
```

#### `tbl_countries_lang` — Tên quốc gia (đa ngôn ngữ)
```sql
CREATE TABLE `tbl_countries_lang` (
  `countrylang_country_id` int NOT NULL,
  `countrylang_lang_id` int NOT NULL,
  `country_name` varchar(100) NOT NULL,
  PRIMARY KEY (`countrylang_country_id`,`countrylang_lang_id`),
  UNIQUE KEY (`countrylang_lang_id`,`country_name`)
) ENGINE=InnoDB;
```

---

## 2. Quan hệ giữa các Bảng (FK)

Các FK là implicit (không enforce bằng constraint), được sử dụng qua JOIN trong SQL:

| Bảng nguồn | Cột | → Bảng đích | Cột |
|---|---|---|---|
| `tbl_orders` | `order_user_id` | → `tbl_users` | `user_id` |
| `tbl_order_lessons` | `ordles_order_id` | → `tbl_orders` | `order_id` |
| `tbl_order_lessons` | `ordles_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_order_lessons` | `ordles_beneficiary_id` | → `tbl_users` | `user_id` |
| `tbl_order_lessons` | `ordles_tlang_id` | → `tbl_teach_languages` | `tlang_id` |
| `tbl_order_lessons_extras` | `ole_ordles_id` | → `tbl_order_lessons` | `ordles_id` |
| `tbl_order_classes` | `ordcls_order_id` | → `tbl_orders` | `order_id` |
| `tbl_order_classes` | `ordcls_grpcls_id` | → `tbl_group_classes` | `grpcls_id` |
| `tbl_order_classes` | `ordcls_beneficiary_id` | → `tbl_users` | `user_id` |
| `tbl_order_payments` | `ordpay_order_id` | → `tbl_orders` | `order_id` |
| `tbl_order_payments` | `ordpay_pmethod_id` | → `tbl_payment_methods` | `pmethod_id` |
| `tbl_order_subscription_plans` | `ordsplan_order_id` | → `tbl_orders` | `order_id` |
| `tbl_order_subscription_plans` | `ordsplan_plan_id` | → `tbl_subscription_plans` | `subplan_id` |
| `tbl_order_subscription_plans` | `ordsplan_beneficiary_id` | → `tbl_users` | `user_id` |
| `tbl_group_classes` | `grpcls_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_group_classes` | `grpcls_tlang_id` | → `tbl_teach_languages` | `tlang_id` |
| `tbl_user_settings` | `user_id` | → `tbl_users` | `user_id` |
| `tbl_user_extras` | `usrextra_user_id` | → `tbl_users` | `user_id` |
| `tbl_user_extras` | `usrextra_css_id` | → `tbl_admin` | `admin_id` |
| `tbl_user_auth_token` | `usrtok_user_id` | → `tbl_users` | `user_id` |
| `tbl_user_teach_languages` | `utlang_user_id` | → `tbl_users` | `user_id` |
| `tbl_user_teach_languages` | `utlang_tlang_id` | → `tbl_teach_languages` | `tlang_id` |
| `tbl_user_transactions` | `usrtxn_user_id` | → `tbl_users` | `user_id` |
| `tbl_users` | `user_country_id` | → `tbl_countries` | `country_id` |
| `tbl_teacher_stats` | `testat_user_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_feedbacks` | `teafeed_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_feedbacks` | `teafeed_learner_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_feedbacks` | `teafeed_record_id` | → `tbl_order_lessons` | `ordles_id` |
| `tbl_teacher_leave_requests` | `tlr_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_leave_request_sessions` | `tlrs_leave_request_id` | → `tbl_teacher_leave_requests` | `tlr_id` |
| `tbl_teacher_leave_request_sessions` | `tlrs_session_id` | → `tbl_order_lessons` | `ordles_id` |
| `tbl_teacher_leave_quotas` | `tlq_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_leave_violations` | `tlv_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_teacher_leave_violations` | `tlv_leave_request_id` | → `tbl_teacher_leave_requests` | `tlr_id` |
| `tbl_availability` | `avail_user_id` | → `tbl_users` | `user_id` |
| `tbl_rating_reviews` | `ratrev_user_id` | → `tbl_users` | `user_id` |
| `tbl_rating_reviews` | `ratrev_teacher_id` | → `tbl_users` | `user_id` |
| `tbl_reported_issues` | `repiss_reported_by` | → `tbl_users` | `user_id` |
| `tbl_lesson_notes` | `lesnote_ordles_id` | → `tbl_order_lessons` | `ordles_id` |
| `tbl_lesson_notes` | `lesnote_user_id` | → `tbl_users` | `user_id` |
| `tbl_session_logs` | `sesslog_record_id` | → `tbl_order_lessons.ordles_id` (khi `record_type=1`) hoặc `tbl_group_classes.grpcls_id` (khi `record_type=2`) |
| `tbl_admin_permissions` | `admperm_admin_id` | → `tbl_admin` | `admin_id` |
| `tbl_admin_roles` | `admrole_admin_id` | → `tbl_admin` | `admin_id` |
| `tbl_program_curriculum` | `program_id` | → `tbl_program` | `id` |
| `tbl_program_curriculum` | `curriculum_id` | → `tbl_curriculum` | `id` |
| `tbl_program_user` | `program_id` | → `tbl_program` | `id` |
| `tbl_program_user` | `user_id` | → `tbl_users` | `user_id` |
| `tbl_curriculum_section` | `curriculum_id` | → `tbl_curriculum` | `id` |
| `tbl_curriculum_lecture` | `curriculum_id` | → `tbl_curriculum` | `id` |
| `tbl_curriculum_lecture` | `curriculum_section_id` | → `tbl_curriculum_section` | `id` |
| `tbl_curriculum_session` | `curriculum_lecture_id` | → `tbl_curriculum_lecture` | `id` |
| `tbl_coupons_history` | `couhis_order_id` | → `tbl_orders` | `order_id` |
| `tbl_coupons_history` | `couhis_coupon_id` | → `tbl_coupons` | `coupon_id` |
| `tbl_coupon_logs` | `clog_coupon_id` | → `tbl_coupons` | `coupon_id` |
| `tbl_zcoupon_transactions` | `zctran_coupon_id` | → `tbl_coupons` | `coupon_id` |
| `tbl_zcoupon_transactions` | `zctran_user_id` | → `tbl_users` | `user_id` |
| `tbl_zcoupon_transactions` | `zctran_usrtxn_id` | → `tbl_user_transactions` | `usrtxn_id` |
| `tbl_countries_lang` | `countrylang_country_id` | → `tbl_countries` | `country_id` |
| `lcms_course_student` | `coustu_student_id` | → `lcms_students` | `stu_id` |
| `lcms_course_student` | `coustu_course_id` | → `lcms_courses` | `cou_id` |
| `lcms_student_scores` | `stusco_student_id` | → `lcms_students` | `stu_id` |
| `lcms_student_scores` | `stusco_course_id` | → `lcms_courses` | `cou_id` |
| `lcms_user_assignments` | `usrasi_student_id` | → `lcms_students` | `stu_id` |
| `lcms_user_assignments` | `usrasi_section_id` | → `lcms_courses` | `cou_id` |

---

## 3. Hằng số & Business Logic

### 3.1 Trạng thái buổi học (ordles_status)
```
1 = Unscheduled (Chưa xếp lịch)
2 = Scheduled   (Đã xếp lịch)
3 = Completed   (Hoàn thành)
4 = Cancelled   (Đã huỷ)
```

### 3.2 Loại buổi học (ordles_type)
```
1 = Trial   (Học thử)
2 = Regular (Học chính thức)
```

### 3.3 Loại đơn hàng (order_type)
```
1  = Lesson (buổi học riêng)
2  = Subscription
3  = Group Class
4  = Package
5  = Course
6  = Wallet (nạp ví)
7  = Gift Card
18 = Subscription Plan
20 = Z-Coupon
21 = Reservation
```

### 3.4 Trạng thái đơn hàng
```
order_status:          1=InProcess, 2=Completed, 3=Cancelled
order_payment_status:  0=Unpaid, 1=Paid
```

### 3.5 Acceptance Code (ClassIn — bảng 3×4: GV hàng × HV cột)

| Code | GV | HV | Mô tả |
|---|---|---|---|
| 0 | — | — | Chưa đánh giá |
| 1 | No show | No show | Cả hai không có mặt |
| 2 | No show | < 1/2 thời lượng | GV vắng, HV đến nhưng ko đủ |
| 3 | No show | Bình thường | GV vắng, HV bình thường |
| 4 | < 1/2 | No show | GV không đủ, HV vắng |
| 5 | < 1/2 | < 1/2 | Cả hai không đủ thời lượng |
| 6 | < 1/2 | Bình thường | GV không đủ, HV ok |
| 7 | Bình thường | No show | GV ok, HV vắng |
| 8 | Bình thường | < 1/2 | GV ok, HV không đủ |
| 9 | Bình thường | < 1/2 (≥15p) | GV ok, HV tham gia ≥15p |
| 10 | Quá giờ | No show | GV quá giờ, HV vắng |
| 11 | Quá giờ | < 1/2 | GV quá giờ, HV không đủ |
| 12 | Quá giờ | Bình thường | **Thành công (Success)** |
| 13-17 | | | Các mã đặc biệt khác |

**Phân loại cho thống kê:**
- **Success (Chargeable):** codes 4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17
- **Student No-show:** codes 1, 4, 7, 10 (cột "No show" của HV)
- **Student Half:** codes 2, 5, 8, 11 (cột "< 1/2" của HV)
- **Teacher No-show:** codes 1, 2, 3 (hàng "No show" của GV)
- **CSI Success (chỉ cho CSI):** codes 3, 6, 9, 12

### 3.6 Trạng thái lớp nhóm (grpcls_status)
```
1 = Scheduled
2 = Completed
3 = Cancelled
```

### 3.7 Phân loại class type (theo grpcls_total_seats)
```
grpcls_total_seats = 1 (hoặc ordles_grpcls_id = 0) → 1:1
grpcls_total_seats = 2 (chính xác = 2)              → 1:2
grpcls_total_seats = 3                               → 1:3
grpcls_total_seats BETWEEN 4 AND 6                   → 1:6
grpcls_total_seats BETWEEN 7 AND 10                  → 1:8
```

### 3.8 Quốc tịch giáo viên (mapping)
```
VN = Vietnamese
PH = Philippines
ZA = Native 1
GB = Native 2
```

### 3.9 Loại giao dịch ví (usrtxn_type)
```
8  = Learner Refund
9  = Teacher Payment
10 = Money Withdraw
11 = Money Deposit
15 = Reward Points Redeemed
16 = Affiliate Commission
```

### 3.10 Session Log (sesslog)
```
sesslog_record_type:  1=OrderLesson, 2=GroupClass
sesslog_user_type:    1=Student, 2=Teacher, 3=Admin
sesslog_changed_status: tương ứng ordles_status (4=Cancelled)
```

### 3.11 Trạng thái nghỉ phép (tlr_status)
```
0 = Draft
1 = Pending
2 = Auto Approved
3 = Approved
4 = Rejected
5 = More Info
6 = Canceled
```

### 3.12 Loại vi phạm nghỉ phép (tlv_violation_type)
```
1 = Quota
2 = Deadline
3 = No-show
4 = Class Impact
```

### 3.13 Trạng thái feedback (teafeed_status)
```
0 = Draft
1 = Pending
2 = Approved
3 = Rejected
4 = Hidden
```

### 3.14 Loại feedback (teafeed_type)
```
1 = Trial (Feedback buổi thử)
2 = Regular (Feedback buổi thường)
3 = Midterm (Giữa kỳ)
4 = Final (Cuối kỳ)
```

### 3.15 SPEAKWELL Subject IDs
```sql
-- Lấy động từ DB:
SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS'
-- Giá trị hiện tại:
533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
584, 582, 404, 403, 583, 471
```

### 3.16 SpeakWell LCMS Course IDs
```
346, 563, 595, 1084
```

### 3.17 LCMS Section Types (cou_section_type)
```
1 = Lecture (Homework)
2 = Homework
3 = Test
4 = Resource
```

### 3.18 CSI Health Score Categories
```
Green (Xanh — Khỏe mạnh):  health_score >= 85
Yellow (Vàng — Cần theo dõi): health_score >= 50 && < 85
Red (Đỏ — Nguy hiểm):       health_score < 50
```

### 3.19 Trạng thái quiz (quizat_evaluation)
```
0 = Not Evaluated
1 = Passed
2 = Failed
```

### 3.20 Login action types (tbl_sales_lead_logs)
```
sllg_action_type: 'LOGIN'
sllg_action:      'LOGIN_SUCCESS', 'LOGIN_FAILED', 'REGISTERED'
```

### 3.21 Timezone
```
Dữ liệu lưu: UTC (+00:00)
Hiển thị:     UTC+7 (+07:00) — Asia/Ho_Chi_Minh
Chuyển đổi:   CONVERT_TZ(datetime, '+00:00', '+07:00')
```

### 3.22 Teacher Qualified (tbl_teacher_stats)
```sql
-- GV đủ điều kiện dạy:
testat_preference > 0 AND testat_qualification > 0 
AND testat_teachlang > 0 AND testat_speaklang > 0 AND testat_availability > 0
```

---

## 4. SQL Queries — Theo tính năng

### 4.1 Tổng quan — Thống kê người dùng

```sql
-- Đếm active learners
SELECT COUNT(*) FROM tbl_users
WHERE user_active = 1 AND user_deleted IS NULL AND user_is_teacher = 0;

-- Đếm active teachers
SELECT COUNT(*) FROM tbl_users
WHERE user_active = 1 AND user_deleted IS NULL AND user_is_teacher = 1;

-- Đếm learners verified
SELECT COUNT(*) FROM tbl_users
WHERE user_active = 1 AND user_deleted IS NULL AND user_is_teacher = 0 AND user_verified IS NOT NULL;

-- Xu hướng đăng ký người dùng
SELECT DATE(user_created) as date, COUNT(*) as count
FROM tbl_users
WHERE user_active = 1 AND user_created >= NOW() - INTERVAL ? DAY
GROUP BY date ORDER BY date;
```

### 4.2 Tổng quan — Thống kê đơn hàng & Doanh thu

```sql
-- Top learners theo chi tiêu
SELECT order_user_id, COUNT(*) as order_count, SUM(order_total_amount) as total_spent
FROM tbl_orders
WHERE order_payment_status = 1 AND order_status = 2
GROUP BY order_user_id
ORDER BY total_spent DESC LIMIT ?;

-- Doanh thu theo ngày
SELECT DATE(order_addedon) as date, SUM(order_total_amount) as total
FROM tbl_orders
WHERE order_payment_status = 1 AND order_status = 2
  AND order_addedon >= NOW() - INTERVAL ? DAY
GROUP BY date ORDER BY date;

-- Conversion funnel
SELECT COUNT(*) FROM tbl_users WHERE user_active = 1 AND user_deleted IS NULL;  -- Registered
SELECT COUNT(DISTINCT order_user_id) FROM tbl_orders WHERE order_status = 2;     -- Ordered
SELECT COUNT(DISTINCT order_user_id) FROM tbl_orders 
WHERE order_payment_status = 1 AND order_status = 2;                             -- Paid
```

### 4.3 Buổi học — Multi-period Session Stats

```sql
-- Đếm buổi học theo trạng thái trong khoảng thời gian
SELECT ordles_status, COUNT(*) as cnt
FROM tbl_order_lessons
WHERE ordles_lesson_starttime BETWEEN ? AND ?
  AND ordles_tlang_id IN (/* SPEAKWELL_SUBJECT_IDS */)
GROUP BY ordles_status;

-- Buổi học hôm nay (UTC+7)
SELECT COUNT(*) FROM tbl_order_lessons
WHERE ordles_status IN (2, 3)
  AND DATE(CONVERT_TZ(ordles_lesson_starttime, '+00:00', '+07:00')) = CURDATE();

-- Buổi học đang diễn ra
SELECT * FROM tbl_order_lessons
WHERE ordles_status IN (2)
  AND ordles_lesson_starttime <= NOW()
  AND DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) >= NOW();

-- Buổi học sắp tới
SELECT * FROM tbl_order_lessons
WHERE ordles_status = 2
  AND ordles_lesson_starttime > NOW()
  AND ordles_lesson_starttime <= DATE_ADD(NOW(), INTERVAL 2 HOUR);

-- Xu hướng buổi học theo tháng
SELECT DATE_FORMAT(CONVERT_TZ(ordles_lesson_starttime, '+00:00', '+07:00'), '%Y-%m') as month,
       COUNT(*) as total,
       SUM(CASE WHEN ordles_status = 3 THEN 1 ELSE 0 END) as completed
FROM tbl_order_lessons
WHERE ordles_tlang_id IN (/* SPW IDs */)
GROUP BY month ORDER BY month;
```

### 4.4 Buổi học — Session Success/Failure Breakdown

```sql
-- Chargeable sessions (acceptance code)
SELECT COUNT(*) FROM tbl_order_lessons ol
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE ol.ordles_status = 3 
  AND ole.ole_acceptance_code IN (4,5,6,7,8,9,10,11,12,16,17)
  AND ol.ordles_lesson_starttime BETWEEN ? AND ?
  AND ol.ordles_tlang_id IN (?);

-- Awaiting ClassIn data (buổi mới kết thúc < 30 phút)
SELECT COUNT(*) FROM tbl_order_lessons ol
LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE ol.ordles_status = 3
  AND (ole.ole_ordles_id IS NULL OR ole.ole_acceptance_code = 0)
  AND TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_endtime, NOW()) <= 30;

-- Missing ClassIn data (buổi kết thúc > 30 phút vẫn chưa có)
SELECT COUNT(*) FROM tbl_order_lessons ol
LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE ol.ordles_status = 3
  AND (ole.ole_ordles_id IS NULL OR ole.ole_acceptance_code = 0)
  AND TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_endtime, NOW()) > 30;

-- Urgent cancellation detection (huỷ gấp < 24h)
SELECT COUNT(DISTINCT ol.ordles_id) FROM tbl_order_lessons ol
WHERE ol.ordles_status = 4
  AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)
  AND EXISTS (
    SELECT 1 FROM tbl_session_logs
    WHERE sesslog_record_id = ol.ordles_id
      AND sesslog_changed_status = 4
      AND sesslog_record_type = 1
  );

-- Urgent cancellation breakdown by user type
SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) as cnt
FROM tbl_order_lessons ol
JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 4 AND sl.sesslog_record_type = 1
WHERE ol.ordles_status = 4
  AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)
GROUP BY sl.sesslog_user_type;

-- No-show detection (scheduled but past end time)
SELECT COUNT(*) FROM tbl_order_lessons
WHERE ordles_status = 2
  AND DATE_ADD(ordles_lesson_starttime, INTERVAL ordles_duration MINUTE) < NOW();
```

### 4.5 Buổi học — Class Type Breakdown (1:1 vs 1:2)

```sql
-- 1:2 Group Classes status counts
SELECT grpcls_status, COUNT(*) as cnt
FROM tbl_group_classes
WHERE grpcls_status IN (1,2,3)
  AND grpcls_total_seats = 2
  AND grpcls_start_datetime BETWEEN ? AND ?
  AND grpcls_tlang_id IN (?)
GROUP BY grpcls_status;

-- 1:2 Urgent cancellation
SELECT COUNT(DISTINCT gc.grpcls_id)
FROM tbl_group_classes gc
JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 3 AND sl.sesslog_record_type = 2
WHERE gc.grpcls_status = 3
  AND sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY);

-- Per-program breakdown (SPEAKWELL vs EASYSPEAK)
SELECT CASE WHEN ordles_tlang_id IN (?) THEN 'speakwell' ELSE 'easyspeak' END as program,
       ordles_status, COUNT(*) as cnt
FROM tbl_order_lessons
WHERE ordles_status IN (2,3,4) AND ordles_lesson_starttime BETWEEN ? AND ?
GROUP BY 1, 2;

-- Students by class size
SELECT
  CASE 
    WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN '1:1'
    WHEN gc.grpcls_total_seats = 2 THEN '1:2'
    WHEN gc.grpcls_total_seats = 3 THEN '1:3'
    WHEN gc.grpcls_total_seats BETWEEN 4 AND 6 THEN '1:6'
    WHEN gc.grpcls_total_seats BETWEEN 7 AND 10 THEN '1:8'
    ELSE 'Group'
  END as class_size,
  COUNT(DISTINCT o.order_user_id) as student_count
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
JOIN tbl_users u ON u.user_id = o.order_user_id
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id, MAX(grpcls_total_seats) as grpcls_total_seats
           FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc
  ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status IN (2,3) AND ol.ordles_tlang_id IN (?)
GROUP BY class_size
ORDER BY FIELD(class_size, '1:1', '1:2', '1:3', '1:6', '1:8', 'Group');
```

### 4.6 ClassIn / Acceptance Code Stats

```sql
-- Acceptance code distribution
SELECT ole_acceptance_code, COUNT(*) as count
FROM tbl_order_lessons_extras
JOIN tbl_order_lessons ON ole_ordles_id = ordles_id
WHERE ordles_lesson_starttime BETWEEN ? AND ?
  AND ordles_tlang_id IN (?) AND ordles_status = 3
GROUP BY ole_acceptance_code ORDER BY ole_acceptance_code;
```

### 4.7 Cancellation Analysis

```sql
-- 1:1 Cancellation by user type
SELECT sl.sesslog_user_type, COUNT(DISTINCT ol.ordles_id) as count
FROM tbl_order_lessons ol
JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 4 AND sl.sesslog_record_type = 1
WHERE ol.ordles_status = 4
  AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY)
  AND ol.ordles_lesson_starttime BETWEEN ? AND ?
GROUP BY sl.sesslog_user_type;

-- 1:2 Cancellation by user type
SELECT sl.sesslog_user_type, COUNT(DISTINCT gc.grpcls_id) as count
FROM tbl_group_classes gc
JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 3 AND sl.sesslog_record_type = 2
WHERE gc.grpcls_status = 3
  AND sl.sesslog_created > DATE_SUB(gc.grpcls_start_datetime, INTERVAL 1 DAY)
GROUP BY sl.sesslog_user_type;

-- 1:1 Cancellation details with teacher/student names
SELECT sl.sesslog_id, sl.sesslog_record_id, sl.sesslog_user_type, sl.sesslog_comment,
       CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) as teacher_name,
       COALESCE(t.user_email, '') as teacher_email,
       CONCAT(COALESCE(s.user_first_name, ''), ' ', COALESCE(s.user_last_name, '')) as student_name
FROM tbl_order_lessons ol
JOIN tbl_session_logs sl ON ol.ordles_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 4 AND sl.sesslog_record_type = 1
LEFT JOIN tbl_users t ON ol.ordles_teacher_id = t.user_id
LEFT JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
LEFT JOIN tbl_users s ON o.order_user_id = s.user_id
WHERE ol.ordles_status = 4
  AND ol.ordles_updated > DATE_SUB(ol.ordles_lesson_starttime, INTERVAL 1 DAY);

-- 1:2 Cancellation details with GROUP_CONCAT for students
SELECT gc.grpcls_id, sl.sesslog_user_type, sl.sesslog_comment,
       CONCAT(COALESCE(t.user_first_name, ''), ' ', COALESCE(t.user_last_name, '')) as teacher_name,
       (SELECT GROUP_CONCAT(CONCAT(us.user_first_name, ' ', us.user_last_name) SEPARATOR ', ')
        FROM tbl_order_classes oc
        INNER JOIN tbl_users us ON oc.ordcls_beneficiary_id = us.user_id
        WHERE oc.ordcls_grpcls_id = gc.grpcls_id) as student_name
FROM tbl_group_classes gc
JOIN tbl_session_logs sl ON gc.grpcls_id = sl.sesslog_record_id
  AND sl.sesslog_changed_status = 3 AND sl.sesslog_record_type = 2
LEFT JOIN tbl_users t ON gc.grpcls_teacher_id = t.user_id
WHERE gc.grpcls_status = 3;
```

### 4.8 Teacher Login Status & Violations

```sql
-- Top teachers by trial lessons
SELECT ordles_teacher_id,
       COUNT(*) as total_trials,
       SUM(CASE WHEN ordles_status = 3 THEN 1 ELSE 0 END) as completed_trials
FROM tbl_order_lessons
WHERE ordles_type = 1 AND ordles_lesson_starttime BETWEEN ? AND ?
GROUP BY ordles_teacher_id
ORDER BY total_trials DESC LIMIT ?;

-- Late entry detection (GV vào trễ > 5 phút)
SELECT * FROM tbl_order_lessons ol
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_starttime, ole.ole_teacher_first_join) > 5;

-- Early exit detection (GV rời sớm > 5 phút)
SELECT * FROM tbl_order_lessons ol
JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
WHERE TIMESTAMPDIFF(MINUTE, ol.ordles_lesson_starttime, ole.ole_teacher_last_leave)
      < (ol.ordles_duration - 5);

-- Late start lessons (cả GV và HV)
SELECT * FROM tbl_order_lessons
WHERE TIMESTAMPDIFF(MINUTE, ordles_lesson_starttime, ordles_teacher_starttime) > 5
   OR TIMESTAMPDIFF(MINUTE, ordles_lesson_starttime, ordles_student_starttime) > 5;
```

### 4.9 Teacher Availability Grid

```sql
-- Speakwell teachers (GV dạy môn SpeakWell)
SELECT DISTINCT utlang_user_id FROM tbl_user_teach_languages
WHERE utlang_tlang_id IN (/* SPEAKWELL_SUBJECT_IDS */);

-- Qualified teachers
SELECT testat_user_id FROM tbl_teacher_stats
WHERE testat_preference > 0 AND testat_qualification > 0
  AND testat_teachlang > 0 AND testat_speaklang > 0 AND testat_availability > 0;

-- Teacher info with country
SELECT user_id, user_first_name, user_last_name, user_email, user_username,
       country_code, country_identifier
FROM tbl_users LEFT JOIN tbl_countries ON country_id = user_country_id
WHERE user_id IN (?);

-- Trial capability
SELECT DISTINCT utlang_user_id FROM tbl_user_teach_languages WHERE utlang_tlang_id = 533;

-- Trial settings
SELECT user_id, user_trial_enabled FROM tbl_user_settings WHERE user_id IN (?);

-- Availability slots
SELECT avail_user_id, avail_starttime, avail_endtime
FROM tbl_availability
WHERE avail_starttime >= ? AND avail_endtime <= ?
  AND avail_user_id IN (?);

-- Booked lessons
SELECT ordles_teacher_id, ordles_lesson_starttime, ordles_lesson_endtime, ordles_status
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
WHERE ol.ordles_status IN (2,3) AND o.order_payment_status = 1 AND o.order_status = 2
  AND ol.ordles_lesson_starttime >= ? AND ol.ordles_lesson_endtime <= ?
  AND ol.ordles_teacher_id IN (?);

-- Booked group classes
SELECT grpcls_teacher_id, grpcls_start_datetime, grpcls_end_datetime
FROM tbl_group_classes
WHERE grpcls_status = 1
  AND grpcls_start_datetime >= ? AND grpcls_end_datetime <= ?
  AND grpcls_teacher_id IN (?);

-- Slot detail: lesson info with student name
SELECT ol.ordles_teacher_id, ol.ordles_id, ol.ordles_beneficiary_id,
       CONCAT(IFNULL(student.user_last_name,''), ' ', IFNULL(student.user_first_name,'')) AS student_full_name,
       tl.tlang_identifier
FROM tbl_order_lessons ol
LEFT JOIN tbl_users student ON ol.ordles_beneficiary_id = student.user_id
LEFT JOIN tbl_teach_languages tl ON ol.ordles_tlang_id = tl.tlang_id
WHERE ol.ordles_teacher_id IN (?) AND ol.ordles_status IN (2,3)
  AND ol.ordles_lesson_starttime >= ? AND ol.ordles_lesson_endtime <= ?;

-- Odd time slots detection
SELECT DISTINCT DATE_FORMAT(DATE_ADD(ol.ordles_teacher_starttime, INTERVAL 7 HOUR), '%H:%i') as time_slot
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
WHERE ol.ordles_status IN (2,3) AND o.order_payment_status = 1;
```

### 4.10 Teacher Leave Management

```sql
-- Leave request stats
SELECT tlr_status, COUNT(*) as cnt
FROM tbl_teacher_leave_requests
WHERE tlr_start_date BETWEEN ? AND ?
GROUP BY tlr_status;

-- Teachers with most leave
SELECT tlr_teacher_id, SUM(tlr_total_days) as total_days, COUNT(*) as request_count
FROM tbl_teacher_leave_requests
WHERE tlr_status IN (2, 3)  -- Approved
GROUP BY tlr_teacher_id
ORDER BY total_days DESC LIMIT ?;

-- Leave quota summary
SELECT SUM(tlq_base_quota) as total_quota,
       SUM(tlq_used_days) as total_used,
       SUM(tlq_base_quota - tlq_used_days) as total_remaining,
       COUNT(DISTINCT tlq_teacher_id) as teachers_count
FROM tbl_teacher_leave_quotas
WHERE tlq_year = ?;

-- Leave violation stats
SELECT tlv_violation_type, COUNT(*) as cnt
FROM tbl_teacher_leave_violations
GROUP BY tlv_violation_type;

-- Leave affected sessions detail
SELECT tlrs.*, tlr.*,
       CONCAT(teacher.user_first_name, ' ', teacher.user_last_name) as teacher_name,
       CONCAT(COALESCE(learner.user_first_name, ''), ' ', COALESCE(learner.user_last_name, '')) as learner_name
FROM tbl_teacher_leave_request_sessions tlrs
JOIN tbl_teacher_leave_requests tlr ON tlrs.tlrs_leave_request_id = tlr.tlr_id
JOIN tbl_users teacher ON tlr.tlr_teacher_id = teacher.user_id
LEFT JOIN tbl_order_lessons ol ON tlrs.tlrs_session_id = ol.ordles_id
LEFT JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
LEFT JOIN tbl_users learner ON o.order_user_id = learner.user_id
WHERE tlr.tlr_status IN (2, 3);
```

### 4.11 Feedback & Đánh giá

```sql
-- Feedback stats by status
SELECT teafeed_status, COUNT(*) as cnt
FROM tbl_teacher_feedbacks
WHERE teafeed_created_at BETWEEN ? AND ?
GROUP BY teafeed_status;

-- Top teachers by feedback count
SELECT teafeed_teacher_id, COUNT(*) as feedback_count
FROM tbl_teacher_feedbacks
GROUP BY teafeed_teacher_id
ORDER BY feedback_count DESC LIMIT ?;

-- Feedback trend
SELECT DATE_FORMAT(teafeed_created_at, '%Y-%m') as month, COUNT(*) as cnt
FROM tbl_teacher_feedbacks
GROUP BY month ORDER BY month;
```

### 4.12 Quiz Stats

```sql
-- Quiz overview
SELECT COUNT(*) as total_quizzes,
       SUM(CASE WHEN quiz_status = 2 THEN 1 ELSE 0 END) as active_quizzes
FROM tbl_quizzes WHERE quiz_deleted IS NULL;

-- Pass/Fail stats
SELECT quizat_evaluation, COUNT(*) as cnt
FROM tbl_quiz_attempts
WHERE quizat_status = 2  -- Completed
GROUP BY quizat_evaluation;

-- Average score
SELECT AVG(quizat_scored / quizat_marks * 100) as avg_score
FROM tbl_quiz_attempts
WHERE quizat_status = 2 AND quizat_marks > 0;
```

### 4.13 Login / Activity Stats

```sql
-- Login count by period
SELECT COUNT(*) FROM tbl_sales_lead_logs
WHERE sllg_action_type = 'LOGIN' AND sllg_action = 'LOGIN_SUCCESS'
  AND sllg_occurred_at BETWEEN ? AND ?;

-- Logins by hour (UTC+7)
SELECT HOUR(DATE_ADD(sllg_occurred_at, INTERVAL 7 HOUR)) as hour, COUNT(*) as count
FROM tbl_sales_lead_logs
WHERE sllg_action_type = 'LOGIN' AND sllg_action = 'LOGIN_SUCCESS'
  AND sllg_occurred_at >= NOW() - INTERVAL 30 DAY
GROUP BY hour ORDER BY hour;

-- Logins by day of week
SELECT DAYOFWEEK(sllg_occurred_at) as day_of_week, COUNT(*) as count
FROM tbl_sales_lead_logs
WHERE sllg_action_type = 'LOGIN' AND sllg_action = 'LOGIN_SUCCESS'
  AND sllg_occurred_at >= NOW() - INTERVAL 30 DAY
GROUP BY day_of_week ORDER BY day_of_week;

-- Logins by source
SELECT sllg_source, COUNT(*) as count
FROM tbl_sales_lead_logs
WHERE sllg_action_type = 'LOGIN' AND sllg_action = 'LOGIN_SUCCESS'
  AND sllg_occurred_at >= NOW() - INTERVAL 30 DAY AND sllg_source IS NOT NULL
GROUP BY sllg_source ORDER BY count DESC LIMIT 10;

-- Top active users
SELECT sllg_user_id, COUNT(*) as login_count
FROM tbl_sales_lead_logs
WHERE sllg_action_type = 'LOGIN' AND sllg_action = 'LOGIN_SUCCESS'
  AND sllg_occurred_at >= NOW() - INTERVAL 30 DAY
GROUP BY sllg_user_id
ORDER BY login_count DESC LIMIT ?;

-- Never logged in students (check auth token)
SELECT usrtok_user_id FROM tbl_user_auth_token WHERE usrtok_user_id IN (?);

-- Students with multiple lessons same day
SELECT order_user_id, DATE(ordles_lesson_starttime) as lesson_date, COUNT(*) as lesson_count
FROM tbl_order_lessons
JOIN tbl_orders ON ordles_order_id = order_id
WHERE ordles_status = 2 AND ordles_tlang_id IN (?)
GROUP BY order_user_id, lesson_date
HAVING lesson_count >= 2;
```

### 4.14 Voucher / Coupon Stats

```sql
-- Active coupons
SELECT COUNT(*) FROM tbl_coupons WHERE coupon_active = 1 AND coupon_end_date >= NOW();

-- Coupon usage stats
SELECT c.coupon_id, c.coupon_code, c.coupon_discount_type, c.coupon_discount_value,
       c.coupon_max_uses, c.coupon_used_uses, c.coupon_start_date, c.coupon_end_date
FROM tbl_coupons c
WHERE c.coupon_active = 1;

-- Coupon history with orders
SELECT ch.couhis_coupon_id, COUNT(*) as usage_count
FROM tbl_coupons_history ch
JOIN tbl_orders o ON ch.couhis_order_id = o.order_id
WHERE o.order_status = 2 AND ch.couhis_created BETWEEN ? AND ?
GROUP BY ch.couhis_coupon_id;

-- Coupon log activity
SELECT clog_action, COUNT(*) as cnt
FROM tbl_coupon_logs
WHERE clog_created_at BETWEEN ? AND ?
GROUP BY clog_action;
```

### 4.15 Program Stats

```sql
-- Program enrollment
SELECT p.id, p.title, COUNT(pu.id) as enrolled_count
FROM tbl_program p
LEFT JOIN tbl_program_user pu ON p.id = pu.program_id
WHERE p.status = 'published'
GROUP BY p.id, p.title;

-- Curriculum lectures for program
SELECT curriculum_id FROM tbl_program_curriculum WHERE program_id = ?;
SELECT id FROM tbl_curriculum_lecture WHERE curriculum_id IN (?);
```

### 4.16 CSI (Customer Success Index) — CTE phức tạp

```sql
-- Base CTE: Buổi học SpeakWell completed với acceptance code
WITH joined AS (
    SELECT l.ordles_id, l.ordles_beneficiary_id, l.ordles_lesson_starttime, l.ordles_tlang_id,
           (SELECT ex.ole_acceptance_code 
            FROM tbl_order_lessons_extras ex 
            WHERE ex.ole_ordles_id = l.ordles_id 
            ORDER BY ex.ole_id DESC LIMIT 1) AS ole_acceptance_code
    FROM tbl_order_lessons l
    WHERE l.ordles_beneficiary_id IS NOT NULL 
      AND l.ordles_status IN (3)
      AND FIND_IN_SET(l.ordles_tlang_id, 
          (SELECT REPLACE(conf_val,' ','') FROM tbl_configurations 
           WHERE conf_name='CONF_SPEAKWELL_SUBJECT_IDS' LIMIT 1))
      AND l.ordles_lesson_starttime >= '2025-11-04'
)

-- Full CTE: Tính health score per student
, first_3_ranked AS (
    SELECT ordles_beneficiary_id, ordles_lesson_starttime, ole_acceptance_code,
           ROW_NUMBER() OVER (PARTITION BY ordles_beneficiary_id 
                              ORDER BY ordles_lesson_starttime ASC) as rn
    FROM joined
)
, first_3_pivot AS (
    SELECT ordles_beneficiary_id,
           MAX(CASE WHEN rn = 1 THEN ole_acceptance_code END) as first_ac,
           MAX(CASE WHEN rn = 2 THEN ole_acceptance_code END) as second_ac,
           MAX(CASE WHEN rn = 3 THEN ole_acceptance_code END) as third_ac,
           MAX(CASE WHEN rn = 1 THEN ordles_lesson_starttime END) as first_time,
           MAX(CASE WHEN rn = 2 THEN ordles_lesson_starttime END) as second_time,
           MAX(CASE WHEN rn = 3 THEN ordles_lesson_starttime END) as third_time
    FROM first_3_ranked WHERE rn <= 3
    GROUP BY ordles_beneficiary_id
)
, leave_per_student AS (
    SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) as student_id,
           COUNT(*) as leave_count
    FROM tbl_teacher_leave_request_sessions lrs
    INNER JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
    WHERE lr.tlr_status IN (2, 3)
    GROUP BY student_id
)
, csi_data AS (
    SELECT j.ordles_beneficiary_id as student_id,
           COUNT(*) as total_lessons,
           SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) as success_count,
           SUM(CASE WHEN j.ole_acceptance_code IN (1,4,7,10) THEN 1 ELSE 0 END) as student_noshow,
           SUM(CASE WHEN j.ole_acceptance_code IN (2,5,8,11) THEN 1 ELSE 0 END) as student_half,
           SUM(CASE WHEN j.ole_acceptance_code IN (1,2,3) THEN 1 ELSE 0 END) as teacher_noshow,
           ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) * 100.0 
                 / COUNT(*), 1) as health_score,
           DATEDIFF(MAX(j.ordles_lesson_starttime), MIN(j.ordles_lesson_starttime)) / 7.0 as weeks_studied,
           COUNT(*) * 1.0 / GREATEST(
               DATEDIFF(MAX(j.ordles_lesson_starttime), MIN(j.ordles_lesson_starttime)) / 7.0, 1
           ) as avg_lessons_per_week
    FROM joined j
    GROUP BY j.ordles_beneficiary_id
)
, csi_full AS (
    SELECT cd.*,
           u.user_first_name, u.user_last_name, u.user_email,
           us.user_phone_number,
           COALESCE(adm.admin_name, '') as css_staff,
           COALESCE(lps.leave_count, 0) as leave_count,
           fp.first_ac, fp.second_ac, fp.third_ac,
           fp.first_time, fp.second_time, fp.third_time,
           CASE 
               WHEN cd.health_score >= 85 THEN 'Xanh (Khỏe mạnh)'
               WHEN cd.health_score >= 50 THEN 'Vàng (Cần theo dõi)'
               ELSE 'Đỏ (Nguy hiểm)'
           END as health_category
    FROM csi_data cd
    LEFT JOIN tbl_users u ON cd.student_id = u.user_id
    LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
    LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
    LEFT JOIN tbl_admin adm ON ue.usrextra_css_id = adm.admin_id
    LEFT JOIN leave_per_student lps ON cd.student_id = lps.student_id
    LEFT JOIN first_3_pivot fp ON cd.student_id = fp.ordles_beneficiary_id
)
-- KPI summary
SELECT COUNT(*) as total_students,
       SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) as green_count,
       SUM(CASE WHEN health_category = 'Vàng (Cần theo dõi)' THEN 1 ELSE 0 END) as yellow_count,
       SUM(CASE WHEN health_category = 'Đỏ (Nguy hiểm)' THEN 1 ELSE 0 END) as red_count,
       ROUND(AVG(health_score), 1) as avg_health_score,
       ROUND(AVG(avg_lessons_per_week), 2) as avg_lessons_per_week
FROM csi_full;
```

### 4.17 CSI — Early Warning System (EWS)

```sql
-- Consecutive missed lessons (tính streak miss gần nhất)
WITH joined AS (/* base CTE */),
student_lessons_ranked AS (
    SELECT ordles_beneficiary_id,
           CASE WHEN ole_acceptance_code IN (3,6,9,12) THEN 0 ELSE 1 END AS is_missed,
           ROW_NUMBER() OVER (PARTITION BY ordles_beneficiary_id 
                              ORDER BY ordles_lesson_starttime DESC) as rn
    FROM joined
),
first_non_missed AS (
    SELECT ordles_beneficiary_id as user_id, MIN(rn) as first_ok_rn
    FROM student_lessons_ranked WHERE is_missed = 0
    GROUP BY ordles_beneficiary_id
),
student_totals AS (
    SELECT ordles_beneficiary_id as user_id, MAX(rn) as total_lessons
    FROM student_lessons_ranked GROUP BY ordles_beneficiary_id
),
ews_calc AS (
    SELECT st.user_id,
           COALESCE(fnm.first_ok_rn, st.total_lessons + 1) - 1 as total_missed
    FROM student_totals st
    LEFT JOIN first_non_missed fnm ON st.user_id = fnm.user_id
)
SELECT ec.user_id, ec.total_missed,
       u.user_first_name, u.user_last_name, u.user_email,
       us.user_phone_number, COALESCE(adm.admin_name, '') as css_staff
FROM ews_calc ec
LEFT JOIN tbl_users u ON ec.user_id = u.user_id
LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
LEFT JOIN tbl_user_extras ue ON u.user_id = ue.usrextra_user_id
LEFT JOIN tbl_admin adm ON ue.usrextra_css_id = adm.admin_id
WHERE ec.total_missed >= 2
ORDER BY ec.total_missed DESC;
```

### 4.18 CSI — Trends & Health Trends

```sql
-- Weekly trend
SELECT YEARWEEK(j.ordles_lesson_starttime, 3) as period,
       COUNT(*) as total_lessons,
       SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) as success_count,
       COUNT(DISTINCT j.ordles_beneficiary_id) as unique_students
FROM joined j
GROUP BY period ORDER BY period;

-- Health trend per period
WITH period_student_scores AS (
    SELECT YEARWEEK(j.ordles_lesson_starttime, 3) as period,
           j.ordles_beneficiary_id as student_id,
           ROUND(SUM(CASE WHEN j.ole_acceptance_code IN (3,6,9,12) THEN 1 ELSE 0 END) * 100.0 
                 / COUNT(*), 1) AS health_score
    FROM joined j
    GROUP BY period, student_id
)
SELECT period,
       COUNT(*) as total_students,
       SUM(CASE WHEN health_score >= 85 THEN 1 ELSE 0 END) as green_count,
       SUM(CASE WHEN health_score >= 50 AND health_score < 85 THEN 1 ELSE 0 END) as yellow_count,
       SUM(CASE WHEN health_score < 50 THEN 1 ELSE 0 END) as red_count
FROM period_student_scores
GROUP BY period ORDER BY period;
```

### 4.19 CSI — SpeakWell Student Stats (Active/Inactive)

```sql
-- Total SpeakWell students (UNION order_lessons + order_classes)
WITH q1 AS (
    SELECT DISTINCT ol.ordles_beneficiary_id as student_id
    FROM tbl_order_lessons ol
    WHERE ol.ordles_tlang_id IN (/* SPW IDs */) AND ol.ordles_beneficiary_id IS NOT NULL
    UNION
    SELECT DISTINCT oc.ordcls_beneficiary_id
    FROM tbl_order_classes oc
    JOIN tbl_group_classes gc ON oc.ordcls_grpcls_id = gc.grpcls_id
    WHERE gc.grpcls_tlang_id IN (/* SPW IDs */) AND oc.ordcls_beneficiary_id IS NOT NULL
),
-- Active = last 30 days login + completed lessons
q2 AS (
    SELECT DISTINCT ol.ordles_beneficiary_id as student_id
    FROM tbl_order_lessons ol
    JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
    JOIN tbl_users u ON ol.ordles_beneficiary_id = u.user_id
    WHERE ol.ordles_tlang_id IN (/* SPW IDs */)
      AND ol.ordles_status IN (2, 3)
      AND ol.ordles_lesson_starttime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
)
-- Inactive = total minus active
SELECT COUNT(DISTINCT q1.student_id)
FROM q1
WHERE NOT EXISTS (SELECT 1 FROM q2 WHERE q2.student_id = q1.student_id);
```

### 4.20 CSI — Student Orders & Packages

```sql
-- Student orders
SELECT o.*, pm.pmethod_code
FROM tbl_orders o
LEFT JOIN tbl_payment_methods pm ON o.order_pmethod_id = pm.pmethod_id
WHERE o.order_id IN (
    SELECT DISTINCT ol.ordles_order_id FROM tbl_order_lessons ol
    WHERE ol.ordles_beneficiary_id = ?
)
ORDER BY o.order_addedon DESC;

-- Student subscription plans
SELECT sp.*, spl.subplan_title
FROM tbl_order_subscription_plans sp
LEFT JOIN tbl_subscription_plans spl ON sp.ordsplan_plan_id = spl.subplan_id
WHERE sp.ordsplan_beneficiary_id = ?
   OR sp.ordsplan_order_id IN (
       SELECT DISTINCT ol.ordles_order_id FROM tbl_order_lessons ol
       WHERE ol.ordles_beneficiary_id = ?
   )
ORDER BY sp.ordsplan_created DESC;
```

### 4.21 CSI — Teacher Leave Sessions for Student

```sql
SELECT lrs.tlrs_session_id, lrs.tlrs_session_date, lrs.tlrs_need_replacement,
       lrs.tlrs_replacement_type, lr.tlr_status,
       CONCAT(t.user_first_name, ' ', t.user_last_name) as teacher_name
FROM tbl_teacher_leave_request_sessions lrs
INNER JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
LEFT JOIN tbl_users t ON lr.tlr_teacher_id = t.user_id
WHERE lr.tlr_status IN (2, 3)
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) = ?;
```

### 4.22 First Orders with Successful Lessons (CTE + ROW_NUMBER)

```sql
WITH first_orders AS (
    SELECT o.order_id, o.order_user_id, o.order_item_count, o.order_net_amount,
           o.order_addedon, op.ordpay_datetime,
           ROW_NUMBER() OVER (PARTITION BY o.order_user_id 
                              ORDER BY op.ordpay_datetime ASC, o.order_id ASC) AS rn
    FROM tbl_orders o
    INNER JOIN tbl_order_payments op ON o.order_id = op.ordpay_order_id
    WHERE op.ordpay_pmethod_id = 13 AND op.ordpay_amount > 0 AND o.order_net_amount > 0
      AND op.ordpay_datetime >= ? AND o.order_payment_status = 1 AND o.order_status = 2
),
first_lessons AS (
    SELECT fo.order_id, ol.ordles_id, ol.ordles_lesson_starttime,
           ROW_NUMBER() OVER (PARTITION BY fo.order_id 
                              ORDER BY ol.ordles_lesson_starttime ASC, ol.ordles_id ASC) AS rn_lesson
    FROM first_orders fo
    INNER JOIN tbl_order_lessons ol ON ol.ordles_order_id = fo.order_id
    INNER JOIN tbl_order_lessons_extras ole ON ole.ole_ordles_id = ol.ordles_id
    WHERE fo.rn = 1 AND ole.ole_acceptance_code IN (9, 12)
)
SELECT fo.order_id, fo.order_user_id, fo.order_item_count, fo.order_net_amount,
       fo.order_addedon, fo.ordpay_datetime,
       fl.ordles_lesson_starttime,
       DATEDIFF(fl.ordles_lesson_starttime, fo.order_addedon) AS days_difference,
       u.user_first_name, u.user_last_name, u.user_email
FROM first_orders fo
LEFT JOIN first_lessons fl ON fo.order_id = fl.order_id AND fl.rn_lesson = 1
LEFT JOIN tbl_users u ON fo.order_user_id = u.user_id
WHERE fo.rn = 1
ORDER BY fo.ordpay_datetime DESC;
```

### 4.23 Trial Lessons List (CONVERT_TZ + JSON_EXTRACT + JSON_TABLE)

```sql
SELECT ol.ordles_id AS trial_id,
       DATE(CONVERT_TZ(o.order_addedon, '+00:00', '+07:00')) AS trial_request_date,
       DATE(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00')) AS trial_date,
       ol.ordles_status AS trial_status,
       CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
       u.user_email AS student_email,
       JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.name')) AS trial_program_name,
       DATE(CONVERT_TZ(tf.teafeed_created_at, '+00:00', '+07:00')) AS trial_feedback_date,
       JSON_UNQUOTE(JSON_EXTRACT(tf.teafeed_assessment_detail, '$.level')) AS trial_feedback_level,
       -- JSON_TABLE for assessment array
       (SELECT GROUP_CONCAT(
           CONCAT(jt.skill_name, ': ', jt.skill_score) SEPARATOR '\n')
        FROM JSON_TABLE(
           tf.teafeed_assessment_detail,
           '$.assessment[*]' COLUMNS (
               skill_name VARCHAR(255) PATH '$.name',
               skill_score VARCHAR(50) PATH '$.score'
           )
        ) AS jt
       ) AS trial_feedback_assessment
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
LEFT JOIN tbl_users u ON u.user_id = ol.ordles_beneficiary_id
LEFT JOIN tbl_lesson_notes ln ON ln.lesnote_ordles_id = ol.ordles_id AND ln.lesnote_is_published = 1
LEFT JOIN tbl_teacher_feedbacks tf ON tf.teafeed_learner_id = ol.ordles_beneficiary_id
  AND tf.teafeed_record_id = ol.ordles_id AND tf.teafeed_record_type = 1
WHERE ol.ordles_tlang_id = (
    SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID' LIMIT 1
  )
  AND ol.ordles_lesson_starttime BETWEEN ? AND ?
ORDER BY ol.ordles_lesson_starttime DESC;
```

### 4.24 Teacher Change Analysis (CTE + LAG + ROW_NUMBER)

```sql
WITH lesson_sequence AS (
    SELECT l.ordles_id, l.ordles_beneficiary_id AS student_id,
           l.ordles_teacher_id, l.ordles_lesson_starttime,
           l.ordles_order_id, l.ordles_tlang_id,
           LAG(l.ordles_teacher_id) OVER (
               PARTITION BY l.ordles_beneficiary_id, l.ordles_order_id
               ORDER BY l.ordles_lesson_starttime, l.ordles_id
           ) AS prev_teacher_id
    FROM tbl_order_lessons l
    WHERE l.ordles_status = 3 AND l.ordles_tlang_id IN (?)
      AND l.ordles_lesson_starttime BETWEEN ? AND ?
),
change_events AS (
    SELECT * FROM lesson_sequence
    WHERE prev_teacher_id IS NOT NULL AND ordles_teacher_id != prev_teacher_id
),
-- Filter by nationality change (same vs different)
change_events_filtered AS (
    SELECT ce.*,
           c_new.country_code as new_country, c_old.country_code as old_country
    FROM change_events ce
    LEFT JOIN tbl_users t_new ON ce.ordles_teacher_id = t_new.user_id
    LEFT JOIN tbl_countries c_new ON t_new.user_country_id = c_new.country_id
    LEFT JOIN tbl_users t_old ON ce.prev_teacher_id = t_old.user_id
    LEFT JOIN tbl_countries c_old ON t_old.user_country_id = c_old.country_id
),
-- Check if change was due to teacher leave
change_with_leave AS (
    SELECT cef.*,
           CASE WHEN EXISTS (
               SELECT 1 FROM tbl_teacher_leave_request_sessions lrs
               JOIN tbl_teacher_leave_requests lr ON lr.tlr_id = lrs.tlrs_leave_request_id
               WHERE lr.tlr_teacher_id = cef.prev_teacher_id AND lr.tlr_status IN (2,3)
                 AND lrs.tlrs_session_date = DATE(cef.ordles_lesson_starttime)
           ) THEN 1 ELSE 0 END as is_leave_change
    FROM change_events_filtered cef
),
lesson_totals AS (
    SELECT ordles_beneficiary_id as student_id, COUNT(*) as total_lessons,
           COUNT(DISTINCT ordles_teacher_id) as distinct_teachers,
           COUNT(DISTINCT ordles_order_id) as order_count
    FROM lesson_sequence GROUP BY ordles_beneficiary_id
),
student_courses AS (
    SELECT ls.student_id,
           GROUP_CONCAT(DISTINCT tl.tlang_identifier ORDER BY tl.tlang_identifier SEPARATOR ', ') AS course_names
    FROM lesson_sequence ls
    INNER JOIN tbl_teach_languages tl ON ls.ordles_tlang_id = tl.tlang_id
    GROUP BY ls.student_id
),
change_counts AS (
    SELECT cwl.student_id,
           COUNT(*) as change_count,
           SUM(cwl.is_leave_change) as leave_change_count
    FROM change_with_leave cwl
    GROUP BY cwl.student_id
)
SELECT cc.student_id,
       CONCAT(u.user_first_name, ' ', u.user_last_name) as student_name,
       u.user_email, us.user_phone_number as phone,
       cc.change_count, cc.leave_change_count,
       lt.total_lessons, lt.distinct_teachers, lt.order_count,
       sc.course_names
FROM change_counts cc
INNER JOIN tbl_users u ON cc.student_id = u.user_id
LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
LEFT JOIN lesson_totals lt ON cc.student_id = lt.student_id
LEFT JOIN student_courses sc ON cc.student_id = sc.student_id
ORDER BY cc.change_count DESC;
```

### 4.25 Teacher Change — Student Detail

```sql
SELECT l.ordles_id, l.ordles_teacher_id, l.ordles_order_id,
       tl.tlang_identifier AS course_name,
       CONCAT(t.user_first_name, ' ', t.user_last_name) AS teacher_name,
       c.country_code AS teacher_country_code,
       l.ordles_lesson_starttime, l.ordles_status,
       DAYOFWEEK(l.ordles_lesson_starttime) AS lesson_dow,
       TIME_FORMAT(l.ordles_lesson_starttime, '%H:%i') AS lesson_time_slot
FROM tbl_order_lessons l
LEFT JOIN tbl_users t ON l.ordles_teacher_id = t.user_id
LEFT JOIN tbl_countries c ON t.user_country_id = c.country_id
LEFT JOIN tbl_teach_languages tl ON l.ordles_tlang_id = tl.tlang_id
WHERE l.ordles_beneficiary_id = ? AND l.ordles_status IN (2, 3)
  AND l.ordles_tlang_id IN (?)
ORDER BY l.ordles_lesson_starttime;
```

### 4.26 LCMS — Homework/Test Completion & Scores

```sql
-- Completion rate (section-level)
SELECT COUNT(sub.section_id) as total_sections,
       SUM(sub.is_section_completed) as completed_sections,
       ROUND(SUM(sub.is_section_completed) * 100.0 / COUNT(sub.section_id), 2) as completion_rate
FROM (
    SELECT ua.usrasi_student_id, ua.usrasi_section_id as section_id,
           CASE WHEN MIN(ua.usrasi_completion_state) = 1 THEN 1 ELSE 0 END AS is_section_completed
    FROM lcms_user_assignments ua
    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
    WHERE c.cou_section_type = ? AND ua.usrasi_course_id IN (/* LCMS Course IDs */)
    GROUP BY ua.usrasi_student_id, ua.usrasi_course_id, ua.usrasi_section_id
) AS sub;

-- Average score (with MAX per student per quiz, then AVG)
SELECT ROUND(AVG(sub.avg_section_score), 2) as overall_avg_score
FROM (
    SELECT ua.usrasi_student_id,
           (SELECT CASE WHEN COUNT(max_scores.highest_score) > 0
                   THEN AVG(max_scores.highest_score) ELSE NULL END
            FROM (
                SELECT MAX(CAST(ss.stusco_overall_score AS DECIMAL(10,2))) as highest_score
                FROM lcms_student_scores ss
                WHERE ss.stusco_user_id = CAST(ua.usrasi_zeus_id AS CHAR)
                  AND ss.stusco_course_id IN (
                      SELECT c2.cou_id FROM lcms_courses c2 
                      WHERE c2.cou_parent_id = ua.usrasi_section_id AND c2.cou_section_type = 3
                  )
                GROUP BY ss.stusco_course_id
            ) AS max_scores
           ) AS avg_section_score
    FROM lcms_user_assignments ua
    JOIN lcms_courses c ON ua.usrasi_section_id = c.cou_id
    WHERE c.cou_section_type IN (1, 2) AND ua.usrasi_course_id IN (/* IDs */)
    GROUP BY ua.usrasi_student_id, ua.usrasi_section_id
) AS sub
WHERE sub.avg_section_score IS NOT NULL;

-- Score distribution
SELECT
    CASE 
        WHEN score >= 90 THEN '90-100'
        WHEN score >= 80 THEN '80-89'
        WHEN score >= 70 THEN '70-79'
        WHEN score >= 60 THEN '60-69'
        ELSE 'Below 60'
    END as score_range,
    COUNT(*) as count
FROM (
    SELECT MAX(CAST(stusco_overall_score AS DECIMAL(10,2))) as score
    FROM lcms_student_scores
    WHERE stusco_course_id IN (?) AND stusco_overall_score IS NOT NULL
    GROUP BY stusco_student_id, stusco_course_id
) AS scores
GROUP BY score_range
ORDER BY FIELD(score_range, '90-100', '80-89', '70-79', '60-69', 'Below 60');

-- Enrollment overview
SELECT cs.coustu_course_id, c.cou_name, COUNT(DISTINCT cs.coustu_student_id) as enrolled
FROM lcms_course_student cs
JOIN lcms_courses c ON cs.coustu_course_id = c.cou_id
WHERE cs.coustu_course_id IN (?)
GROUP BY cs.coustu_course_id, c.cou_name;

-- Student demographics
SELECT stu_gender, COUNT(*) as count
FROM lcms_students
WHERE stu_id IN (SELECT DISTINCT coustu_student_id FROM lcms_course_student WHERE coustu_course_id IN (?))
GROUP BY stu_gender;

-- Completion trend by month
SELECT DATE_FORMAT(ua.usrasi_completion_time, '%Y-%m') as month,
       COUNT(*) as completions
FROM lcms_user_assignments ua
WHERE ua.usrasi_completion_state = 1 AND ua.usrasi_course_id IN (?)
  AND ua.usrasi_completion_time IS NOT NULL
GROUP BY month ORDER BY month;
```

### 4.27 Usage Report — Export Data

```sql
-- Main export query
SELECT o.order_id, ol.ordles_tlang_id,
       u.user_id, u.user_first_name, u.user_last_name, u.user_email,
       tl.tlang_identifier as subject_name,
       COUNT(ol.ordles_id) as item_count,
       SUM(ol.ordles_amount) as total_amount,
       SUM(COALESCE(ol.ordles_discount, 0)) as total_discount,
       SUM(CASE WHEN ol.ordles_status = 3 THEN 1 ELSE 0 END) as used_lessons,
       MIN(ol.ordles_lesson_starttime) as first_lesson_date,
       MAX(ol.ordles_lesson_endtime) as last_lesson_date,
       SUBSTRING_INDEX(GROUP_CONCAT(tc.country_code ORDER BY tc.country_code), ',', 1) as teacher_country_code,
       op.ordpay_datetime as payment_date,
       o.order_net_amount, o.order_total_amount
FROM tbl_orders o
JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
JOIN tbl_users u ON o.order_user_id = u.user_id
LEFT JOIN tbl_teach_languages tl ON ol.ordles_tlang_id = tl.tlang_id
LEFT JOIN tbl_order_payments op ON op.ordpay_order_id = o.order_id
LEFT JOIN tbl_users teacher ON ol.ordles_teacher_id = teacher.user_id
LEFT JOIN tbl_countries tc ON teacher.user_country_id = tc.country_id
WHERE o.order_payment_status = 1 AND o.order_status = 2
  AND ol.ordles_tlang_id IN (/* SPW IDs */) AND ol.ordles_status = 3
  AND ol.ordles_lesson_starttime >= ? AND ol.ordles_lesson_starttime < ?
GROUP BY o.order_id, ol.ordles_tlang_id, u.user_id, u.user_first_name,
         u.user_last_name, u.user_email, tl.tlang_identifier, op.ordpay_datetime,
         o.order_net_amount, o.order_total_amount;

-- Used before period (opening balance)
SELECT ordles_order_id, ordles_tlang_id, COUNT(*) as used_before
FROM tbl_order_lessons
WHERE ordles_status = 3 AND ordles_lesson_endtime < ?
GROUP BY ordles_order_id, ordles_tlang_id;

-- Z-Coupon received/outgoing transfers
SELECT zctran_user_id, COUNT(*) as txn_count, SUM(zctran_value_change) as total_value
FROM tbl_zcoupon_transactions
WHERE zctran_action = 1 /* received */ AND zctran_created BETWEEN ? AND ?
GROUP BY zctran_user_id;

SELECT zctran_user_id, COUNT(*) as txn_count, SUM(zctran_value_change) as total_value
FROM tbl_zcoupon_transactions
WHERE zctran_action = 2 /* outgoing */ AND zctran_created BETWEEN ? AND ?
GROUP BY zctran_user_id;
```

### 4.28 Weekly Plan — Scheduled/Unscheduled Sessions

```sql
-- Scheduled sessions by class size and teacher nationality
SELECT
    CASE WHEN gc.grpcls_total_seats = 1 OR gc.grpcls_total_seats IS NULL THEN '1v1'
         WHEN gc.grpcls_total_seats = 2 THEN '1v2'
         WHEN gc.grpcls_total_seats = 3 THEN '1v3'
         WHEN gc.grpcls_total_seats >= 4 THEN '1v8'
    END as class_size,
    CASE WHEN c.country_code = 'VN' THEN 'Vietnamese'
         WHEN c.country_code = 'PH' THEN 'Philippines'
         WHEN c.country_code = 'ZA' THEN 'Native 1'
         WHEN c.country_code = 'GB' THEN 'Native 2'
         ELSE 'Other'
    END as teacher_nationality,
    COUNT(*) as session_count
FROM tbl_order_lessons ol
JOIN tbl_orders o ON o.order_id = ol.ordles_order_id
LEFT JOIN tbl_users teacher ON teacher.user_id = ol.ordles_teacher_id
LEFT JOIN tbl_countries c ON c.country_id = teacher.user_country_id
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id,
                  MAX(grpcls_total_seats) as grpcls_total_seats
           FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc
  ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status IN (2,3,4) AND ol.ordles_tlang_id IN (?)
  AND o.order_status = 2 AND o.order_payment_status = 1
  AND ol.ordles_lesson_starttime BETWEEN ? AND ?
GROUP BY class_size, teacher_nationality;

-- Weekly summary by teacher country (CONVERT_TZ + YEARWEEK)
SELECT teacher_country_name,
       YEARWEEK(starttime_utc7, 3) as year_week,
       WEEK(starttime_utc7, 3) as week_num,
       DATE_FORMAT(MIN(DATE(starttime_utc7)) - INTERVAL WEEKDAY(MIN(DATE(starttime_utc7))) DAY, '%d/%m/%Y') AS week_start,
       COUNT(*) AS lesson_count
FROM (
    SELECT IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
           CONVERT_TZ(ordles.ordles_lesson_starttime, '+00:00', '+07:00') AS starttime_utc7
    FROM tbl_order_lessons ordles
    JOIN tbl_orders o ON ordles.ordles_order_id = o.order_id
    JOIN tbl_users teacher ON ordles.ordles_teacher_id = teacher.user_id
    LEFT JOIN tbl_countries c ON teacher.user_country_id = c.country_id
    LEFT JOIN tbl_countries_lang cl ON c.country_id = cl.countrylang_country_id AND cl.countrylang_lang_id = 1
    WHERE ordles.ordles_status IN (2,3,4) AND ordles.ordles_tlang_id IN (?)
      AND o.order_status = 2 AND o.order_payment_status = 1
) AS base
GROUP BY teacher_country_name, YEARWEEK(starttime_utc7, 3)
ORDER BY teacher_country_name, year_week;

-- Active students count
SELECT COUNT(DISTINCT o.order_user_id) as active_students
FROM tbl_orders o
JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
WHERE o.order_status = 2 AND o.order_payment_status = 1
  AND ol.ordles_status IN (1, 2) AND ol.ordles_tlang_id IN (?);

-- Active students by class type
SELECT CASE WHEN gc.grpcls_total_seats = 2 THEN '1:2' ELSE '1:1' END AS class_type,
       COUNT(DISTINCT o.order_user_id) as student_count
FROM tbl_orders o
JOIN tbl_order_lessons ol ON ol.ordles_order_id = o.order_id
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id,
                  MAX(grpcls_total_seats) as grpcls_total_seats
           FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc
  ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE o.order_status = 2 AND o.order_payment_status = 1
  AND ol.ordles_status IN (1, 2) AND ol.ordles_tlang_id IN (?)
GROUP BY class_type;

-- Unscheduled by country and class type
SELECT IFNULL(cl.country_name, c.country_identifier) AS teacher_country_name,
       CASE WHEN gc.grpcls_total_seats = 2 THEN '1:2' ELSE '1:1' END AS class_type,
       COUNT(ol.ordles_id) AS unscheduled_count
FROM tbl_order_lessons ol
JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
LEFT JOIN tbl_users teacher ON ol.ordles_teacher_id = teacher.user_id
LEFT JOIN tbl_countries c ON teacher.user_country_id = c.country_id
LEFT JOIN tbl_countries_lang cl ON c.country_id = cl.countrylang_country_id AND cl.countrylang_lang_id = 1
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id,
                  MAX(grpcls_total_seats) as grpcls_total_seats
           FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc
  ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status = 1 AND ol.ordles_tlang_id IN (?)
  AND o.order_status = 2 AND o.order_payment_status = 1
GROUP BY c.country_id, teacher_country_name, class_type;

-- Scheduled by week and class type
SELECT YEARWEEK(CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00'), 3) as year_week,
       CASE WHEN gc.grpcls_total_seats = 2 THEN '1:2' ELSE '1:1' END AS class_type,
       COUNT(*) as session_count
FROM tbl_order_lessons ol
JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
LEFT JOIN (SELECT grpcls_tlang_id, grpcls_teacher_id,
                  MAX(grpcls_total_seats) as grpcls_total_seats
           FROM tbl_group_classes GROUP BY grpcls_tlang_id, grpcls_teacher_id) gc
  ON gc.grpcls_tlang_id = ol.ordles_tlang_id AND gc.grpcls_teacher_id = ol.ordles_teacher_id
WHERE ol.ordles_status IN (2,3,4) AND ol.ordles_tlang_id IN (?)
  AND o.order_status = 2 AND o.order_payment_status = 1
  AND ol.ordles_lesson_starttime BETWEEN ? AND ?
GROUP BY year_week, class_type
ORDER BY year_week;
```

### 4.29 Authentication

```sql
-- Admin login
SELECT * FROM tbl_admin
WHERE admin_email = ? AND admin_password = ? AND admin_active = 1 LIMIT 1;

-- Admin roles
SELECT admrole_role_id FROM tbl_admin_roles WHERE admrole_admin_id = ?;

-- Admin permissions
SELECT * FROM tbl_admin_permissions
WHERE admperm_admin_id = ?;

-- Role names
SELECT role_name FROM tbl_roles_lang
WHERE rolelang_role_id IN (?) AND rolelang_lang_id = 1;
```

### 4.30 Dynamic Configuration

```sql
-- SPEAKWELL subject IDs
SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS';

-- Trial subject ID
SELECT conf_val FROM tbl_configurations WHERE conf_name = 'CONF_TRIAL_SUBJECT_ID';

-- FIND_IN_SET pattern (dùng trong CSI)
FIND_IN_SET(l.ordles_tlang_id, 
    (SELECT REPLACE(conf_val,' ','') FROM tbl_configurations 
     WHERE conf_name='CONF_SPEAKWELL_SUBJECT_IDS' LIMIT 1))
```

---

## Phụ lục: SQL Patterns thường dùng

| Pattern | Mục đích | Ví dụ |
|---|---|---|
| `CONVERT_TZ(dt, '+00:00', '+07:00')` | UTC → UTC+7 | Hiển thị giờ Việt Nam |
| `WITH ... AS (CTE)` | Common Table Expressions | CSI health scoring, Teacher change |
| `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY ...)` | Window function xếp hạng | First-3 lessons, First order |
| `LAG() OVER (PARTITION BY ... ORDER BY ...)` | Window function so sánh row trước | Teacher change detection |
| `CASE WHEN ... THEN ... ELSE ... END` | Phân loại điều kiện | Class size, Program, Health |
| `FIND_IN_SET(col, string)` | Kiểm tra giá trị trong danh sách | SpeakWell subject filter |
| `TIMESTAMPDIFF(MINUTE, dt1, dt2)` | Chênh lệch thời gian (phút) | Late entry, Awaiting data |
| `DATE_ADD(dt, INTERVAL n UNIT)` | Cộng thời gian | Lesson end time |
| `DATE_SUB(dt, INTERVAL n UNIT)` | Trừ thời gian | Urgent cancellation (< 24h) |
| `YEARWEEK(dt, 3)` | ISO-8601 week number | Weekly Plan, CSI trends |
| `JSON_EXTRACT(col, '$.path')` | Trích xuất JSON field | Leave session info, Feedback |
| `JSON_UNQUOTE(JSON_EXTRACT(...))` | Trích xuất JSON (bỏ quotes) | Learner ID from leave session |
| `JSON_TABLE(col, path COLUMNS(...))` | JSON → table | Trial feedback assessment |
| `GROUP_CONCAT(... SEPARATOR ', ')` | Nối chuỗi nhóm | Student names, Course names |
| `SUBSTRING_INDEX(GROUP_CONCAT(...), ',', 1)` | Lấy phần tử đầu | Teacher country code |
| `EXISTS (SELECT 1 FROM ...)` | Kiểm tra tồn tại | Urgent cancellation, Active check |
| `FIELD(col, 'a', 'b', 'c')` | Sắp xếp theo thứ tự chỉ định | Class size ordering |
