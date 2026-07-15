<!-- Feedback Widget -->
<div id="feedbackWidget" class="feedback-widget">
    <button id="feedbackButton" class="feedback-button" onclick="openFeedbackModal()">
        <i class="fas fa-comment-dots"></i>
        <span>Feedback</span>
    </button>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="feedback-modal">
    <div class="feedback-modal-content">
        <div class="feedback-header">
            <h3><i class="fas fa-star"></i> Share Your Feedback</h3>
            <button class="feedback-close" onclick="closeFeedbackModal()">&times;</button>
        </div>
        
        <form id="feedbackForm" class="feedback-form">
            <!-- Star Rating -->
            <div class="feedback-group">
                <label>How would you rate your experience?</label>
                <div class="star-rating" id="starRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" id="rating" name="rating" required>
            </div>
            
            <!-- Category -->
            <div class="feedback-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select category...</option>
                    <option value="general">General Feedback</option>
                    <option value="bug">Bug Report</option>
                    <option value="feature">Feature Request</option>
                    <option value="usability">Usability</option>
                    <option value="performance">Performance</option>
                    <option value="content">Content Quality</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <!-- Guest user fields -->
            <div id="guestFields" style="display: <?php echo isset($_SESSION['user_id']) ? 'none' : 'block'; ?>;">
                <div class="feedback-group">
                    <label for="guestName">Your Name</label>
                    <input type="text" id="guestName" name="name" placeholder="Enter your name">
                </div>
                <div class="feedback-group">
                    <label for="guestEmail">Your Email</label>
                    <input type="email" id="guestEmail" name="email" placeholder="Enter your email">
                </div>
            </div>
            
            <!-- Subject -->
            <div class="feedback-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" placeholder="Brief summary of your feedback" required>
            </div>
            
            <!-- Message -->
            <div class="feedback-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" placeholder="Please provide detailed feedback..." required rows="4"></textarea>
            </div>
            
            <!-- Submit Button -->
            <div class="feedback-actions">
                <button type="button" class="btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitFeedback">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.feedback-widget {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 1000;
}

.feedback-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.feedback-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.feedback-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    overflow-y: auto;
}

.feedback-modal-content {
    background: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feedback-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feedback-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.feedback-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.feedback-close:hover {
    background: rgba(255,255,255,0.2);
}

.feedback-form {
    padding: 25px;
}

.feedback-group {
    margin-bottom: 20px;
}

.feedback-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #4a5568;
}

.feedback-group input,
.feedback-group select,
.feedback-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    font-family: inherit;
}

.feedback-group input:focus,
.feedback-group select:focus,
.feedback-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.star-rating {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
}

.star {
    font-size: 28px;
    color: #e2e8f0;
    cursor: pointer;
    transition: color 0.2s ease;
    user-select: none;
}

.star:hover,
.star.active {
    color: #fbbf24;
}

.feedback-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 25px;
}

.btn-primary,
.btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6169;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Mobile responsiveness */
@media (max-width: 600px) {
    .feedback-modal-content {
        margin: 2% auto;
        width: 95%;
    }
    
    .feedback-form {
        padding: 20px;
    }
    
    .feedback-actions {
        flex-direction: column;
    }
    
    .feedback-button {
        padding: 10px 16px;
        border-radius: 20px;
    }
}
</style>

<script>
let selectedRating = 0;

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            document.getElementById('rating').value = selectedRating;
            updateStarDisplay();
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
        });
    });
    
    document.getElementById('starRating').addEventListener('mouseleave', function() {
        updateStarDisplay();
    });
});

function highlightStars(rating) {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function updateStarDisplay() {
    highlightStars(selectedRating);
}

function openFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    resetFeedbackForm();
}

function resetFeedbackForm() {
    document.getElementById('feedbackForm').reset();
    selectedRating = 0;
    updateStarDisplay();
}

// Close modal when clicking outside
document.getElementById('feedbackModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFeedbackModal();
    }
});

// Handle form submission
document.getElementById('feedbackForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (selectedRating === 0) {
        alert('Please select a rating before submitting.');
        return;
    }
    
    const submitBtn = document.getElementById('submitFeedback');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const formData = new FormData(this);
        const feedbackData = {
            rating: selectedRating,
            category: formData.get('category'),
            subject: formData.get('subject'),
            message: formData.get('message'),
            name: formData.get('name'),
            email: formData.get('email'),
            page: window.location.pathname
        };
        
        const response = await fetch('api_feedback.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(feedbackData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Show success message
            showFeedbackMessage('Thank you for your feedback! We appreciate your input.', 'success');
            closeFeedbackModal();
        } else {
            showFeedbackMessage(result.error || 'Failed to submit feedback. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error submitting feedback:', error);
        showFeedbackMessage('Network error. Please check your connection and try again.', 'error');
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

function showFeedbackMessage(message, type) {
    // Create or update message element
    let messageEl = document.getElementById('feedbackMessage');
    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.id = 'feedbackMessage';
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 3000;
            max-width: 300px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(messageEl);
    }
    
    messageEl.textContent = message;
    messageEl.style.background = type === 'success' ? '#10b981' : '#ef4444';
    messageEl.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageEl.style.display = 'none';
    }, 5000);
}
</script>