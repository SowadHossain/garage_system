<?php
// chat/staff_chat.php - Staff Chat Interface

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if staff is logged in
if (empty($_SESSION['staff_id'])) {
    header("Location: ../public/staff_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];

// Get all conversations
$conversations_stmt = $conn->prepare("SELECT c.conversation_id, c.subject, c.status, c.created_at, c.updated_at,
                                             cust.name as customer_name,
                                             (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.conversation_id AND m.is_read = 0 AND m.sender_type = 'customer') as unread_count,
                                             (SELECT message_text FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                                             (SELECT created_at FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_message_time
                                      FROM conversations c
                                      JOIN customers cust ON c.customer_id = cust.customer_id
                                      ORDER BY c.updated_at DESC");
$conversations_stmt->execute();
$conversations = $conversations_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conversations_stmt->close();

// Get selected conversation ID
$selected_conversation_id = (int)($_GET['id'] ?? 0);
$selected_conversation = null;
$messages = [];

if ($selected_conversation_id > 0) {
    // Get conversation
    $conv_stmt = $conn->prepare("SELECT c.*, cust.name as customer_name, cust.email as customer_email, cust.phone as customer_phone
                                 FROM conversations c
                                 JOIN customers cust ON c.customer_id = cust.customer_id
                                 WHERE c.conversation_id = ?");
    $conv_stmt->bind_param("i", $selected_conversation_id);
    $conv_stmt->execute();
    $result = $conv_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_conversation = $result->fetch_assoc();
        
        // Mark messages as read
        $mark_read = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'customer'");
        $mark_read->bind_param("i", $selected_conversation_id);
        $mark_read->execute();
        $mark_read->close();
        
        // Get messages
        $messages_stmt = $conn->prepare("SELECT m.*, 
                                         CASE WHEN m.sender_type = 'customer' THEN c.name ELSE s.name END as sender_name
                                         FROM messages m
                                         LEFT JOIN customers c ON m.sender_id = c.customer_id AND m.sender_type = 'customer'
                                         LEFT JOIN staff s ON m.sender_id = s.staff_id AND m.sender_type = 'staff'
                                         WHERE m.conversation_id = ?
                                         ORDER BY m.created_at ASC");
        $messages_stmt->bind_param("i", $selected_conversation_id);
        $messages_stmt->execute();
        $messages = $messages_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $messages_stmt->close();
    }
    $conv_stmt->close();
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $selected_conversation_id > 0) {
    $message_text = trim($_POST['message_text'] ?? '');
    
    if (!empty($message_text)) {
        $insert_stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, created_at) 
                                       VALUES (?, 'staff', ?, ?, NOW())");
        $insert_stmt->bind_param("iis", $selected_conversation_id, $staff_id, $message_text);
        $insert_stmt->execute();
        $insert_stmt->close();
        
        // Update conversation timestamp
        $update_conv = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE conversation_id = ?");
        $update_conv->bind_param("i", $selected_conversation_id);
        $update_conv->execute();
        $update_conv->close();
        
        header("Location: staff_chat.php?id=" . $selected_conversation_id);
        exit;
    }
}

// Handle close conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_conversation']) && $selected_conversation_id > 0) {
    $close_stmt = $conn->prepare("UPDATE conversations SET status = 'closed' WHERE conversation_id = ?");
    $close_stmt->bind_param("i", $selected_conversation_id);
    $close_stmt->execute();
    $close_stmt->close();
    
    header("Location: staff_chat.php?id=" . $selected_conversation_id);
    exit;
}

// Handle reopen conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen_conversation']) && $selected_conversation_id > 0) {
    $reopen_stmt = $conn->prepare("UPDATE conversations SET status = 'open' WHERE conversation_id = ?");
    $reopen_stmt->bind_param("i", $selected_conversation_id);
    $reopen_stmt->execute();
    $reopen_stmt->close();
    
    header("Location: staff_chat.php?id=" . $selected_conversation_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Messages - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            height: 100vh;
            overflow: hidden;
        }
        
        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .chat-container {
            display: flex;
            height: calc(100vh - 72px);
        }
        
        .conversations-sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .filter-tab {
            flex: 1;
            padding: 0.5rem;
            background: #f8f9fa;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: #e7f1ff;
            border-left: 4px solid var(--primary-color);
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .customer-name {
            font-weight: 600;
            color: #212529;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .conversation-subject {
            font-size: 0.875rem;
            color: #6c757d;
            margin: 0.25rem 0;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .conversation-preview {
            font-size: 0.875rem;
            color: #6c757d;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .unread-badge {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        
        .status-open { background: #d1e7dd; color: #0f5132; }
        .status-closed { background: #f8d7da; color: #842029; }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }
        
        .chat-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-title-section {
            flex: 1;
        }
        
        .chat-customer {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .chat-subject {
            font-size: 0.95rem;
            color: #6c757d;
            margin: 0.25rem 0 0 0;
        }
        
        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }
        
        .message {
            display: flex;
            margin-bottom: 1.5rem;
        }
        
        .message.staff {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            position: relative;
        }
        
        .message.customer .message-bubble {
            background: white;
            border: 1px solid #e9ecef;
        }
        
        .message.staff .message-bubble {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .message-sender {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .message.customer .message-sender {
            color: #198754;
        }
        
        .message.staff .message-sender {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .message-text {
            margin: 0;
            line-height: 1.5;
        }
        
        .message-time {
            font-size: 0.7rem;
            margin-top: 0.5rem;
            opacity: 0.7;
        }
        
        .chat-input-area {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem;
        }
        
        .input-group {
            display: flex;
            gap: 0.75rem;
        }
        
        .message-input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 24px;
            padding: 0.75rem 1.25rem;
            resize: none;
            font-family: inherit;
        }
        
        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn-send {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            text-align: center;
            padding: 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .conversations-sidebar {
                width: 100%;
            }
            
            .chat-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <a href="../public/staff_dashboard.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Staff Dashboard
        </a>
        <a href="../public/logout.php" class="btn btn-sm btn-light">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>
    
    <div class="chat-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">
                    <i class="bi bi-chat-dots me-2"></i>Customer Messages
                </h2>
            </div>
            
            <div class="conversations-list">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <p>No conversations yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="staff_chat.php?id=<?php echo $conv['conversation_id']; ?>" 
                           class="conversation-item <?php echo ($conv['conversation_id'] == $selected_conversation_id) ? 'active' : ''; ?>">
                            <div class="conversation-header">
                                <h3 class="customer-name">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <?php echo htmlspecialchars($conv['customer_name']); ?>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </h3>
                                <span class="conversation-time">
                                    <?php 
                                    $time = strtotime($conv['last_message_time'] ?? $conv['updated_at']);
                                    if (date('Y-m-d') == date('Y-m-d', $time)) {
                                        echo date('g:i A', $time);
                                    } else {
                                        echo date('M d', $time);
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="conversation-subject">
                                <i class="bi bi-chat-quote me-1"></i>
                                <?php echo htmlspecialchars($conv['subject']); ?>
                            </p>
                            <?php if ($conv['last_message']): ?>
                                <p class="conversation-preview"><?php echo htmlspecialchars($conv['last_message']); ?></p>
                            <?php endif; ?>
                            <span class="status-badge status-<?php echo $conv['status']; ?>">
                                <?php echo ucfirst($conv['status']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($selected_conversation): ?>
                <div class="chat-header">
                    <div class="chat-title-section">
                        <h2 class="chat-customer">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($selected_conversation['customer_name']); ?>
                        </h2>
                        <p class="chat-subject"><?php echo htmlspecialchars($selected_conversation['subject']); ?></p>
                        <small class="text-muted">
                            <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($selected_conversation['customer_email']); ?>
                            <i class="bi bi-telephone ms-3 me-1"></i><?php echo htmlspecialchars($selected_conversation['customer_phone']); ?>
                        </small>
                    </div>
                    <div class="chat-actions">
                        <span class="status-badge status-<?php echo $selected_conversation['status']; ?>">
                            <?php echo ucfirst($selected_conversation['status']); ?>
                        </span>
                        <?php if ($selected_conversation['status'] === 'open'): ?>
                            <form method="POST" action="staff_chat.php?id=<?php echo $selected_conversation_id; ?>" style="display: inline;">
                                <button type="submit" name="close_conversation" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-circle"></i> Close
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="staff_chat.php?id=<?php echo $selected_conversation_id; ?>" style="display: inline;">
                                <button type="submit" name="reopen_conversation" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-arrow-clockwise"></i> Reopen
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_type']; ?>">
                            <div class="message-bubble">
                                <div class="message-sender">
                                    <?php echo htmlspecialchars($msg['sender_name']); ?>
                                </div>
                                <p class="message-text"><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                                <div class="message-time">
                                    <?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($selected_conversation['status'] === 'open'): ?>
                    <div class="chat-input-area">
                        <form method="POST" action="staff_chat.php?id=<?php echo $selected_conversation_id; ?>">
                            <div class="input-group">
                                <textarea name="message_text" 
                                          class="message-input" 
                                          placeholder="Type your reply..." 
                                          rows="1"
                                          required></textarea>
                                <button type="submit" name="send_message" class="btn-send">
                                    <i class="bi bi-send-fill"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-input-area text-center">
                        <p class="text-muted mb-0">
                            <i class="bi bi-lock me-2"></i>This conversation is closed
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <h3>Select a conversation</h3>
                    <p>Choose a customer conversation from the sidebar to view and respond</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Auto-grow textarea
        const messageInput = document.querySelector('.message-input');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    </script>
</body>
</html>
