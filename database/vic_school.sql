CREATE DATABASE IF NOT EXISTS vic_school;
USE vic_school;

-- ==========================
-- ADMINS
-- ==========================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- STUDENTS
-- ==========================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(50) UNIQUE,
    roll_no VARCHAR(50),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    gender VARCHAR(20),
    dob DATE,
    class VARCHAR(50),
    section VARCHAR(20),
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- TEACHERS
-- ==========================
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50),
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    subject VARCHAR(100),
    qualification VARCHAR(100),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- PARENTS
-- ==========================
CREATE TABLE IF NOT EXISTS parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ==========================
-- ONLINE ADMISSIONS
-- ==========================
CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150),
    parent_name VARCHAR(150),
    mobile VARCHAR(20),
    email VARCHAR(100),
    class_applied VARCHAR(50),
    address TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- CONTACT ENQUIRIES
-- ==========================
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- NEWS
-- ==========================
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- EVENTS
-- ==========================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    event_date DATE,
    image VARCHAR(255)
);

-- ==========================
-- GALLERY
-- ==========================
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    image VARCHAR(255),
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- ATTENDANCE
-- ==========================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    attendance_date DATE,
    status ENUM('Present', 'Absent', 'Leave'),
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ==========================
-- FEES
-- ==========================
CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    amount DECIMAL(10,2),
    payment_date DATE,
    payment_status ENUM('Paid', 'Pending'),
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ==========================
-- RESULTS
-- ==========================
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    subject VARCHAR(100),
    marks INT,
    total_marks INT,
    exam_name VARCHAR(100),
    FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ==========================
-- NOTICES
-- ==========================
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    publish_date DATE
);

-- ==========================
-- INDEXES FOR PERFORMANCE
-- ==========================
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_class_section ON students(class, section);
CREATE INDEX idx_teachers_email ON teachers(email);
CREATE INDEX idx_parents_email ON parents(email);
CREATE INDEX idx_attendance_student_date ON attendance(student_id, attendance_date);
CREATE INDEX idx_fees_student ON fees(student_id);
CREATE INDEX idx_results_student ON results(student_id);

-- ==========================
-- SEO SETTINGS
-- ==========================
CREATE TABLE IF NOT EXISTS seo_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(100) DEFAULT 'VIC School',
    default_meta_title VARCHAR(200) DEFAULT 'VIC School - Inspiring Excellence',
    default_meta_description TEXT,
    default_meta_keywords VARCHAR(255),
    default_og_image VARCHAR(255),
    robots_txt TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- SEO PAGES
-- ==========================
CREATE TABLE IF NOT EXISTS seo_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_path VARCHAR(255) UNIQUE NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    og_title VARCHAR(200),
    og_description TEXT,
    og_image VARCHAR(255),
    twitter_card VARCHAR(50) DEFAULT 'summary_large_image',
    canonical_url VARCHAR(255),
    schema_markup TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- BLOG CATEGORIES
-- ==========================
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
);

-- ==========================
-- BLOGS
-- ==========================
CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT,
    tags VARCHAR(255),
    featured_image VARCHAR(255),
    meta_title VARCHAR(200),
    meta_description TEXT,
    status ENUM('Draft', 'Published') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
);

-- ==========================
-- CRM LEADS
-- ==========================
CREATE TABLE IF NOT EXISTS crm_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT DEFAULT 1,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    class_applied VARCHAR(50),
    message TEXT,
    source VARCHAR(50) DEFAULT 'Website',
    status ENUM('New Lead', 'Contacted', 'Interested', 'Visit Scheduled', 'Admission Confirmed') DEFAULT 'New Lead',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE
);

-- ==========================
-- CRM FOLLOWUPS
-- ==========================
CREATE TABLE IF NOT EXISTS crm_followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    followup_date DATETIME NOT NULL,
    response TEXT,
    method ENUM('Call', 'WhatsApp', 'Email', 'In-Person') DEFAULT 'Call',
    next_followup_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE
);

-- ==========================
-- CRM NOTES
-- ==========================
CREATE TABLE IF NOT EXISTS crm_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE
);

-- ==========================
-- CRM TASKS
-- ==========================
CREATE TABLE IF NOT EXISTS crm_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    task_title VARCHAR(255) NOT NULL,
    task_desc TEXT,
    due_date DATETIME NOT NULL,
    status ENUM('Pending', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE
);

CREATE INDEX idx_crm_leads_school ON crm_leads(school_id);
CREATE INDEX idx_crm_leads_status ON crm_leads(status);
CREATE INDEX idx_crm_leads_phone ON crm_leads(phone);


