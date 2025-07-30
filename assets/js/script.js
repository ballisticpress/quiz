/**
 * Quiz Application JavaScript
 * Handles quiz interactions, AJAX requests, and UI enhancements
 */

// Global variables
let currentQuestion = 0;
let selectedAnswer = null;
let quizData = [];

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize quiz if on quiz page
    if (document.getElementById('quiz-container')) {
        initializeQuiz();
    }
    
    // Initialize dashboard if on dashboard page
    if (document.getElementById('dashboard-container')) {
        initializeDashboard();
    }
    
    // Add fade-in animation to main content
    document.querySelector('.container').classList.add('fade-in');
});

/**
 * Initialize quiz functionality
 */
function initializeQuiz() {
    // Handle answer selection
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('answer-option')) {
            selectAnswer(e.target);
        }
    });
    
    // Handle next question button
    const nextBtn = document.getElementById('next-question');
    if (nextBtn) {
        nextBtn.addEventListener('click', nextQuestion);
    }
    
    // Handle quiz submission
    const submitBtn = document.getElementById('submit-quiz');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitQuiz);
    }
}

/**
 * Select an answer option
 * @param {Element} selectedOption 
 */
function selectAnswer(selectedOption) {
    // Remove previous selection
    document.querySelectorAll('.answer-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selection to clicked option
    selectedOption.classList.add('selected');
    selectedAnswer = selectedOption.dataset.answer;
    
    // Enable next button
    const nextBtn = document.getElementById('next-question');
    const submitBtn = document.getElementById('submit-quiz');
    
    if (nextBtn) nextBtn.disabled = false;
    if (submitBtn) submitBtn.disabled = false;
}

/**
 * Move to next question
 */
function nextQuestion() {
    if (selectedAnswer === null) {
        showAlert('Please select an answer before proceeding.', 'warning');
        return;
    }
    
    // Store answer
    storeAnswer();
    
    // Show loading
    showLoading();
    
    // Submit answer via AJAX
    const formData = new FormData();
    formData.append('question_id', document.getElementById('question-id').value);
    formData.append('selected_answer', selectedAnswer);
    formData.append('action', 'submit_answer');
    
    fetch('quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            if (data.next_question) {
                loadNextQuestion(data.next_question);
            } else {
                // Quiz completed, redirect to results
                window.location.href = 'results.php';
            }
        } else {
            showAlert(data.message || 'An error occurred', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Network error occurred', 'danger');
        console.error('Error:', error);
    });
}

/**
 * Load next question
 * @param {Object} questionData 
 */
function loadNextQuestion(questionData) {
    const container = document.getElementById('quiz-container');
    container.innerHTML = questionData.html;
    
    // Update progress bar
    updateProgressBar(questionData.current, questionData.total);
    
    // Reset selection
    selectedAnswer = null;
    
    // Add fade-in animation
    container.classList.add('fade-in');
}

/**
 * Update progress bar
 * @param {number} current 
 * @param {number} total 
 */
function updateProgressBar(current, total) {
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        const percentage = (current / total) * 100;
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.textContent = `${current}/${total}`;
    }
}

/**
 * Submit quiz
 */
function submitQuiz() {
    if (selectedAnswer === null) {
        showAlert('Please select an answer before submitting.', 'warning');
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('question_id', document.getElementById('question-id').value);
    formData.append('selected_answer', selectedAnswer);
    formData.append('action', 'submit_final');
    
    fetch('quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            window.location.href = 'results.php';
        } else {
            showAlert(data.message || 'An error occurred', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Network error occurred', 'danger');
        console.error('Error:', error);
    });
}

/**
 * Store answer locally (for backup)
 */
function storeAnswer() {
    if (typeof(Storage) !== "undefined") {
        const answers = JSON.parse(localStorage.getItem('quiz_answers') || '[]');
        answers.push({
            question_id: document.getElementById('question-id').value,
            selected_answer: selectedAnswer,
            timestamp: new Date().toISOString()
        });
        localStorage.setItem('quiz_answers', JSON.stringify(answers));
    }
}

/**
 * Initialize dashboard functionality
 */
function initializeDashboard() {
    // Handle filter changes
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', applyFilters);
    });
    
    // Handle date range picker
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', applyFilters);
    });
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
}

/**
 * Apply dashboard filters
 */
function applyFilters() {
    const filters = {};
    
    // Collect filter values
    document.querySelectorAll('.filter-select').forEach(select => {
        if (select.value) {
            filters[select.name] = select.value;
        }
    });
    
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (input.value) {
            filters[input.name] = input.value;
        }
    });
    
    // Send AJAX request to filter data
    const formData = new FormData();
    Object.keys(filters).forEach(key => {
        formData.append(key, filters[key]);
    });
    formData.append('action', 'filter_users');
    
    fetch('admin/dashboard_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUserTable(data.users);
            updateStatistics(data.stats);
        } else {
            showAlert(data.message || 'Filter error', 'danger');
        }
    })
    .catch(error => {
        showAlert('Network error occurred', 'danger');
        console.error('Error:', error);
    });
}

/**
 * Update user table with filtered data
 * @param {Array} users 
 */
function updateUserTable(users) {
    const tbody = document.querySelector('#users-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.first_name} ${user.last_name}</td>
            <td>${user.total_attempts}</td>
            <td>${user.best_score}%</td>
            <td>${user.average_score}%</td>
            <td>${formatDate(user.last_attempt)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails(${user.id})">
                    <i class="bi bi-eye"></i> View
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Update statistics display
 * @param {Object} stats 
 */
function updateStatistics(stats) {
    const statElements = {
        'total-users': stats.total_users,
        'total-attempts': stats.total_attempts,
        'average-score': stats.average_score + '%',
        'questions-count': stats.questions_count
    };
    
    Object.keys(statElements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = statElements[id];
        }
    });
}

/**
 * View user details
 * @param {number} userId 
 */
function viewUserDetails(userId) {
    window.location.href = `admin/user_details.php?id=${userId}`;
}

/**
 * Show alert message
 * @param {string} message 
 * @param {string} type 
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || document.body;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.insertBefore(alert, alertContainer.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Show loading indicator
 */
function showLoading() {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => {
        if (!btn.disabled) {
            btn.disabled = true;
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span> Loading...';
        }
    });
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    const buttons = document.querySelectorAll('button[data-original-text]');
    buttons.forEach(btn => {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText;
        delete btn.dataset.originalText;
    });
}

/**
 * Format date for display
 * @param {string} dateString 
 * @returns {string}
 */
function formatDate(dateString) {
    if (!dateString) return 'Never';
    
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

/**
 * Initialize charts (if Chart.js is loaded)
 */
function initializeCharts() {
    // Performance chart
    const performanceCtx = document.getElementById('performance-chart');
    if (performanceCtx) {
        // Chart implementation would go here
        // This is a placeholder for actual chart data
    }
}

// Utility functions
const Utils = {
    /**
     * Debounce function calls
     * @param {Function} func 
     * @param {number} wait 
     * @returns {Function}
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Validate form inputs
     * @param {HTMLFormElement} form 
     * @returns {boolean}
     */
    validateForm: function(form) {
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
};