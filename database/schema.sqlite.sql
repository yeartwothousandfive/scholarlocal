-- ScholarLocal SQLite Schema & Seed Data
-- Zero-config database for local testing or lightweight deployments

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  student_id TEXT NOT NULL UNIQUE,
  program TEXT NOT NULL DEFAULT 'DIT',
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'student' CHECK (role IN ('student','faculty','admin','super_admin')),
  account_status TEXT NOT NULL DEFAULT 'active' CHECK (account_status IN ('active','pending','suspended')),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS studies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  authors TEXT NOT NULL,
  year INTEGER NOT NULL,
  department TEXT NOT NULL DEFAULT 'PUP Institute of Technology',
  program TEXT NOT NULL CHECK (program IN ('DIT','DCvET','DCET','DEET','DECET','DMET','DOMT','DRET')),
  abstract TEXT NOT NULL,
  keywords TEXT NOT NULL DEFAULT '',
  extracted_text TEXT DEFAULT '',
  pdf_filename TEXT DEFAULT NULL,
  file_status TEXT NOT NULL DEFAULT 'available' CHECK (file_status IN ('available','metadata-only')),
  review_status TEXT NOT NULL DEFAULT 'approved' CHECK (review_status IN ('approved','pending','revision')),
  view_count INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS download_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  study_id INTEGER NOT NULL REFERENCES studies(id) ON DELETE CASCADE,
  study_title TEXT NOT NULL,
  purpose TEXT NOT NULL,
  academic_use_accepted INTEGER NOT NULL DEFAULT 1,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','denied')),
  review_note TEXT DEFAULT NULL,
  reviewed_by INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
  reviewed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER DEFAULT NULL,
  action TEXT NOT NULL,
  target_type TEXT NOT NULL DEFAULT '',
  target_id INTEGER DEFAULT NULL,
  details TEXT NOT NULL DEFAULT '',
  ip_address TEXT NOT NULL DEFAULT '127.0.0.1',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_settings (
  setting_key TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO users (full_name, student_id, program, email, password_hash, role, account_status) VALUES
('Office of the Director', 'ADMIN-0001', 'STAFF', 'director@pup.edu.ph', 'SEED:Admin@2026!', 'super_admin', 'active'),
('Repository Office', 'ADMIN-0002', 'STAFF', 'repo.admin@pup.edu.ph', 'SEED:Admin@2026!', 'admin', 'active'),
('Instr. Marisol D. Payra', 'FAC-00105', 'DIT', 'prof.payra@pup.edu.ph', 'SEED:Faculty@2026!', 'faculty', 'active'),
('Juan M. Dela Cruz', '2024-00128-MN-0', 'DIT', 'juan.delacruz@iskolarngbayan.pup.edu.ph', 'SEED:Student@2026!', 'student', 'active'),
('Angela M. Reyes', '2024-00129-MN-0', 'DCET', 'angela.reyes@iskolarngbayan.pup.edu.ph', 'SEED:Student@2026!', 'student', 'active');

INSERT OR IGNORE INTO studies (slug, title, authors, year, department, program, abstract, keywords, extracted_text, pdf_filename, file_status, review_status, view_count) VALUES
('scholarlocal-tfidf-cosine-repository', 'ScholarLocal: A Web-Based Research Repository with TF-IDF and Cosine Similarity for PUP Institute of Technology Students', 'Baldomero, Kier Justine D., Carlos, Sebastian Paul S., Infante, Psalms D., Magpantay, Sopia Angela M., Saja, Vince Stanley P., Suarez, Elijah Joel P.', 2026, 'PUP Institute of Technology', 'DIT', 'This study develops ScholarLocal, a centralized web-based research repository exclusively for approved PUP Institute of Technology undergraduate capstone and thesis projects. The platform integrates automated PDF text extraction, stop-word removal, stemming, n-grams, TF-IDF vectorization, Cosine Similarity ranking, and Role-Based Access Control (RBAC) with dynamic PDF watermarking.', 'TF-IDF, Cosine Similarity, Research Repository, RBAC, NLP, PUP ITECH', 'TF-IDF vector space model cosine similarity research repository PUP ITECH capstone thesis PDF extraction OCR RBAC watermarking ISO 25010 SUS', 'study-01-scholarlocal-tfidf.pdf', 'available', 'approved', 214),
('iot-smart-hydroponics-monitoring-dit', 'IoT-Based Automated Nutrient and pH Monitoring System for Urban Hydroponics Using ESP32 and Machine Learning', 'Santos, Mark Anthony R., Villanueva, Nicole T., Bautista, Carlo J.', 2025, 'PUP Institute of Technology', 'DIT', 'An Internet of Things (IoT) monitoring and actuation system built on ESP32 microcontrollers to track pH, electrical conductivity, water temperature, and dissolved oxygen in urban hydroponic farms, paired with a lightweight machine learning anomaly detector.', 'IoT Systems, ESP32, Machine Learning, Hydroponics, Sensors', 'IoT smart hydroponics ESP32 pH electrical conductivity machine learning anomaly detection sensor telemetry dashboard', 'study-02-iot-hydroponics.pdf', 'available', 'approved', 168),
('solar-microgrid-load-forecasting-deet', 'Solar Microgrid Load Forecasting and Battery Dispatch Optimization for Campus Buildings', 'Diaz, Engr. Ramon C., Mendoza, Patricia L., Torres, Kevin G.', 2024, 'PUP Institute of Technology', 'DEET', 'This capstone evaluates time-series load forecasting and lithium-iron-phosphate battery dispatch scheduling for a campus solar photovoltaic microgrid, reducing peak grid demand by 23.4% across academic semesters.', 'Renewable Energy, Solar Microgrid, Load Forecasting, Battery Optimization', 'solar photovoltaic microgrid load forecasting battery dispatch optimization peak shaving campus energy management', 'study-03-solar-microgrid.pdf', 'available', 'approved', 142),
('railway-track-defect-vision-dret', 'Automated Railway Track Fastener and Surface Defect Detection Using Convolutional Neural Networks', 'Cruz, Mikaela P., Navarro, Adrian V., Lim, Marco S.', 2025, 'PUP Institute of Technology', 'DRET', 'A computer-vision inspection prototype for urban light rail tracks that identifies missing rail fasteners, hairline cracks, and ballast fouling from track-side camera frames in real time.', 'Railway Safety, Machine Learning, Computer Vision, Defect Detection, CNN', 'railway safety track defect detection convolutional neural network rail fastener inspection preventive maintenance', 'study-04-railway-defect-cnn.pdf', 'available', 'approved', 155),
('smart-flood-early-warning-dcvet', 'Ultrasonic and LoRaWAN-Based Urban Flood Early Warning System for Low-Lying Metro Manila Barangays', 'Ramos, Luis R., Garcia, Bea K., Aquino, Jerome D.', 2024, 'PUP Institute of Technology', 'DCvET', 'Designs a solar-powered ultrasonic water-level telemetry node communicating over LoRaWAN to alert barangay disaster response teams when drainage canals approach critical overflow thresholds.', 'IoT Systems, LoRaWAN, Flood Monitoring, Civil Engineering, Early Warning', 'flood early warning ultrasonic sensor LoRaWAN drainage telemetry disaster risk reduction Metro Manila', 'study-05-flood-lorawan.pdf', 'available', 'approved', 129),
('fpga-edge-ecg-arrhythmia-dcet', 'Low-Power Edge Computing Prototype for Real-Time ECG Arrhythmia Classification on Embedded ARM', 'Flores, Christine L., Pascual, Miguel A., Reyes, Paolo R.', 2024, 'PUP Institute of Technology', 'DCET', 'Implements a quantized 1D neural network on an embedded ARM Cortex microcontroller to classify cardiac arrhythmias from wearable ECG leads without continuous cloud connectivity.', 'Machine Learning, Embedded Systems, Edge Computing, ECG, Healthcare', 'embedded ARM Cortex edge computing ECG arrhythmia classification wearable sensor low power', 'study-06-edge-ecg.pdf', 'available', 'approved', 118),
('automated-pcb-solder-inspection-decet', 'Optical Automated Solder Joint Inspection Bench Using OpenCV for Electronics Prototyping Labs', 'Villareal, Ana P., Soriano, Gabriel M., Castro, Denise F.', 2023, 'PUP Institute of Technology', 'DECET', 'Constructs a low-cost optical inspection rig with ring-light illumination and OpenCV contour analysis to detect solder bridges and cold joints on student-fabricated PCBs.', 'Electronics, OpenCV, PCB Inspection, Quality Control, Automation', 'optical solder joint inspection OpenCV PCB quality control electronics laboratory automation', 'study-07-pcb-inspection.pdf', 'available', 'approved', 94),
('document-workflow-tracking-domt', 'Digital Records Routing and Turnaround Analytics System for Academic Department Offices', 'Ocampo, Bianca S., Evangelista, Joshua R., Mercado, Trixie N.', 2023, 'PUP Institute of Technology', 'DOMT', 'Streamlines physical and digital memorandum routing, endorsement tracking, and turnaround-time reporting for university administrative offices using barcode-tagged dossiers.', 'Office Management, Records Tracking, Workflow Automation, Analytics', 'digital records routing turnaround analytics academic office management barcode document tracking', NULL, 'metadata-only', 'approved', 87);

INSERT OR IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('public_repository_access', 'enabled', 'Allow visitors to browse approved study metadata and abstracts'),
('student_self_registration', 'enabled', 'Accept verified PUP institutional email accounts'),
('submission_review_required', 'enabled', 'Require administrator approval before publishing new studies'),
('pdf_watermark_enabled', 'enabled', 'Apply dynamic user-watermarking in the protected PDF viewer');
