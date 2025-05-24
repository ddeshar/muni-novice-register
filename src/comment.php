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
        // Return the updated comments list
        $res = $conn->prepare("SELECT id, comment, created_at FROM registration_comments WHERE registration_id=? ORDER BY created_at DESC");
        $res->bind_param("i", $reg_id);
        $res->execute();
        $result = $res->get_result();

        echo "<div class='comment-list'>";
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $count++;
            echo "<div class='comment-item mb-2'>";
            echo "<div class='d-flex justify-content-between align-items-start'>";
            echo "<div class='comment-content'>";
            echo "<div class='text-muted small mb-1'>" . date('M j, Y g:i A', strtotime($row['created_at'])) . "</div>";
            echo "<div>" . nl2br(htmlspecialchars($row['comment'])) . "</div>";
            echo "</div>";
            echo "<button onclick='editComment({$reg_id}, {$row['id']}, `" . htmlspecialchars(addslashes($row['comment'])) . "`)' 
                    class='btn btn-link btn-sm text-muted p-0 ms-2'>
                    <i class='bi bi-pencil'></i>
                </button>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "<script>document.getElementById('comment-count').textContent = '{$count}';</script>";
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
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "<div class='comment-item mb-2'>";
    echo "<div class='d-flex justify-content-between align-items-start'>";
    echo "<div class='comment-content'>";
    echo "<div class='text-muted small mb-1'>" . date('M j, Y g:i A', strtotime($row['created_at'])) . "</div>";
    echo "<div>" . nl2br(htmlspecialchars($row['comment'])) . "</div>";
    echo "</div>";
    echo "<button onclick='editComment({$reg_id}, {$row['id']}, `" . htmlspecialchars(addslashes($row['comment'])) . "`)' 
              class='btn btn-link btn-sm text-muted p-0 ms-2'>
              <i class='bi bi-pencil'></i>
          </button>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";
echo "<script>document.getElementById('comment-count').textContent = '{$count}';</script>";
echo "</div>";
$res->close();
