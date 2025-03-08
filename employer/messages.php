<?php
require_once "../includes/session_check.php";
require_once "../config/database.php";

// Get selected user ID from URL
$selected_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Fetch all conversations for the employer
$conversations_sql = "SELECT DISTINCT 
    u.id as user_id,
    CONCAT(js.first_name, ' ', js.last_name) as full_name,
    js.profile_picture,
    (SELECT message FROM messages 
     WHERE (sender_id = u.id AND receiver_id = ?) 
     OR (sender_id = ? AND receiver_id = u.id) 
     ORDER BY sent_at DESC LIMIT 1) as last_message,
    (SELECT sent_at FROM messages 
     WHERE (sender_id = u.id AND receiver_id = ?) 
     OR (sender_id = ? AND receiver_id = u.id) 
     ORDER BY sent_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages 
     WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    INNER JOIN jobseeker_profiles js ON u.id = js.user_id
    INNER JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = u.id)
    WHERE u.user_type = 'jobseeker'
    GROUP BY u.id
    ORDER BY last_message_time DESC";

$stmt = mysqli_prepare($conn, $conversations_sql);
mysqli_stmt_bind_param($stmt, "iiiiiii", 
    $_SESSION['id'], $_SESSION['id'], 
    $_SESSION['id'], $_SESSION['id'],
    $_SESSION['id'], $_SESSION['id'],
    $_SESSION['id']
);
mysqli_stmt_execute($stmt);
$conversations = mysqli_stmt_get_result($stmt);

// If a user is selected, fetch messages for that conversation
if ($selected_user > 0) {
    // Mark messages as read
    $update_sql = "UPDATE messages SET is_read = 1 
                   WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ii", $selected_user, $_SESSION['id']);
    mysqli_stmt_execute($update_stmt);
    
    // Fetch user details
    $user_sql = "SELECT u.id, u.email, CONCAT(js.first_name, ' ', js.last_name) as full_name,
                 js.profile_picture, js.phone
                 FROM users u
                 INNER JOIN jobseeker_profiles js ON u.id = js.user_id
                 WHERE u.id = ? AND u.user_type = 'jobseeker'";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    mysqli_stmt_bind_param($user_stmt, "i", $selected_user);
    mysqli_stmt_execute($user_stmt);
    $selected_user_details = mysqli_stmt_get_result($user_stmt)->fetch_assoc();
    
    // Fetch messages
    $messages_sql = "SELECT m.*, 
                    CONCAT(js.first_name, ' ', js.last_name) as sender_name,
                    js.profile_picture as sender_picture
                    FROM messages m
                    LEFT JOIN jobseeker_profiles js ON m.sender_id = js.user_id
                    WHERE (sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)
                    ORDER BY sent_at ASC";
    $messages_stmt = mysqli_prepare($conn, $messages_sql);
    mysqli_stmt_bind_param($messages_stmt, "iiii", 
        $selected_user, $_SESSION['id'],
        $_SESSION['id'], $selected_user
    );
    mysqli_stmt_execute($messages_stmt);
    $messages = mysqli_stmt_get_result($messages_stmt);
}

// Handle message sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message']) && $selected_user > 0) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $insert_sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at, is_read) 
                      VALUES (?, ?, ?, CURRENT_TIMESTAMP, 0)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $_SESSION['id'], $selected_user, $message);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Redirect to prevent form resubmission
            header("Location: messages.php?user=" . $selected_user);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PartTimePro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .messages-container {
            height: calc(100vh - 400px);
            min-height: 400px;
            overflow-y: auto;
        }
        .message-bubble {
            max-width: 75%;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
        }
        .message-sent {
            background-color: #007bff;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
        }
        .message-received {
            background-color: #e9ecef;
            color: #212529;
            margin-right: auto;
            border-bottom-left-radius: 0.25rem;
        }
        .conversation-list {
            height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .conversation-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .conversation-item.active {
            background-color: rgba(0,123,255,0.1);
        }
        .profile-picture {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        .default-profile-picture {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-briefcase me-2"></i>
                PartTimePro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-list me-1"></i> My Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-users me-1"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="messages.php">
                            <i class="fas fa-envelope me-1"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </nav>
                <h2>Messages</h2>
            </div>
        </div>

        <div class="row">
            <!-- Conversations List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Conversations</h5>
                    </div>
                    <div class="conversation-list">
                        <?php if (mysqli_num_rows($conversations) > 0): ?>
                            <?php while ($conversation = mysqli_fetch_assoc($conversations)): ?>
                                <a href="?user=<?php echo $conversation['user_id']; ?>" 
                                   class="list-group-item list-group-item-action conversation-item <?php echo $selected_user == $conversation['user_id'] ? 'active' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($conversation['profile_picture'])): ?>
                                                <img src="../<?php echo htmlspecialchars($conversation['profile_picture']); ?>" 
                                                     alt="Profile Picture" class="profile-picture">
                                            <?php else: ?>
                                                <div class="default-profile-picture">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($conversation['full_name']); ?></h6>
                                                <?php if ($conversation['unread_count'] > 0): ?>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo $conversation['unread_count']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-0 small text-muted text-truncate">
                                                <?php echo htmlspecialchars($conversation['last_message']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date('M d, g:i A', strtotime($conversation['last_message_time'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No conversations yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="col-md-8">
                <?php if ($selected_user > 0 && isset($selected_user_details)): ?>
                    <div class="card">
                        <!-- Chat Header -->
                        <div class="card-header bg-white">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($selected_user_details['profile_picture'])): ?>
                                        <img src="../<?php echo htmlspecialchars($selected_user_details['profile_picture']); ?>" 
                                             alt="Profile Picture" class="profile-picture">
                                    <?php else: ?>
                                        <div class="default-profile-picture">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($selected_user_details['full_name']); ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($selected_user_details['email']); ?>
                                        <?php if (!empty($selected_user_details['phone'])): ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($selected_user_details['phone']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Messages Container -->
                        <div class="card-body messages-container" id="messagesContainer">
                            <?php if (mysqli_num_rows($messages) > 0): ?>
                                <?php while ($message = mysqli_fetch_assoc($messages)): ?>
                                    <div class="message-bubble <?php echo $message['sender_id'] == $_SESSION['id'] ? 'message-sent' : 'message-received'; ?>">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        <div class="message-time <?php echo $message['sender_id'] == $_SESSION['id'] ? 'text-white' : 'text-muted'; ?>">
                                            <?php echo date('g:i A', strtotime($message['sent_at'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No messages yet</p>
                                    <p class="text-muted small">Start the conversation by sending a message below</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Message Input -->
                        <div class="card-footer bg-white">
                            <form method="post" class="message-form">
                                <div class="input-group">
                                    <textarea class="form-control" name="message" rows="1" 
                                              placeholder="Type your message..." required></textarea>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h5>Select a Conversation</h5>
                            <p class="text-muted">Choose a conversation from the list to start messaging</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to bottom of messages container
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        window.onload = scrollToBottom;
    </script>
</body>
</html> 