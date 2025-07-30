-- Quiz Application Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS quiz_app;
USE quiz_app;

-- Users table to store user information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Questions table to store quiz questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quiz attempts table to track user performance
CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Quiz answers table to store individual question responses
CREATE TABLE quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer ENUM('a', 'b', 'c', 'd') NOT NULL,
    is_correct BOOLEAN NOT NULL,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Insert sample questions
INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
('What is the capital of France?', 'London', 'Berlin', 'Paris', 'Madrid', 'c'),
('Which programming language is known as the "language of the web"?', 'Python', 'JavaScript', 'Java', 'C++', 'b'),
('What does HTML stand for?', 'Hypertext Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink and Text Markup Language', 'a'),
('Which of the following is a CSS framework?', 'React', 'Bootstrap', 'Node.js', 'PHP', 'b'),
('What is the result of 2 + 2 * 3?', '12', '8', '10', '6', 'b'),
('Which database management system is most commonly used with PHP?', 'MongoDB', 'PostgreSQL', 'MySQL', 'SQLite', 'c'),
('What does PHP stand for?', 'Personal Home Page', 'PHP: Hypertext Preprocessor', 'Private Home Page', 'Public Hypertext Processor', 'b'),
('Which HTTP method is used to retrieve data?', 'POST', 'PUT', 'GET', 'DELETE', 'c'),
('What is the latest version of HTML?', 'HTML4', 'HTML5', 'HTML6', 'XHTML', 'b'),
('Which of the following is NOT a JavaScript framework?', 'Angular', 'Vue.js', 'React', 'Laravel', 'd'),
('What does CSS stand for?', 'Computer Style Sheets', 'Creative Style Sheets', 'Cascading Style Sheets', 'Colorful Style Sheets', 'c'),
('Which symbol is used for comments in PHP?', '//', '#', '/* */', 'All of the above', 'd'),
('What is the correct way to end a PHP statement?', '.', ';', ':', ',', 'b'),
('Which of the following is a server-side scripting language?', 'JavaScript', 'HTML', 'CSS', 'PHP', 'd'),
('What does AJAX stand for?', 'Asynchronous JavaScript and XML', 'Advanced JavaScript and XML', 'Automatic JavaScript and XML', 'Active JavaScript and XML', 'a'),
('Which Bootstrap class is used for responsive navigation?', 'nav-responsive', 'navbar', 'nav-bar', 'navigation', 'b'),
('What is the default port for HTTP?', '443', '21', '80', '25', 'c'),
('Which of the following is used to connect PHP with MySQL?', 'mysqli', 'PDO', 'mysql', 'Both A and B', 'd'),
('What does DOM stand for?', 'Document Object Model', 'Data Object Management', 'Dynamic Object Model', 'Document Oriented Model', 'a'),
('Which Bootstrap class is used to create a responsive table?', 'table-responsive', 'responsive-table', 'table-fluid', 'fluid-table', 'a');