-- CareerPath India v2 — fresh install.
-- Run once on a new database. To reset: DROP DATABASE career_guidance_db; then run again.
CREATE DATABASE IF NOT EXISTS career_guidance_db
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE career_guidance_db;

CREATE TABLE careers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    education VARCHAR(150),
    skills TEXT NOT NULL,
    interest_tags VARCHAR(255),
    work_style VARCHAR(255),
    difficulty VARCHAR(20) NOT NULL DEFAULT 'Beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roadmap_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    career_id INT NOT NULL,
    step_number INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    duration VARCHAR(80),
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
);

CREATE TABLE learning_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    career_id INT NULL,
    title VARCHAR(180) NOT NULL,
    provider VARCHAR(120),
    resource_url VARCHAR(500) NOT NULL,
    resource_type VARCHAR(50),
    is_free TINYINT(1) DEFAULT 1,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE SET NULL
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE saved_careers (
    user_id INT NOT NULL,
    career_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, career_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
);

CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    career_id INT NOT NULL,
    item_type VARCHAR(10) NOT NULL,       -- 'step' or 'skill'
    item_key VARCHAR(150) NOT NULL,       -- step id, or lower-case skill name
    UNIQUE KEY uniq_progress (user_id, career_id, item_type, item_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (career_id) REFERENCES careers(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(10) NOT NULL,           -- 'user' or 'admin'
    ip VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts (scope, ip, attempted_at)
);

INSERT INTO careers (title, category, description, education, skills, interest_tags, work_style, difficulty) VALUES
('Web Developer','Software Development','Build websites and web applications.','Computer Science or equivalent practical skills','HTML, CSS, JavaScript, PHP, SQL, Git','coding,design,creativity','remote,independent,team','Beginner'),
('Data Analyst','Data and Analytics','Analyse data and communicate useful findings.','Any relevant degree with data skills','Excel, SQL, Statistics, Data visualization','analysis,coding,communication','remote,team,independent','Intermediate'),
('UI/UX Designer','Design','Design digital products that are easy to use.','Design or any relevant educational background','Figma, Wireframing, Prototyping, User research','design,creativity,communication','team,remote','Beginner'),
('Cybersecurity Analyst','Cybersecurity','Help protect systems, networks, and information.','Relevant technical education or equivalent skills','Networking, Linux, Security fundamentals, Scripting','security,coding,analysis','team,hands-on','Advanced'),
('Digital Marketer','Marketing','Help people find products and services through search, content and social media.','Any degree; marketing or communication is helpful','SEO, Content writing, Analytics, Social media','communication,creativity,analysis','team,remote','Beginner'),
('Mobile App Developer','Software Development','Build apps for Android and iOS phones.','Computer Science or equivalent practical skills','Kotlin or Dart, UI toolkits, APIs, Git','coding,design,creativity','independent,remote,team','Intermediate');

INSERT INTO roadmap_steps (career_id, step_number, title, description, duration) VALUES
(1,1,'Learn HTML and CSS','Build and style simple web pages.','1–2 weeks'),
(1,2,'Learn JavaScript','Practise variables, functions, events and DOM manipulation.','2–4 weeks'),
(1,3,'Learn PHP and SQL','Build server-side pages and practise database operations.','2–4 weeks'),
(1,4,'Build a portfolio project','Create a complete website with forms and database storage.','2–3 weeks'),
(1,5,'Learn Git and deployment','Track your code and learn how to publish a project.','1–2 weeks'),
(2,1,'Learn spreadsheet basics','Use Excel or Google Sheets for cleaning, sorting and formulas.','1–2 weeks'),
(2,2,'Learn SQL','Query tables with SELECT, JOIN, GROUP BY and filters.','2–4 weeks'),
(2,3,'Learn statistics basics','Understand averages, spread, correlation and sampling.','2–4 weeks'),
(2,4,'Learn data visualization','Build clear charts and dashboards that answer a question.','2–3 weeks'),
(2,5,'Complete an analysis project','Analyse a public dataset and write up your findings.','2–3 weeks'),
(3,1,'Learn design principles','Study layout, colour, typography and accessibility.','1–2 weeks'),
(3,2,'Learn Figma','Create frames, components and simple prototypes.','2–3 weeks'),
(3,3,'Practise user research','Run short interviews and usability tests with real people.','2–3 weeks'),
(3,4,'Design a case study','Redesign an app screen flow and explain your decisions.','2–4 weeks'),
(4,1,'Learn networking basics','Understand IP, DNS, HTTP and how traffic moves.','2–4 weeks'),
(4,2,'Learn Linux','Use the command line, file permissions and processes.','2–3 weeks'),
(4,3,'Learn security fundamentals','Study authentication, encryption and common web risks.','3–4 weeks'),
(4,4,'Practise in legal labs','Use intentionally vulnerable practice labs you are allowed to test.','Ongoing'),
(5,1,'Learn marketing basics','Understand audiences, channels and how a campaign is planned.','1–2 weeks'),
(5,2,'Learn SEO and content writing','Research keywords and write useful pages and posts.','3–4 weeks'),
(5,3,'Learn web analytics','Read traffic reports and measure what a campaign achieved.','2–3 weeks'),
(5,4,'Run a small campaign','Promote a real or practice project and record the results.','2–4 weeks'),
(6,1,'Learn programming fundamentals','Pick Kotlin (Android) or Dart (Flutter) and learn the basics.','3–5 weeks'),
(6,2,'Learn a UI toolkit','Build screens with Jetpack Compose or Flutter widgets.','3–4 weeks'),
(6,3,'Work with data and APIs','Fetch data from a web API and store data on the device.','2–4 weeks'),
(6,4,'Build and test an app','Finish a complete app and test it on a real phone.','3–4 weeks');

INSERT INTO learning_resources (career_id, title, provider, resource_url, resource_type, is_free) VALUES
(1,'Learn web development','MDN Web Docs','https://developer.mozilla.org/en-US/docs/Learn_web_development','Documentation and tutorials',1),
(1,'PHP Manual','PHP','https://www.php.net/manual/en/','Documentation',1),
(1,'MySQL Documentation','MySQL','https://dev.mysql.com/doc/','Documentation',1),
(2,'Kaggle Learn','Kaggle','https://www.kaggle.com/learn','Interactive courses',1),
(2,'SQLBolt','SQLBolt','https://sqlbolt.com/','Interactive tutorial',1),
(3,'Figma Help Center','Figma','https://help.figma.com/','Documentation',1),
(4,'OWASP Top Ten','OWASP','https://owasp.org/www-project-top-ten/','Documentation',1),
(4,'Linux Journey','Linux Journey','https://linuxjourney.com/','Tutorial',1),
(5,'Google Skillshop','Google','https://skillshop.withgoogle.com/','Courses',1),
(6,'Android Developers courses','Google','https://developer.android.com/courses','Courses',1),
(6,'Flutter documentation','Flutter','https://docs.flutter.dev/','Documentation',1),
(NULL,'GitHub Skills','GitHub','https://skills.github.com/','Interactive learning',1);
