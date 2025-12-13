-- Create reviews table for customer feedback system
-- Allows customers to rate and review completed jobs

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    staff_response TEXT,
    is_approved BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    responded_by INT NULL,
    
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
    
    INDEX idx_job_id (job_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at),
    INDEX idx_is_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample reviews for testing (only if jobs exist)
INSERT INTO reviews (job_id, customer_id, rating, review_text, is_approved, is_featured, created_at)
SELECT j.job_id, j.customer_id, 5, 'Excellent service! The mechanic was very professional and explained everything clearly. My car runs like new!', TRUE, TRUE, '2025-12-10 14:30:00'
FROM jobs j
JOIN appointments a ON j.appointment_id = a.appointment_id
WHERE j.status = 'completed'
LIMIT 1;

INSERT INTO reviews (job_id, customer_id, rating, review_text, is_approved, is_featured, created_at)
SELECT j.job_id, j.customer_id, 4, 'Good work overall. The repair took a bit longer than expected, but the quality was great.', TRUE, FALSE, '2025-12-11 10:15:00'
FROM jobs j
JOIN appointments a ON j.appointment_id = a.appointment_id
WHERE j.status = 'completed'
LIMIT 1 OFFSET 1;

-- Note: Staff responses can be added later through the admin panel
