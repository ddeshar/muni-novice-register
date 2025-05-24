<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) exit("Login required.");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_id = intval($_POST['reg_id']);
    $comment = trim($_POST['comment']);
    
    if (isset($_POST['edit_id'])) {
        // Edit existing comment
        $edit_id = intval($_POST['edit_id']);
        $stmt = $conn->prepare("UPDATE registration_comments SET comment = ? WHERE id = ? AND registration_id = ?");
        $stmt->bind_param("sii", $comment, $edit_id, $reg_id);
    } else {
        // Add new comment
        $stmt = $conn->prepare("INSERT INTO registration_comments (registration_id, comment) VALUES (?, ?)");
        $stmt->bind_param("is", $reg_id, $comment);
    }

    if ($stmt->execute()) {
        // Return the updated comments list along with the input form
        $res = $conn->prepare("SELECT id, comment, created_at FROM registration_comments WHERE registration_id=? ORDER BY created_at DESC");
        $res->bind_param("i", $reg_id);
        $res->execute();
        $result = $res->get_result();
        
        echo "<div class='comment-list'>";
        while ($row = $result->fetch_assoc()) {
            echo "<div class='alert alert-secondary mb-1 py-2 px-3 d-flex justify-content-between align-items-start'>";
            echo "<div><b>[{$row['created_at']}]</b> " . htmlspecialchars($row['comment']) . "</div>";
            echo "<div class='btn-group btn-group-sm'>";
            echo "<button onclick='editComment({$reg_id}, {$row['id']}, `" . htmlspecialchars(addslashes($row['comment'])) . "`)' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i></button>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "<textarea id='comment-input-$reg_id' rows='2' placeholder='Add comment...' class='form-control mb-2'></textarea>";
        echo "<button onclick='addComment($reg_id)' class='btn btn-primary btn-sm mb-2'><i class='bi bi-plus-circle'></i> Add</button>";
        $res->close();
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// Display all comments for a registration
if (isset($_GET['reg_id'])) {
    $reg_id = intval($_GET['reg_id']);
} elseif (isset($_POST['reg_id'])) {
    $reg_id = intval($_POST['reg_id']);
} else {
    exit;
}
$res = $conn->prepare("SELECT id, comment, created_at FROM registration_comments WHERE registration_id=? ORDER BY created_at DESC");
$res->bind_param("i", $reg_id);
$res->execute();
$result = $res->get_result();

echo "<div class='comment-list'>";
while ($row = $result->fetch_assoc()) {
    echo "<div class='alert alert-secondary mb-1 py-2 px-3 d-flex justify-content-between align-items-start'>";
    echo "<div><b>[{$row['created_at']}]</b> " . htmlspecialchars($row['comment']) . "</div>";
    echo "<div class='btn-group btn-group-sm'>";
    echo "<button onclick='editComment({$reg_id}, {$row['id']}, `" . htmlspecialchars(addslashes($row['comment'])) . "`)' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i></button>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";
echo "<textarea id='comment-input-$reg_id' rows='2' placeholder='Add comment...' class='form-control mb-2'></textarea>";
echo "<button onclick='addComment($reg_id)' class='btn btn-primary btn-sm mb-2'><i class='bi bi-plus-circle'></i> Add</button>";
$res->close();
?>