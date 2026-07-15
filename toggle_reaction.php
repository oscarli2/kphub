<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$postId = $input['post_id'] ?? null;
$reactionType = $input['reaction_type'] ?? null;
$userId = $_SESSION['user_id'];

if (!$postId || !$reactionType) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$validReactions = ['like', 'love', 'celebrate', 'insightful'];
if (!in_array($reactionType, $validReactions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
    exit;
}

try {
    // Check if user already reacted to this post
    $checkStmt = $pdo->prepare("SELECT reaction_id FROM post_reactions WHERE post_id = ? AND user_id = ?");
    $checkStmt->execute([$postId, $userId]);
    $existingReaction = $checkStmt->fetch();
    
    if ($existingReaction) {
        // Remove existing reaction
        $deleteStmt = $pdo->prepare("DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?");
        $deleteStmt->execute([$postId, $userId]);
        $userReacted = false;
    } else {
        // Add new reaction
        $insertStmt = $pdo->prepare("INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $insertStmt->execute([$postId, $userId, $reactionType]);
        $userReacted = true;
    }
    
    // Get updated count for this reaction type
    $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_reactions WHERE post_id = ? AND reaction_type = ?");
    $countStmt->execute([$postId, $reactionType]);
    $count = $countStmt->fetch()['count'];
    
    // If user just added a reaction (not removed), create notification for post owner
    if ($userReacted) {
        // Get post owner
        $postOwnerStmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
        $postOwnerStmt->execute([$postId]);
        $postOwnerId = $postOwnerStmt->fetch()['user_id'];
        
        // Only notify if someone else reacted (not self)
        if ($postOwnerId != $userId) {
            // Get reactor's facility
            $reactorStmt = $pdo->prepare("SELECT facility FROM users WHERE user_id = ?");
            $reactorStmt->execute([$userId]);
            $reactorFacility = $reactorStmt->fetch()['facility'];
            
            $message = $reactorFacility . " reacted to your post with " . $reactionType;
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, post_id, from_user_id) VALUES (?, 'post_reaction', ?, ?, ?)");
            $notifStmt->execute([$postOwnerId, $message, $postId, $userId]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => (int)$count,
        'user_reacted' => $userReacted
    ]);
    
} catch (PDOException $e) {
    error_log("Toggle reaction error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>