-- ScholarLocal MySQL / MariaDB Schema & Seed Data
-- PUP Institute of Technology Research Repository

CREATE DATABASE IF NOT EXISTS `scholarlocal_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `scholarlocal_db`;

-- Users table (Students, Faculty, Content Admins, Super Admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `student_id` VARCHAR(40) NOT NULL,
  `program` ENUM('DIT','DCvET','DCET','DEET','DECET','DMET','DOMT','DRET','STAFF') NOT NULL DEFAULT 'DIT',
  `email` VARCHAR(160) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student','faculty','admin','super_admin') NOT NULL DEFAULT 'student',
  `account_status` ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Research studies table
CREATE TABLE IF NOT EXISTS `studies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(180) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `authors` VARCHAR(255) NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `department` VARCHAR(120) NOT NULL DEFAULT 'PUP Institute of Technology',
  `program` ENUM('DIT','DCvET','DCET','DEET','DECET','DMET','DOMT','DRET') NOT NULL,
  `abstract` TEXT NOT NULL,
  `keywords` VARCHAR(500) NOT NULL DEFAULT '',
  `extracted_text` MEDIUMTEXT NULL,
  `pdf_filename` VARCHAR(255) NULL,
  `file_status` ENUM('available','metadata-only') NOT NULL DEFAULT 'available',
  `review_status` ENUM('approved','pending','revision') NOT NULL DEFAULT 'approved',
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_studies_slug` (`slug`),
  KEY `idx_studies_program_year` (`program`, `year`),
  CONSTRAINT `fk_studies_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Controlled PDF download requests table
CREATE TABLE IF NOT EXISTS `download_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `study_id` INT UNSIGNED NOT NULL,
  `study_title` VARCHAR(255) NOT NULL,
  `purpose` TEXT NOT NULL,
  `academic_use_accepted` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `review_note` TEXT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requests_user_study` (`user_id`, `study_id`),
  KEY `idx_requests_status` (`status`),
  CONSTRAINT `fk_req_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_study` FOREIGN KEY (`study_id`) REFERENCES `studies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security & RBAC audit log table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(80) NOT NULL,
  `target_type` VARCHAR(40) NOT NULL DEFAULT '',
  `target_id` INT UNSIGNED NULL,
  `details` VARCHAR(255) NOT NULL DEFAULT '',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed accounts (SEED: prefix is automatically hashed with PHP PASSWORD_BCRYPT on first login)
INSERT INTO `users` (`full_name`, `student_id`, `program`, `email`, `password_hash`, `role`, `account_status`) VALUES
('Office of the Director', 'ADMIN-0001', 'STAFF', 'director@pup.edu.ph', 'SEED:Admin@2026!', 'super_admin', 'active'),
('Repository Office', 'ADMIN-0002', 'STAFF', 'repo.admin@pup.edu.ph', 'SEED:Admin@2026!', 'admin', 'active'),
('Instr. Marisol D. Payra', 'FAC-00105', 'DIT', 'prof.payra@pup.edu.ph', 'SEED:Faculty@2026!', 'faculty', 'active'),
('Juan M. Dela Cruz', '2024-00128-MN-0', 'DIT', 'juan.delacruz@iskolarngbayan.pup.edu.ph', 'SEED:Student@2026!', 'student', 'active'),
('Angela M. Reyes', '2024-00129-MN-0', 'DCET', 'angela.reyes@iskolarngbayan.pup.edu.ph', 'SEED:Student@2026!', 'student', 'active');

-- Seed approved PUP ITECH studies
INSERT INTO `studies` (`slug`, `title`, `authors`, `year`, `department`, `program`, `abstract`, `keywords`, `extracted_text`, `pdf_filename`, `file_status`, `review_status`, `view_count`) VALUES
('scholarlocal-tfidf-cosine-repository', 'ScholarLocal: A Web-Based Research Repository with TF-IDF and Cosine Similarity for PUP Institute of Technology Students', 'Baldomero, Kier Justine D., Carlos, Sebastian Paul S., Infante, Psalms D., Magpantay, Sopia Angela M., Saja, Vince Stanley P., Suarez, Elijah Joel P.', 2026, 'PUP Institute of Technology', 'DIT', 'This study develops ScholarLocal, a centralized web-based research repository exclusively for approved PUP Institute of Technology undergraduate capstone and thesis projects. The platform integrates automated PDF text extraction, stop-word removal, stemming, n-grams, TF-IDF vectorization, Cosine Similarity ranking, and Role-Based Access Control (RBAC) with dynamic PDF watermarking.', 'TF-IDF, Cosine Similarity, Research Repository, RBAC, NLP, PUP ITECH', 'TF-IDF vector space model cosine similarity research repository PUP ITECH capstone thesis PDF extraction OCR RBAC watermarking ISO 25010 SUS', 'study-01-scholarlocal-tfidf.pdf', 'available', 'approved', 214),
('iot-smart-hydroponics-monitoring-dit', 'IoT-Based Automated Nutrient and pH Monitoring System for Urban Hydroponics Using ESP32 and Machine Learning', 'Santos, Mark Anthony R., Villanueva, Nicole T., Bautista, Carlo J.', 2025, 'PUP Institute of Technology', 'DIT', 'An Internet of Things (IoT) monitoring and actuation system built on ESP32 microcontrollers to track pH, electrical conductivity, water temperature, and dissolved oxygen in urban hydroponic farms, paired with a lightweight machine learning anomaly detector.', 'IoT Systems, ESP32, Machine Learning, Hydroponics, Sensors', 'IoT smart hydroponics ESP32 pH electrical conductivity machine learning anomaly detection sensor telemetry dashboard', 'study-02-iot-hydroponics.pdf', 'available', 'approved', 168),
('solar-microgrid-load-forecasting-deet', 'Solar Microgrid Load Forecasting and Battery Dispatch Optimization for Campus Buildings', 'Diaz, Engr. Ramon C., Mendoza, Patricia L., Torres, Kevin G.', 2024, 'PUP Institute of Technology', 'DEET', 'This capstone evaluates time-series load forecasting and lithium-iron-phosphate battery dispatch scheduling for a campus solar photovoltaic microgrid, reducing peak grid demand by 23.4% across academic semesters.', 'Renewable Energy, Solar Microgrid, Load Forecasting, Battery Optimization', 'solar photovoltaic microgrid load forecasting battery dispatch optimization peak shaving campus energy management', 'study-03-solar-microgrid.pdf', 'available', 'approved', 142),
('railway-track-defect-vision-dret', 'Automated Railway Track Fastener and Surface Defect Detection Using Convolutional Neural Networks', 'Cruz, Mikaela P., Navarro, Adrian V., Lim, Marco S.', 2025, 'PUP Institute of Technology', 'DRET', 'A computer-vision inspection prototype for urban light rail tracks that identifies missing rail fasteners, hairline cracks, and ballast fouling from track-side camera frames in real time.', 'Railway Safety, Machine Learning, Computer Vision, Defect Detection, CNN', 'railway safety track defect detection convolutional neural network rail fastener inspection preventive maintenance', 'study-04-railway-defect-cnn.pdf', 'available', 'approved', 155),
('smart-flood-early-warning-dcvet', 'Ultrasonic and LoRaWAN-Based Urban Flood Early Warning System for Low-Lying Metro Manila Barangays', 'Ramos, Luis R., Garcia, Bea K., Aquino, Jerome D.', 2024, 'PUP Institute of Technology', 'DCvET', 'Designs a solar-powered ultrasonic water-level telemetry node communicating over LoRaWAN to alert barangay disaster response teams when drainage canals approach critical overflow thresholds.', 'IoT Systems, LoRaWAN, Flood Monitoring, Civil Engineering, Early Warning', 'flood early warning ultrasonic sensor LoRaWAN drainage telemetry disaster risk reduction Metro Manila', 'study-05-flood-lorawan.pdf', 'available', 'approved', 129),
('fpga-edge-ecg-arrhythmia-dcet', 'Low-Power Edge Computing Prototype for Real-Time ECG Arrhythmia Classification on Embedded ARM', 'Flores, Christine L., Pascual, Miguel A., Reyes, Paolo R.', 2024, 'PUP Institute of Technology', 'DCET', 'Implements a quantized 1D neural network on an embedded ARM Cortex microcontroller to classify cardiac arrhythmias from wearable ECG leads without continuous cloud connectivity.', 'Machine Learning, Embedded Systems, Edge Computing, ECG, Healthcare', 'embedded ARM Cortex edge computing ECG arrhythmia classification wearable sensor low power', 'study-06-edge-ecg.pdf', 'available', 'approved', 118),
('automated-pcb-solder-inspection-decet', 'Optical Automated Solder Joint Inspection Bench Using OpenCV for Electronics Prototyping Labs', 'Villareal, Ana P., Soriano, Gabriel M., Castro, Denise F.', 2023, 'PUP Institute of Technology', 'DECET', 'Constructs a low-cost optical inspection rig with ring-light illumination and OpenCV contour analysis to detect solder bridges and cold joints on student-fabricated PCBs.', 'Electronics, OpenCV, PCB Inspection, Quality Control, Automation', 'optical solder joint inspection OpenCV PCB quality control electronics laboratory automation', 'study-07-pcb-inspection.pdf', 'available', 'approved', 94),
('document-workflow-tracking-domt', 'Digital Records Routing and Turnaround Analytics System for Academic Department Offices', 'Ocampo, Bianca S., Evangelista, Joshua R., Mercado, Trixie N.', 2023, 'PUP Institute of Technology', 'DOMT', 'Streamlines physical and digital memorandum routing, endorsement tracking, and turnaround-time reporting for university administrative offices using barcode-tagged dossiers.', 'Office Management, Records Tracking, Workflow Automation, Analytics', 'digital records routing turnaround analytics academic office management barcode document tracking', NULL, 'metadata-only', 'approved', 87);

-- Seed system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('public_repository_access', 'enabled', 'Allow visitors to browse approved study metadata and abstracts'),
('student_self_registration', 'enabled', 'Accept verified PUP institutional email accounts'),
('submission_review_required', 'enabled', 'Require administrator approval before publishing new studies'),
('pdf_watermark_enabled', 'enabled', 'Apply dynamic user-watermarking in the protected PDF viewer');
