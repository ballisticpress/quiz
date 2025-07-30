# Quiz Application

A comprehensive PHP-based quiz application with Bootstrap frontend, featuring user management, randomized questions, detailed analytics, and an admin dashboard.

## Features

### 🎯 Quiz System
- **Randomized Questions**: Each quiz presents 10 randomly selected questions from the database
- **Multiple Choice Format**: Four answer options per question with single selection
- **Real-time Progress**: Visual progress bar and question counter
- **Instant Results**: Detailed score breakdown and performance analysis
- **Retake Capability**: Users can take multiple quiz attempts with different questions

### 👥 User Management
- **Simple Registration**: First name and last name entry to start quizzes
- **Performance Tracking**: Individual user statistics and improvement trends
- **Attempt History**: Complete record of all quiz attempts with timestamps
- **User Analytics**: Best scores, averages, and performance patterns

### 📊 Admin Dashboard
- **Comprehensive Analytics**: Total users, attempts, average scores, and question counts
- **User Performance Overview**: Top performers and recent activity tracking
- **Advanced Filtering**: Filter users by date range, score thresholds, and attempt counts
- **Data Export**: Export quiz results to CSV or JSON formats
- **Question Management**: Add new questions and manage existing ones

### 🎨 Modern UI/UX
- **Bootstrap 5**: Responsive, mobile-first design
- **Interactive Elements**: Smooth animations and hover effects
- **Accessibility**: ARIA labels and keyboard navigation support
- **Visual Feedback**: Color-coded performance indicators and progress bars

### 🔒 Security Features
- **Input Validation**: Comprehensive server-side validation and sanitization
- **CSRF Protection**: Token-based protection against cross-site request forgery
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: HTML escaping for all user-generated content

## Technology Stack

- **Backend**: PHP 7.4+ with PDO for database interactions
- **Frontend**: Bootstrap 5.3.0 with custom CSS and JavaScript
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Icons**: Bootstrap Icons 1.10.0
- **Charts**: Chart.js for performance visualizations

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)
- Web server (Apache, Nginx, or built-in PHP server)

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   cd quiz-application
   ```

2. **Database Setup**
   - Create a MySQL database named `quiz_app`
   - Import the database schema:
   ```bash
   mysql -u your_username -p quiz_app < sql/database_schema.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php`
   - Update the database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'quiz_app';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Set File Permissions**
   ```bash
   chmod 755 -R .
   chmod 644 config/database.php
   ```

5. **Start the Application**
   - For Apache/Nginx: Place files in web root directory
   - For PHP built-in server:
   ```bash
   php -S localhost:8000
   ```

6. **Access the Application**
   - Open your browser and navigate to your domain or `http://localhost:8000`
   - The database comes pre-loaded with 20 sample questions
   - URLs are clean without .php extensions (e.g., `/quiz` instead of `/quiz.php`)

## Usage Guide

### For Quiz Takers

1. **Start a Quiz**
   - Enter your first and last name on the homepage
   - Click "Start Quiz Challenge"

2. **Taking the Quiz**
   - Read each question carefully
   - Select one of the four answer options
   - Click "Next" to proceed to the next question
   - Use keyboard shortcuts (1-4 or A-D) for quick selection

3. **View Results**
   - See your score and percentage at the end
   - Review detailed question breakdown
   - Compare with your previous attempts
   - Take another quiz with different questions

### For Administrators

1. **Access Dashboard**
   - Navigate to `/admin` (automatically redirects to dashboard)
   - View overall statistics and user performance

2. **Manage Questions**
   - Click "Add Question" to create new quiz questions
   - Fill in question text and four answer options
   - Select the correct answer
   - Use the tips provided for creating effective questions

3. **Analyze Performance**
   - Use filters to analyze user performance by date, score, or attempts
   - View top performers and recent activity
   - Export data for further analysis

4. **User Management**
   - View individual user details and performance history
   - Track improvement trends and attempt patterns

## File Structure

```
quiz-application/
├── admin/                  # Admin dashboard and management
│   ├── dashboard.php       # Main admin dashboard
│   ├── add_question.php    # Add new questions
│   ├── user_details.php    # Individual user analytics
│   └── dashboard_handler.php # AJAX handler for dashboard
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── script.js       # JavaScript functionality
├── config/
│   └── database.php        # Database configuration
├── includes/               # Common includes
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── sql/
│   └── database_schema.sql # Database structure and sample data
├── index.php               # Main landing page
├── quiz.php                # Quiz interface
├── quiz_handler.php        # Quiz AJAX handler
├── results.php             # Results display
└── README.md               # This file
```

## Database Schema

### Tables

- **users**: Stores user information (id, first_name, last_name, created_at)
- **questions**: Quiz questions with multiple choice options
- **quiz_attempts**: Records of completed quizzes with scores
- **quiz_answers**: Individual question responses for each attempt

### Sample Data

The application comes with 20 pre-loaded questions covering:
- Web development (HTML, CSS, JavaScript, PHP)
- General programming concepts
- Technology and computer science basics

## Customization

### Adding More Questions

1. Use the admin interface at `/admin/add_question`
2. Or directly insert into the database:
```sql
INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) 
VALUES ('Your question?', 'Option A', 'Option B', 'Option C', 'Option D', 'a');
```

### Modifying Quiz Settings

Edit the following in `quiz.php`:
- Number of questions per quiz (default: 10)
- Question selection criteria
- Time limits (if desired)

### Styling Customization

- Modify `assets/css/style.css` for visual changes
- Update Bootstrap variables for theme customization
- Add custom JavaScript in `assets/js/script.js`

## Security Considerations

- Change default database credentials
- Use HTTPS in production
- Regularly update PHP and dependencies
- Implement proper session management
- Consider rate limiting for quiz attempts

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For issues, questions, or contributions:
- Create an issue in the repository
- Check existing documentation
- Review the code comments for implementation details

---

**Note**: This application is designed for educational and demonstration purposes. For production use, consider additional security measures, performance optimizations, and scalability improvements.
