<?php
/**
 * Notification Helper Functions
 * Use these functions throughout the application to send notifications
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Send a notification to a user
 * 
 * @param string $user_type 'staff' or 'customer'
 * @param int $user_id User ID
 * @param string $type_code Notification type code (e.g., 'JOB_ASSIGNED')
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link_url Optional link URL
 * @param string $priority 'low', 'normal', 'high', or 'urgent'
 * @param string|null $related_entity Optional related entity type
 * @param int|null $related_id Optional related entity ID
 * @param string $sender_type 'staff', 'customer', or 'system'
 * @param int|null $sender_id Optional sender ID
 * @return bool Success status
 */
function sendNotification(
    $user_type,
    $user_id,
    $type_code,
    $title,
    $message,
    $link_url = null,
    $priority = 'normal',
    $related_entity = null,
    $related_id = null,
    $sender_type = 'system',
    $sender_id = null
) {
    global $conn;
    
    try {
        // Get notification_type_id from code
        $type_query = "SELECT notification_type_id FROM notification_types WHERE code = ?";
        $type_stmt = $conn->prepare($type_query);
        $type_stmt->bind_param("s", $type_code);
        $type_stmt->execute();
        $type_result = $type_stmt->get_result();
        
        if ($type_result->num_rows == 0) {
            error_log("Unknown notification type code: $type_code");
            return false;
        }
        
        $notification_type_id = $type_result->fetch_assoc()['notification_type_id'];
        
        // Check user preferences
        $pref_query = "SELECT email_enabled, in_app_enabled, quiet_hours_start, quiet_hours_end
                       FROM notification_preferences
                       WHERE user_type = ? AND user_id = ? AND notification_type_id = ?";
        $pref_stmt = $conn->prepare($pref_query);
        $pref_stmt->bind_param("sii", $user_type, $user_id, $notification_type_id);
        $pref_stmt->execute();
        $pref_result = $pref_stmt->get_result();
        
        // If no preferences exist, create default ones
        if ($pref_result->num_rows == 0) {
            $create_pref = "INSERT INTO notification_preferences 
                           (user_type, user_id, notification_type_id, email_enabled, in_app_enabled)
                           VALUES (?, ?, ?, TRUE, TRUE)";
            $create_stmt = $conn->prepare($create_pref);
            $create_stmt->bind_param("sii", $user_type, $user_id, $notification_type_id);
            $create_stmt->execute();
            $in_app_enabled = true;
            $email_enabled = true;
        } else {
            $prefs = $pref_result->fetch_assoc();
            $in_app_enabled = $prefs['in_app_enabled'];
            $email_enabled = $prefs['email_enabled'];
            
            // Check quiet hours
            if ($prefs['quiet_hours_start'] && $prefs['quiet_hours_end']) {
                $current_time = date('H:i:s');
                $start = $prefs['quiet_hours_start'];
                $end = $prefs['quiet_hours_end'];
                
                if ($start < $end) {
                    // Normal range (e.g., 22:00 - 08:00 next day)
                    if ($current_time >= $start && $current_time <= $end) {
                        // In quiet hours - only send urgent/high priority
                        if (!in_array($priority, ['urgent', 'high'])) {
                            return true; // Skip notification
                        }
                    }
                }
            }
        }
        
        // Create in-app notification
        if ($in_app_enabled) {
            $insert_query = "INSERT INTO notifications 
                           (user_type, user_id, notification_type_id, sender_type, sender_id,
                            title, message, link_url, priority, related_entity, related_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param(
                "siisississi",
                $user_type,
                $user_id,
                $notification_type_id,
                $sender_type,
                $sender_id,
                $title,
                $message,
                $link_url,
                $priority,
                $related_entity,
                $related_id
            );
            $insert_stmt->execute();
        }
        
        // Send email if enabled
        if ($email_enabled) {
            sendNotificationEmail($user_type, $user_id, $title, $message, $link_url);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification email
 */
function sendNotificationEmail($user_type, $user_id, $title, $message, $link_url = null) {
    global $conn;
    
    // Get user email
    if ($user_type === 'staff') {
        $query = "SELECT email, name FROM staff WHERE staff_id = ?";
    } else {
        $query = "SELECT email, name FROM customers WHERE customer_id = ?";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    $email = $user['email'];
    $name = $user['name'];
    
    // Prepare email
    $subject = "Screw Dheela - $title";
    $body = "Dear $name,\n\n";
    $body .= "$message\n\n";
    
    if ($link_url) {
        $full_url = "http://localhost/garage_system" . $link_url;
        $body .= "View details: $full_url\n\n";
    }
    
    $body .= "Best regards,\n";
    $body .= "Screw Dheela Management System\n\n";
    $body .= "---\n";
    $body .= "To manage your notification preferences, visit:\n";
    $body .= "http://localhost/garage_system/notifications/settings.php";
    
    $headers = "From: noreply@screwdheela.com\r\n";
    $headers .= "Reply-To: support@screwdheela.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Note: In production, use PHPMailer or similar for better email delivery
    return mail($email, $subject, $body, $headers);
}

/**
 * Bulk send notifications to multiple users
 */
function sendBulkNotification(
    $user_type,
    array $user_ids,
    $type_code,
    $title,
    $message,
    $link_url = null,
    $priority = 'normal'
) {
    $success_count = 0;
    foreach ($user_ids as $user_id) {
        if (sendNotification($user_type, $user_id, $type_code, $title, $message, $link_url, $priority)) {
            $success_count++;
        }
    }
    return $success_count;
}

/**
 * Notification type helper constants
 */
define('NOTIF_APPOINTMENT_REMINDER', 'APPOINTMENT_REMINDER');
define('NOTIF_BILL_GENERATED', 'BILL_GENERATED');
define('NOTIF_APPOINTMENT_CONFIRMED', 'APPOINTMENT_CONFIRMED');
define('NOTIF_APPOINTMENT_CANCELLED', 'APPOINTMENT_CANCELLED');
define('NOTIF_JOB_ASSIGNED', 'JOB_ASSIGNED');
define('NOTIF_JOB_STARTED', 'JOB_STARTED');
define('NOTIF_JOB_COMPLETED', 'JOB_COMPLETED');
define('NOTIF_PAYMENT_RECEIVED', 'PAYMENT_RECEIVED');
define('NOTIF_PAYMENT_REMINDER', 'PAYMENT_REMINDER');
define('NOTIF_MESSAGE_RECEIVED', 'MESSAGE_RECEIVED');
define('NOTIF_REVIEW_SUBMITTED', 'REVIEW_SUBMITTED');
define('NOTIF_SYSTEM_ALERT', 'SYSTEM_ALERT');

/**
 * Example usage:
 * 
 * // Send job assignment notification
 * sendNotification(
 *     'staff',
 *     $mechanic_id,
 *     NOTIF_JOB_ASSIGNED,
 *     'New Job Assigned',
 *     "You have been assigned a new job for $vehicle_details",
 *     "/jobs/list.php?id=$job_id",
 *     'high',
 *     'job',
 *     $job_id,
 *     'staff',
 *     $_SESSION['staff_id']
 * );
 * 
 * // Send appointment confirmation
 * sendNotification(
 *     'customer',
 *     $customer_id,
 *     NOTIF_APPOINTMENT_CONFIRMED,
 *     'Appointment Confirmed',
 *     "Your appointment has been confirmed for $datetime",
 *     "/appointments/view_appointments.php?id=$appointment_id",
 *     'normal',
 *     'appointment',
 *     $appointment_id
 * );
 */
?>
