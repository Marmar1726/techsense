-- ============================================================
-- TechSense: Intelligent IT Problem Analysis System
-- Database Schema + Seed Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS techsense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE techsense;

-- ============================================================
-- TABLE: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(60) DEFAULT 'bi-cpu',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: problems
-- ============================================================
CREATE TABLE IF NOT EXISTS problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    problem_name VARCHAR(200) NOT NULL,
    description TEXT,
    difficulty ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    estimated_time VARCHAR(50) DEFAULT '5-15 minutes',
    warning TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: symptoms
-- ============================================================
CREATE TABLE IF NOT EXISTS symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symptom_name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: problem_symptoms (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS problem_symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    symptom_id INT NOT NULL,
    UNIQUE KEY unique_ps (problem_id, symptom_id),
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE,
    FOREIGN KEY (symptom_id) REFERENCES symptoms(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: causes
-- ============================================================
CREATE TABLE IF NOT EXISTS causes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    cause_description VARCHAR(255) NOT NULL,
    order_num INT DEFAULT 1,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: troubleshooting_steps
-- ============================================================
CREATE TABLE IF NOT EXISTS troubleshooting_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    step_number INT NOT NULL,
    instruction TEXT NOT NULL,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: solutions
-- ============================================================
CREATE TABLE IF NOT EXISTS solutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    solution_description TEXT NOT NULL,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: troubleshooting_sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS troubleshooting_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    problem_id INT,
    category_id INT,
    user_description TEXT,
    selected_symptoms TEXT,
    match_score DECIMAL(5,2) DEFAULT 0,
    result ENUM('resolved','unresolved','in_progress') DEFAULT 'in_progress',
    status ENUM('active','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: feedback
-- ============================================================
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    rating ENUM('helpful','partial','not_helpful') NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES troubleshooting_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED: Settings
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'TechSense'),
('admin_email', 'admin@techsense.local'),
('session_timeout', '3600'),
('items_per_page', '10');

-- ============================================================
-- SEED: Users
-- admin password: Admin@123
-- user password:  User@123
-- ============================================================
INSERT INTO users (full_name, email, username, password, role, status) VALUES
('System Administrator', 'admin@techsense.local', 'admin',
 '$2y$10$2Pq4a8cXuiBr5xRFpT7.fOGSytoUzSOcNqDUT3mt1LDvIyONiIk0C', 'admin', 'active'),
('Juan dela Cruz', 'juan@example.com', 'testuser',
 '$2y$10$qJjAP4kI/7jhYamUOsVNuubXnZbNZlq0a9euNVkRhdjJ3iTxpGu8e', 'user', 'active'),
('Maria Santos', 'maria@example.com', 'maria_s',
 '$2y$10$qJjAP4kI/7jhYamUOsVNuubXnZbNZlq0a9euNVkRhdjJ3iTxpGu8e', 'user', 'active');

-- ============================================================
-- SEED: Categories
-- ============================================================
INSERT INTO categories (id, category_name, description, icon, status) VALUES
(1, 'Computer Hardware', 'Issues related to physical computer components such as motherboard, RAM, CPU, power supply, and storage devices.', 'bi-cpu-fill', 'active'),
(2, 'Operating System', 'Problems with Windows, Linux, or macOS including startup issues, crashes, and system errors.', 'bi-windows', 'active'),
(3, 'Software/Application', 'Issues with installed software, applications, games, or programs that fail to run properly.', 'bi-app-indicator', 'active'),
(4, 'Internet/Network', 'Problems with Wi-Fi, LAN, internet connectivity, DNS, and network configuration.', 'bi-wifi', 'active'),
(5, 'Printer', 'Printer-related issues including offline status, paper jams, and poor print quality.', 'bi-printer-fill', 'active'),
(6, 'Storage Devices', 'Issues with hard drives, SSDs, USB flash drives, SD cards, and external storage.', 'bi-device-hdd-fill', 'active'),
(7, 'Peripheral Devices', 'Problems with keyboards, mice, monitors, webcams, speakers, and other external devices.', 'bi-keyboard-fill', 'active'),
(8, 'Security', 'Virus infections, malware, unauthorized access, firewall, and cybersecurity issues.', 'bi-shield-lock-fill', 'active'),
(9, 'Other IT Problems', 'General IT issues that do not fall under a specific category.', 'bi-tools', 'active');

-- ============================================================
-- SEED: Symptoms
-- ============================================================
-- Hardware Symptoms (cat 1)
INSERT INTO symptoms (id, symptom_name, category_id) VALUES
(1,  'Computer does not turn on', 1),
(2,  'No display on monitor', 1),
(3,  'Computer is running slow', 1),
(4,  'Computer suddenly shuts down', 1),
(5,  'Computer is overheating', 1),
(6,  'Blue Screen of Death (BSOD)', 1),
(7,  'Strange noises from computer', 1),
(8,  'RAM not detected', 1),
(9,  'Computer restarts randomly', 1),
(10, 'Burning smell from computer', 1),
-- OS Symptoms (cat 2)
(11, 'System boots very slowly', 2),
(12, 'Windows update failed', 2),
(13, 'Startup error or loop', 2),
(14, 'Blue screen error', 2),
(15, 'System freezes or hangs', 2),
(16, 'Cannot log in to Windows', 2),
(17, 'Missing system files', 2),
(18, 'Black screen after startup', 2),
-- Software Symptoms (cat 3)
(19, 'Application crashes on launch', 3),
(20, 'Application will not open', 3),
(21, 'Software installation failed', 3),
(22, 'Application freezes or hangs', 3),
(23, 'Error messages on startup', 3),
(24, 'Missing DLL or runtime error', 3),
(25, 'Application runs very slowly', 3),
-- Network Symptoms (cat 4)
(26, 'Cannot connect to Wi-Fi', 4),
(27, 'Slow internet connection', 4),
(28, 'No internet connection', 4),
(29, 'Cannot access a specific website', 4),
(30, 'Network cable not detected', 4),
(31, 'Frequent disconnection from internet', 4),
(32, 'IP address conflict', 4),
-- Printer Symptoms (cat 5)
(33, 'Printer not printing', 5),
(34, 'Paper jam', 5),
(35, 'Printer shows offline', 5),
(36, 'Poor or faded print quality', 5),
(37, 'Printer not detected by computer', 5),
(38, 'Ink cartridge error', 5),
-- Storage Symptoms (cat 6)
(39, 'HDD/SSD not detected', 6),
(40, 'USB drive not detected', 6),
(41, 'Storage almost full', 6),
(42, 'File cannot be accessed or corrupted', 6),
(43, 'Slow read/write speed', 6),
(44, 'Drive making clicking sounds', 6),
-- Peripheral Symptoms (cat 7)
(45, 'Keyboard not working', 7),
(46, 'Mouse not responding', 7),
(47, 'No sound from speakers', 7),
(48, 'Monitor flickering or no signal', 7),
(49, 'Webcam not detected', 7),
(50, 'USB device not recognized', 7),
-- Security Symptoms (cat 8)
(51, 'Virus or malware detected', 8),
(52, 'Computer running unusually slow', 8),
(53, 'Suspicious pop-up messages', 8),
(54, 'Browser redirecting to unknown sites', 8),
(55, 'Cannot access files (ransomware)', 8),
(56, 'Antivirus disabled automatically', 8),
-- Other Symptoms (cat 9)
(57, 'General performance issues', 9),
(58, 'Unknown error messages', 9),
(59, 'System clock incorrect', 9),
(60, 'Device driver problems', 9);

-- ============================================================
-- SEED: Problems (25 problems across all categories)
-- ============================================================
INSERT INTO problems (id, category_id, problem_name, description, difficulty, estimated_time, warning, status) VALUES
-- HARDWARE
(1,  1, 'Computer Does Not Turn On',
 'The computer has no power response when the power button is pressed. No fans spin, no lights turn on.',
 'Beginner', '10-20 minutes',
 'Ensure the computer is fully unplugged before opening the case or touching internal components.', 'active'),

(2,  1, 'No Display on Monitor',
 'The computer powers on (fans spin, lights activate) but nothing appears on the monitor.',
 'Beginner', '10-15 minutes',
 'Avoid touching internal components without proper anti-static precautions.', 'active'),

(3,  1, 'Computer Overheating',
 'The computer generates excessive heat causing thermal throttling, sudden shutdowns, or fan noise.',
 'Intermediate', '20-40 minutes',
 'Allow the computer to cool completely before opening the case.', 'active'),

(4,  1, 'RAM Not Detected',
 'The system does not boot or reports less RAM than installed. Memory-related POST errors occur.',
 'Intermediate', '15-25 minutes',
 'Handle RAM modules carefully. Touch a grounded metal surface first to discharge static electricity.', 'active'),

(5,  1, 'Random Shutdown',
 'The computer shuts down unexpectedly without warning, often during heavy tasks.',
 'Intermediate', '20-30 minutes',
 'Do not use a computer that shuts down during hardware checks — could indicate power supply failure.', 'active'),

-- OPERATING SYSTEM
(6,  2, 'Windows Starts Very Slowly',
 'Windows takes an unusually long time to boot to the desktop.',
 'Beginner', '15-30 minutes', NULL, 'active'),

(7,  2, 'Blue Screen of Death (BSOD)',
 'Windows displays a blue error screen and restarts. May show error codes like IRQL_NOT_LESS_OR_EQUAL.',
 'Advanced', '30-60 minutes',
 'Note the error code displayed on the BSOD before the computer restarts.', 'active'),

(8,  2, 'Windows Update Failed',
 'Windows Update fails to download or install, showing error codes or getting stuck.',
 'Intermediate', '20-45 minutes', NULL, 'active'),

(9,  2, 'Windows Startup Loop or Error',
 'Windows cannot complete startup and repeatedly restarts or shows a startup repair screen.',
 'Advanced', '30-60 minutes',
 'Backup important data if possible before performing system repair.', 'active'),

(10, 2, 'Missing or Corrupted System Files',
 'Windows reports missing DLL files, corrupted registry, or fails to load system components.',
 'Intermediate', '20-40 minutes', NULL, 'active'),

-- SOFTWARE
(11, 3, 'Application Crashes on Launch',
 'A software application closes immediately or crashes shortly after being opened.',
 'Beginner', '10-20 minutes', NULL, 'active'),

(12, 3, 'Software Installation Failed',
 'A program fails to install and shows an error message or rolls back the installation.',
 'Beginner', '15-25 minutes', NULL, 'active'),

(13, 3, 'Application Freezes or Hangs',
 'A running application stops responding to input and shows "Not Responding" in the title bar.',
 'Beginner', '5-15 minutes', NULL, 'active'),

-- NETWORK
(14, 4, 'Cannot Connect to Wi-Fi',
 'The computer cannot connect to any Wi-Fi network or the network adapter is not working.',
 'Beginner', '10-20 minutes', NULL, 'active'),

(15, 4, 'Slow Internet Connection',
 'Websites load slowly, video streaming buffers, and downloads take much longer than expected.',
 'Beginner', '10-25 minutes', NULL, 'active'),

(16, 4, 'No Internet Connection (Ethernet)',
 'The computer is connected via Ethernet cable but cannot access the internet.',
 'Intermediate', '15-30 minutes', NULL, 'active'),

(17, 4, 'Cannot Access a Specific Website',
 'Most websites work but one or more specific websites cannot be reached.',
 'Beginner', '5-15 minutes', NULL, 'active'),

-- PRINTER
(18, 5, 'Printer Not Printing',
 'The printer accepts print jobs but nothing comes out, or jobs are stuck in the print queue.',
 'Beginner', '10-20 minutes', NULL, 'active'),

(19, 5, 'Printer Paper Jam',
 'Paper gets stuck inside the printer and must be manually removed.',
 'Beginner', '5-15 minutes',
 'Turn off the printer before attempting to remove jammed paper to avoid damage.', 'active'),

(20, 5, 'Printer Shows Offline',
 'The printer status shows "Offline" in Windows even though it is powered on and connected.',
 'Beginner', '10-15 minutes', NULL, 'active'),

-- STORAGE
(21, 6, 'HDD/SSD Not Detected',
 'The BIOS or operating system does not detect an internal or external hard drive or SSD.',
 'Intermediate', '15-30 minutes',
 'Backup data immediately if the drive is intermittently detected, as it may be failing.', 'active'),

(22, 6, 'USB Drive Not Detected',
 'A USB flash drive is not recognized when plugged in. No drive letter appears in File Explorer.',
 'Beginner', '5-15 minutes', NULL, 'active'),

(23, 6, 'Storage Almost Full',
 'The hard drive or SSD is nearly full, causing the system to slow down or show low disk space warnings.',
 'Beginner', '10-30 minutes', NULL, 'active'),

-- SECURITY
(24, 8, 'Virus or Malware Infection',
 'The computer behaves abnormally: slow performance, suspicious pop-ups, unknown programs, or browser redirects.',
 'Intermediate', '30-60 minutes',
 'Disconnect from the internet immediately if you suspect an active malware infection to prevent data theft.', 'active'),

-- PERIPHERAL
(25, 7, 'No Sound from Speakers or Headphones',
 'The computer produces no audio output despite sound being enabled and volume turned up.',
 'Beginner', '10-20 minutes', NULL, 'active');

-- ============================================================
-- SEED: Problem-Symptom Links
-- ============================================================
INSERT INTO problem_symptoms (problem_id, symptom_id) VALUES
-- Problem 1: Computer does not turn on
(1, 1), (1, 10),
-- Problem 2: No display
(2, 2), (2, 8),
-- Problem 3: Overheating
(3, 5), (3, 4), (3, 9),
-- Problem 4: RAM not detected
(4, 8), (4, 6), (4, 2),
-- Problem 5: Random shutdown
(5, 4), (5, 9), (5, 5),
-- Problem 6: Windows slow
(6, 11), (6, 3), (6, 15),
-- Problem 7: BSOD
(7, 14), (7, 6), (7, 9), (7, 15),
-- Problem 8: Windows Update failed
(8, 12), (8, 17),
-- Problem 9: Startup loop
(9, 13), (9, 18), (9, 14),
-- Problem 10: Missing system files
(10, 17), (10, 15), (10, 13),
-- Problem 11: App crashes
(11, 19), (11, 24), (11, 23),
-- Problem 12: Installation failed
(12, 21), (12, 24), (12, 23),
-- Problem 13: App freezes
(13, 22), (13, 25),
-- Problem 14: Wi-Fi cannot connect
(14, 26), (14, 28), (14, 31),
-- Problem 15: Slow internet
(15, 27), (15, 31),
-- Problem 16: No internet ethernet
(16, 28), (16, 30), (16, 32),
-- Problem 17: Cannot access website
(17, 29), (17, 28),
-- Problem 18: Printer not printing
(18, 33), (18, 35), (18, 37),
-- Problem 19: Paper jam
(19, 34), (19, 33),
-- Problem 20: Printer offline
(20, 35), (20, 37),
-- Problem 21: HDD not detected
(21, 39), (21, 43), (21, 44),
-- Problem 22: USB not detected
(22, 40), (22, 50),
-- Problem 23: Storage almost full
(23, 41), (23, 3), (23, 43),
-- Problem 24: Virus/malware
(24, 51), (24, 52), (24, 53), (24, 54), (24, 56),
-- Problem 25: No sound
(25, 47), (25, 60);

-- ============================================================
-- SEED: Causes
-- ============================================================
INSERT INTO causes (problem_id, cause_description, order_num) VALUES
-- Problem 1: Does not turn on
(1, 'Faulty or disconnected power cable', 1),
(1, 'Defective power supply unit (PSU)', 2),
(1, 'Blown fuse or tripped surge protector', 3),
(1, 'Loose internal power connections', 4),
-- Problem 2: No display
(2, 'Loose or damaged display cable (HDMI/VGA/DisplayPort)', 1),
(2, 'RAM not properly seated', 2),
(2, 'Monitor set to wrong input source', 3),
(2, 'Graphics card not properly seated or faulty', 4),
(2, 'Monitor itself is defective', 5),
-- Problem 3: Overheating
(3, 'Accumulated dust in CPU cooler or vents', 1),
(3, 'Dried-out or insufficient thermal paste on CPU', 2),
(3, 'CPU cooler fan not spinning', 3),
(3, 'Poor airflow inside the case', 4),
-- Problem 4: RAM not detected
(4, 'RAM module not fully seated in slot', 1),
(4, 'Dirty or oxidized RAM contacts', 2),
(4, 'Faulty RAM module', 3),
(4, 'Incompatible RAM for the motherboard', 4),
-- Problem 5: Random shutdown
(5, 'Overheating causing thermal protection shutdown', 1),
(5, 'Insufficient power from PSU', 2),
(5, 'Failing or overloaded power supply unit', 3),
(5, 'Bad sectors on the hard drive', 4),
-- Problem 6: Windows slow
(6, 'Too many startup programs', 1),
(6, 'Hard drive is almost full', 2),
(6, 'Malware or background processes consuming resources', 3),
(6, 'Outdated or fragmented HDD', 4),
-- Problem 7: BSOD
(7, 'Faulty or incompatible RAM', 1),
(7, 'Corrupted system files or drivers', 2),
(7, 'Hardware malfunction or overheating', 3),
(7, 'Failing hard drive', 4),
-- Problem 8: Windows Update failed
(8, 'Interrupted internet connection during update', 1),
(8, 'Insufficient disk space', 2),
(8, 'Corrupted Windows Update cache', 3),
(8, 'Conflicting third-party software', 4),
-- Problem 9: Startup loop
(9, 'Corrupted Boot Configuration Data (BCD)', 1),
(9, 'Failed Windows update', 2),
(9, 'Corrupted system files', 3),
(9, 'Faulty hardware (RAM or HDD)', 4),
-- Problem 10: Missing system files
(10, 'Corrupted Windows installation', 1),
(10, 'Malware infection that deleted system files', 2),
(10, 'Incomplete Windows update', 3),
-- Problem 11: App crashes
(11, 'Corrupted application installation files', 1),
(11, 'Missing runtime libraries (Visual C++, .NET)', 2),
(11, 'Incompatible software version', 3),
(11, 'Insufficient system resources (RAM/CPU)', 4),
-- Problem 12: Installation failed
(12, 'Insufficient disk space', 1),
(12, 'Corrupted installer file', 2),
(12, 'Conflicting installed software', 3),
(12, 'Missing administrator privileges', 4),
-- Problem 13: App freezes
(13, 'Insufficient RAM available', 1),
(13, 'Application bug or conflict', 2),
(13, 'Corrupt application data or cache', 3),
-- Problem 14: Wi-Fi cannot connect
(14, 'Incorrect Wi-Fi password entered', 1),
(14, 'Wi-Fi adapter driver outdated or corrupted', 2),
(14, 'Router or modem issue', 3),
(14, 'IP address conflict on the network', 4),
-- Problem 15: Slow internet
(15, 'Too many devices connected to the network', 1),
(15, 'Background downloads or updates consuming bandwidth', 2),
(15, 'ISP throttling or outage', 3),
(15, 'Outdated network adapter drivers', 4),
-- Problem 16: No internet ethernet
(16, 'Faulty or damaged Ethernet cable', 1),
(16, 'Network adapter driver issue', 2),
(16, 'Router or modem malfunction', 3),
(16, 'IP address conflict', 4),
-- Problem 17: Cannot access website
(17, 'DNS server issue', 1),
(17, 'Website is down or blocked', 2),
(17, 'Browser cache or cookies issue', 3),
(17, 'Firewall or antivirus blocking the site', 4),
-- Problem 18: Printer not printing
(18, 'Print spooler service is stopped', 1),
(18, 'Outdated or corrupted printer driver', 2),
(18, 'Ink or toner cartridge is empty', 3),
(18, 'Print job stuck in the queue', 4),
-- Problem 19: Paper jam
(19, 'Incorrect paper size or type loaded', 1),
(19, 'Paper loaded improperly in the tray', 2),
(19, 'Worn or dirty paper feed rollers', 3),
(19, 'Torn paper fragments left inside printer', 4),
-- Problem 20: Printer offline
(20, 'Printer not set as default printer', 1),
(20, 'USB or network connection issue', 2),
(20, 'Print spooler service not running', 3),
-- Problem 21: HDD/SSD not detected
(21, 'Loose data or power cable connection', 1),
(21, 'Faulty or failing drive', 2),
(21, 'Drive not initialized or formatted', 3),
(21, 'Outdated BIOS or SATA controller drivers', 4),
-- Problem 22: USB not detected
(22, 'USB port is damaged or disabled', 1),
(22, 'USB drive is corrupted or faulty', 2),
(22, 'Missing or outdated USB driver', 3),
-- Problem 23: Storage almost full
(23, 'Large files or duplicate files accumulating', 1),
(23, 'Temporary files and system cache not cleared', 2),
(23, 'Recycle Bin not emptied', 3),
(23, 'Windows update files not cleaned up', 4),
-- Problem 24: Virus/malware
(24, 'Downloaded infected files from the internet', 1),
(24, 'Opened phishing email attachment', 2),
(24, 'Visiting malicious websites', 3),
(24, 'Using infected USB drives', 4),
-- Problem 25: No sound
(25, 'Audio device not set as default playback device', 1),
(25, 'Muted audio or volume set to zero', 2),
(25, 'Outdated or corrupted audio driver', 3),
(25, 'Faulty audio cable or connector', 4);

-- ============================================================
-- SEED: Troubleshooting Steps
-- ============================================================
INSERT INTO troubleshooting_steps (problem_id, step_number, instruction) VALUES
-- Problem 1: Does not turn on
(1,1,'Check that the power cable is firmly connected to both the computer and the wall outlet or power strip.'),
(1,2,'Try a different wall outlet or power strip to rule out an electrical issue.'),
(1,3,'Check that the surge protector or UPS (if used) is powered on and functioning.'),
(1,4,'Press the power button and check for any LED indicators or fan movement.'),
(1,5,'If there is no response, check the internal power connections inside the case (PSU cables connected to motherboard).'),
(1,6,'Test with a known-working power supply if available.'),
-- Problem 2: No display
(2,1,'Verify that the monitor is powered on — check the power LED indicator on the monitor.'),
(2,2,'Check that the display cable (HDMI, VGA, or DisplayPort) is securely connected to both the monitor and the computer.'),
(2,3,'Confirm the monitor is set to the correct input source (HDMI 1, DisplayPort, etc.) using the monitor''s menu buttons.'),
(2,4,'Try connecting the monitor to a different computer to rule out a monitor problem.'),
(2,5,'Power off the computer, open the case, and reseat the RAM modules in their slots.'),
(2,6,'If a dedicated GPU is installed, reseat it in the PCIe slot and ensure its power connectors are attached.'),
(2,7,'Try booting with only one RAM stick at a time.'),
-- Problem 3: Overheating
(3,1,'Check the CPU and system temperatures using a tool like HWMonitor or Core Temp.'),
(3,2,'Power off the computer and open the case. Check for accumulated dust on the CPU fan, heatsink, and intake/exhaust vents.'),
(3,3,'Use compressed air to clean out dust from all fans and vents.'),
(3,4,'Verify that the CPU fan is spinning properly and is firmly connected to the CPU_FAN header on the motherboard.'),
(3,5,'If temperatures remain high, consider replacing the thermal paste on the CPU (remove old paste with isopropyl alcohol and apply a fresh pea-sized amount).'),
(3,6,'Ensure the computer case has proper airflow: intake fans at the front/bottom, exhaust fans at the rear/top.'),
-- Problem 4: RAM not detected
(4,1,'Power off the computer and unplug it from the wall outlet.'),
(4,2,'Open the computer case and locate the RAM slots on the motherboard.'),
(4,3,'Carefully remove the RAM modules by pressing down on the retaining clips on both ends.'),
(4,4,'Clean the gold contacts of the RAM with a clean pencil eraser or dry cloth to remove oxidation.'),
(4,5,'Reinsert the RAM firmly until both retaining clips click into place.'),
(4,6,'If you have multiple RAM sticks, try booting with only one stick at a time to identify a faulty module.'),
(4,7,'Consult the motherboard manual to ensure RAM is installed in the correct slots for dual-channel operation.'),
-- Problem 5: Random shutdown
(5,1,'Check CPU and GPU temperatures during use with HWMonitor. Temperatures above 90°C indicate overheating.'),
(5,2,'Clean dust from all fans and vents inside the computer.'),
(5,3,'Check Windows Event Viewer (eventvwr.msc) for Critical errors around the time of shutdowns.'),
(5,4,'Run Windows Memory Diagnostic to test for RAM errors.'),
(5,5,'Check the power supply: ensure all cables are firmly connected and the PSU wattage is sufficient for your hardware.'),
(5,6,'Run a hard drive health check using CrystalDiskInfo.'),
-- Problem 6: Windows slow
(6,1,'Press Ctrl+Shift+Esc to open Task Manager. Check for processes using high CPU or RAM.'),
(6,2,'Open Task Manager → Startup tab. Disable unnecessary startup programs.'),
(6,3,'Run Disk Cleanup: type "Disk Cleanup" in the Start menu and run it on the C: drive.'),
(6,4,'Check available disk space on the C: drive. Free up space if it is below 15% of total capacity.'),
(6,5,'Run a full malware scan using Windows Defender or another antivirus tool.'),
(6,6,'Check for and install pending Windows Updates.'),
(6,7,'Consider upgrading from HDD to SSD for a significant performance boost.'),
-- Problem 7: BSOD
(7,1,'Note the error code displayed on the blue screen (e.g., MEMORY_MANAGEMENT, PAGE_FAULT).'),
(7,2,'Search the error code online for specific causes related to that code.'),
(7,3,'Run Windows Memory Diagnostic: type "mdsched.exe" in Run (Win+R) and restart to test RAM.'),
(7,4,'Update all device drivers, especially GPU and chipset drivers.'),
(7,5,'Run System File Checker: open Command Prompt as Administrator and type "sfc /scannow".'),
(7,6,'Check for Windows Updates and install any pending updates.'),
(7,7,'Run CHKDSK to check the hard drive: open CMD as Admin, type "chkdsk /f /r" and restart.'),
-- Problem 8: Windows Update failed
(8,1,'Check your internet connection is stable before running Windows Update.'),
(8,2,'Ensure you have at least 10GB of free disk space on the C: drive.'),
(8,3,'Open Services (services.msc), find "Windows Update" and "Background Intelligent Transfer Service", stop them, then restart them.'),
(8,4,'Delete the SoftwareDistribution folder: navigate to C:\Windows and rename "SoftwareDistribution" to "SoftwareDistribution.old".'),
(8,5,'Open Command Prompt as Administrator and run: "net start wuauserv" to restart the Windows Update service.'),
(8,6,'Try running Windows Update again and check if the error persists.'),
(8,7,'Use the Windows Update Troubleshooter from Settings → Update & Security → Troubleshoot.'),
-- Problem 9: Startup loop
(9,1,'Try booting into Safe Mode by pressing F8 or holding Shift while clicking Restart.'),
(9,2,'If Safe Mode works, undo recent changes (software installations or driver updates).'),
(9,3,'Boot from a Windows installation USB/DVD and choose "Repair your computer".'),
(9,4,'Use Startup Repair from the recovery environment.'),
(9,5,'Open Command Prompt in recovery and run: "bootrec /fixmbr" and "bootrec /fixboot" and "bootrec /rebuildbcd".'),
(9,6,'If repair fails, back up data using the recovery CMD and perform a clean Windows installation.'),
-- Problem 10: Missing system files
(10,1,'Open Command Prompt as Administrator.'),
(10,2,'Run: sfc /scannow and wait for it to complete. It will find and replace corrupted system files.'),
(10,3,'If SFC reports errors, run: DISM /Online /Cleanup-Image /RestoreHealth'),
(10,4,'Run SFC again after DISM completes.'),
(10,5,'Restart the computer and check if the problem is resolved.'),
-- Problem 11: App crashes
(11,1,'Restart the application and check if the crash is reproducible.'),
(11,2,'Check Windows Event Viewer for application error logs related to the crash.'),
(11,3,'Uninstall and reinstall the application using the latest version from the official website.'),
(11,4,'Install or repair missing runtimes: Microsoft Visual C++ Redistributables and .NET Framework.'),
(11,5,'Check if the application is compatible with your version of Windows.'),
(11,6,'Run the application as Administrator and check if the issue persists.'),
-- Problem 12: Installation failed
(12,1,'Ensure you have enough free disk space (at least 2× the installer size).'),
(12,2,'Right-click the installer and select "Run as Administrator".'),
(12,3,'Disable antivirus temporarily as it may be blocking the installation.'),
(12,4,'Download a fresh copy of the installer to rule out a corrupted download.'),
(12,5,'Close all other running applications before installing.'),
(12,6,'Check Windows Temp folder (type %temp% in Run) and clear temporary files.'),
-- Problem 13: App freezes
(13,1,'Wait 1-2 minutes as the application may be processing a task.'),
(13,2,'Open Task Manager (Ctrl+Shift+Esc) and check if the application shows "Not Responding".'),
(13,3,'End the process from Task Manager and relaunch the application.'),
(13,4,'Clear the application''s cache or temporary data from its settings.'),
(13,5,'Close other applications to free up RAM.'),
(13,6,'Reinstall the application if freezing persists.'),
-- Problem 14: Wi-Fi cannot connect
(14,1,'Right-click the Wi-Fi icon in the taskbar and select "Troubleshoot problems".'),
(14,2,'Forget the Wi-Fi network and reconnect: Settings → Network → Wi-Fi → Manage known networks.'),
(14,3,'Restart the Wi-Fi router and modem by unplugging them for 30 seconds.'),
(14,4,'Open Device Manager, expand "Network adapters", and update the Wi-Fi adapter driver.'),
(14,5,'Open Command Prompt as Admin and run: "netsh winsock reset" then "netsh int ip reset". Restart.'),
(14,6,'Temporarily disable firewall/antivirus to rule out software conflicts.'),
-- Problem 15: Slow internet
(15,1,'Run a speed test at fast.com or speedtest.net and compare results to your subscribed plan speed.'),
(15,2,'Restart your router and modem by unplugging them for 30 seconds, then reconnecting.'),
(15,3,'Connect via Ethernet cable instead of Wi-Fi to check if the problem is wireless-specific.'),
(15,4,'Check if other devices on the network are also experiencing slow speeds.'),
(15,5,'Close bandwidth-heavy background applications (Windows Update, OneDrive, video streaming).'),
(15,6,'Contact your ISP if the problem persists and speeds are consistently below the subscribed plan.'),
-- Problem 16: No internet ethernet
(16,1,'Check that the Ethernet cable is firmly plugged into both the computer and the router/switch.'),
(16,2,'Try a different Ethernet cable to rule out a faulty cable.'),
(16,3,'Try a different port on the router.'),
(16,4,'Check if the network adapter shows as enabled in Device Manager.'),
(16,5,'Open CMD as Admin and run: "ipconfig /release" then "ipconfig /renew" then "ipconfig /flushdns".'),
(16,6,'Restart the router and modem.'),
-- Problem 17: Cannot access website
(17,1,'Try accessing the website using a different browser.'),
(17,2,'Try a different internet connection (mobile hotspot) to check if the site is accessible.'),
(17,3,'Clear browser cache and cookies: Ctrl+Shift+Delete in most browsers.'),
(17,4,'Open CMD and run: "nslookup websitename.com" to check DNS resolution.'),
(17,5,'Change your DNS server to Google DNS (8.8.8.8, 8.8.4.4) in Network Adapter settings.'),
(17,6,'Check if your antivirus or firewall is blocking the website.'),
-- Problem 18: Printer not printing
(18,1,'Check that the printer is powered on and has paper loaded correctly.'),
(18,2,'Check ink or toner levels from the printer software or control panel.'),
(18,3,'Open the print queue: Control Panel → Devices and Printers → double-click the printer. Cancel all pending jobs.'),
(18,4,'Open Services (services.msc) and restart the "Print Spooler" service.'),
(18,5,'Remove and reinstall the printer driver from the manufacturer''s website.'),
(18,6,'Try printing a test page from the printer''s own control panel.'),
-- Problem 19: Paper jam
(19,1,'Turn off the printer immediately to prevent motor damage.'),
(19,2,'Open all access panels (front, rear, and top if applicable).'),
(19,3,'Gently pull out the jammed paper in the direction of paper travel — do not yank or tear it.'),
(19,4,'Check for small torn pieces of paper remaining inside — these cause future jams.'),
(19,5,'Close all panels and turn the printer back on.'),
(19,6,'Avoid overfilling the paper tray. Use the correct paper size and type for your printer.'),
-- Problem 20: Printer offline
(20,1,'Ensure the printer is powered on and connected (via USB or network).'),
(20,2,'Open Control Panel → Devices and Printers. Right-click your printer and select "See what''s printing".'),
(20,3,'In the printer window, click Printer menu and uncheck "Use Printer Offline" if it is checked.'),
(20,4,'Set the printer as the default printer: right-click → Set as default printer.'),
(20,5,'Restart the Print Spooler service: open services.msc, find Print Spooler, right-click → Restart.'),
(20,6,'Reinstall the printer driver if the issue persists.'),
-- Problem 21: HDD/SSD not detected
(21,1,'Power off the computer and check that all SATA data and power cables are firmly connected to the drive and motherboard.'),
(21,2,'Try connecting the drive to a different SATA port on the motherboard.'),
(21,3,'Try a different SATA data cable.'),
(21,4,'Check BIOS/UEFI settings to see if the drive appears in the boot device list.'),
(21,5,'If the drive appears in BIOS but not Windows, open Disk Management (diskmgmt.msc) and initialize/format the drive.'),
(21,6,'Run CrystalDiskInfo to check the drive health status for any warnings or failures.'),
-- Problem 22: USB not detected
(22,1,'Try plugging the USB drive into a different USB port on the computer.'),
(22,2,'Try the USB drive on a different computer to check if the drive itself is faulty.'),
(22,3,'Open Device Manager and check for Unknown Device entries under Universal Serial Bus controllers.'),
(22,4,'Update USB controller drivers from Device Manager.'),
(22,5,'Open Disk Management to check if the drive appears but has no drive letter assigned.'),
(22,6,'If the drive is visible in Disk Management without a letter, right-click and select "Change Drive Letter and Paths".'),
-- Problem 23: Storage almost full
(23,1,'Open File Explorer, right-click the C: drive and select Properties to see used/free space.'),
(23,2,'Run Disk Cleanup: search for "Disk Cleanup" in Start, select C:, and check all boxes including System files.'),
(23,3,'Empty the Recycle Bin.'),
(23,4,'Uninstall unused applications: Settings → Apps → Apps & Features.'),
(23,5,'Move large files (videos, photos) to an external drive or cloud storage.'),
(23,6,'Enable Storage Sense: Settings → System → Storage → Configure Storage Sense.'),
-- Problem 24: Virus/malware
(24,1,'Disconnect the computer from the internet immediately to prevent further data transmission.'),
(24,2,'Run a full scan using Windows Defender: Security → Virus & threat protection → Quick scan.'),
(24,3,'Download and run Malwarebytes (free version) for a thorough malware scan.'),
(24,4,'Remove all detected threats and restart the computer.'),
(24,5,'Change passwords for all important accounts (email, banking, social media) from a clean device.'),
(24,6,'Enable Windows Defender real-time protection and keep Windows updated.'),
(24,7,'Avoid downloading software from unofficial sources in the future.'),
-- Problem 25: No sound
(25,1,'Click the speaker icon in the system tray and ensure volume is not muted and is set to an audible level.'),
(25,2,'Right-click the speaker icon → Open Sound Settings. Verify the correct output device is selected.'),
(25,3,'Right-click the speaker icon → Sounds → Playback tab. Set the correct device as default.'),
(25,4,'Open Device Manager → Sound, video and game controllers. Right-click the audio device → Update driver.'),
(25,5,'Uninstall the audio driver from Device Manager and restart Windows to reinstall it automatically.'),
(25,6,'Check the physical connection of speakers/headphones to ensure they are properly plugged in.');

-- ============================================================
-- SEED: Solutions
-- ============================================================
INSERT INTO solutions (problem_id, solution_description) VALUES
(1,  'Ensure all power connections are secure and test with a working power supply. If the computer still does not turn on, the power supply unit likely needs replacement.'),
(2,  'Reseat the display cable and RAM. If a dedicated GPU is present, reseat it. Try a different monitor or cable to isolate the issue.'),
(3,  'Clean the CPU cooler and apply fresh thermal paste. Ensure adequate case airflow. If the problem persists, consider upgrading the CPU cooling solution.'),
(4,  'Reseat the RAM modules, clean contacts, and test modules individually. Replace any faulty RAM stick.'),
(5,  'Address overheating first (clean fans, replace thermal paste). If shutdowns continue, test with a known-good PSU. Failing PSU replacement is the likely final solution.'),
(6,  'Disable startup programs, run Disk Cleanup, and perform a malware scan. For a permanent performance upgrade, replace an HDD with an SSD.'),
(7,  'Note the BSOD error code and address its specific cause. Run memory diagnostics, update drivers, and run SFC. If BSODs persist, consider reinstalling Windows.'),
(8,  'Clear the Windows Update cache by resetting the SoftwareDistribution folder, ensure stable internet, and adequate disk space. Use the Windows Update Troubleshooter as a last resort.'),
(9,  'Use Windows Startup Repair from a bootable USB. Run bootrec commands to rebuild BCD. If unsuccessful, reinstall Windows while preserving personal files.'),
(10, 'Run "sfc /scannow" followed by "DISM /Online /Cleanup-Image /RestoreHealth" to repair corrupted system files.'),
(11, 'Reinstall the application and ensure all required runtimes (Visual C++, .NET) are installed. Run as Administrator if permission issues occur.'),
(12, 'Download a fresh installer, run as Administrator with antivirus disabled, and ensure sufficient disk space before attempting installation again.'),
(13, 'Clear application cache and close background programs to free RAM. Reinstall the application if freezing continues.'),
(14, 'Forget and reconnect to the Wi-Fi network, update the wireless adapter driver, and reset network settings using netsh commands.'),
(15, 'Restart your router and modem. If speeds remain low, contact your ISP. Consider upgrading your internet plan if needed.'),
(16, 'Replace the Ethernet cable, flush DNS and renew IP address using CMD, and ensure the network adapter driver is up to date.'),
(17, 'Change DNS to 8.8.8.8 (Google DNS), clear browser cache, and check firewall settings. If the site is globally down, wait for the website owner to resolve it.'),
(18, 'Clear the print queue, restart the Print Spooler service, and reinstall the printer driver from the manufacturer''s website.'),
(19, 'Carefully remove all jammed paper with the printer powered off. Ensure paper is loaded correctly and not overfilling the tray.'),
(20, 'Uncheck "Use Printer Offline" from the printer menu, set it as the default printer, and restart the Print Spooler service.'),
(21, 'Check and reseat all drive cables. If the drive is new, initialize it via Disk Management. If drive health is failing, back up data and replace the drive immediately.'),
(22, 'Try different USB ports and update USB drivers. If the drive appears in Disk Management without a letter, assign one. If still undetected, the drive may be faulty.'),
(23, 'Run Disk Cleanup including system files, empty the Recycle Bin, uninstall unused applications, and move large files to external storage.'),
(24, 'Run Windows Defender and Malwarebytes to remove threats. Change account passwords from a clean device. Keep Windows and antivirus updated to prevent future infections.'),
(25, 'Set the correct audio output device as default in Sound Settings, update or reinstall the audio driver, and check all physical connections.');

-- ============================================================
-- SEED: Sample Troubleshooting Sessions
-- ============================================================
INSERT INTO troubleshooting_sessions (user_id, problem_id, category_id, user_description, selected_symptoms, match_score, result, status) VALUES
(2, 2,  1, 'My computer turns on but there is nothing on the screen.',   '2,8', 100.00, 'resolved', 'closed'),
(2, 14, 4, 'I cannot connect to the Wi-Fi network at home.',             '26,28', 100.00, 'resolved', 'closed'),
(2, 6,  2, 'My Windows takes a very long time to load after turning on.','11,3', 66.67, 'unresolved', 'closed'),
(3, 24, 8, 'Pop-ups keep appearing and the browser redirects randomly.', '53,54,52', 60.00, 'resolved', 'closed'),
(3, 18, 5, 'Printer is on but it won''t print anything.',               '33,35', 66.67, 'resolved', 'closed');

-- ============================================================
-- SEED: Sample Feedback
-- ============================================================
INSERT INTO feedback (session_id, user_id, rating, comments) VALUES
(1, 2, 'helpful', 'The steps were very clear and easy to follow. Problem solved!'),
(2, 2, 'helpful', 'Worked perfectly after resetting network settings.'),
(3, 2, 'partial', 'The steps helped but Windows is still a bit slow. I may need an SSD.'),
(4, 3, 'helpful', 'Malwarebytes removed the malware. Very useful guide.'),
(5, 3, 'helpful', 'Restarting the Print Spooler service fixed it immediately.');

-- ============================================================
-- SEED: Notifications
-- ============================================================
INSERT INTO notifications (user_id, title, message, is_read) VALUES
(2, 'Welcome to TechSense!', 'Hello Juan! Your account has been created. Start troubleshooting your IT problems now.', 0),
(2, 'Troubleshooting Session Saved', 'Your troubleshooting session for "No Display on Monitor" has been saved to your history.', 1),
(3, 'Welcome to TechSense!', 'Hello Maria! Your account has been created. Start troubleshooting your IT problems now.', 0),
(3, 'Knowledge Base Updated', 'New troubleshooting guides have been added to the Security category.', 0);
