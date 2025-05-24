<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $rid = intval($_POST['reg_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE registrations SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $rid);
    $stmt->execute();
    $stmt->close();
}

// Dashboard info
$total = $conn->query("SELECT COUNT(*) FROM registrations")->fetch_row()[0];
$pending = $conn->query("SELECT COUNT(*) FROM registrations WHERE status='Pending'")->fetch_row()[0];
$approved = $conn->query("SELECT COUNT(*) FROM registrations WHERE status='Approved'")->fetch_row()[0];
$rejected = $conn->query("SELECT COUNT(*) FROM registrations WHERE status='Rejected'")->fetch_row()[0];

// List registrations
$res = $conn->query("SELECT * FROM registrations ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge { font-size: 1rem; }
        .table-img { max-width:48px; max-height:48px; border-radius:50%; }
        .comment-area textarea { width: 100%; }
        .dashboard-cards .card { min-width: 180px; }
    </style>
    <script>    function showComments(id) {
        var box = document.getElementById('comments-'+id);
        if (box.style.display === 'none') {
            fetch('comment.php?reg_id='+id)
            .then(resp=>resp.text())
            .then(html=>{ box.innerHTML=html; box.style.display='block'; });
        } else {
            box.style.display = 'none';
        }
    }
    
    function addComment(id) {
        var input = document.getElementById('comment-input-'+id);
        var comment = input.value.trim();
        if (!comment) return;
        
        var formData = new FormData();
        formData.append('reg_id', id);
        formData.append('comment', comment);
        
        fetch('comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if (result.includes('alert-secondary')) {
                var box = document.getElementById('comments-'+id);
                box.innerHTML = result;
                input.value = '';
            } else {
                alert('Error adding comment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding comment');
        });
    }

    function editComment(regId, commentId, oldComment) {
        var newComment = prompt('Edit comment:', oldComment);
        if (!newComment || newComment === oldComment) return;

        var formData = new FormData();
        formData.append('reg_id', regId);
        formData.append('edit_id', commentId);
        formData.append('comment', newComment);
        
        fetch('comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if (result.includes('alert-secondary')) {
                var box = document.getElementById('comments-'+regId);
                box.innerHTML = result;
            } else {
                alert('Error updating comment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating comment');
        });
    }
    </script>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        <a href="logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <div class="dashboard-cards row mb-4">
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <span class="badge bg-primary"><?= $total ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <span class="badge bg-warning text-dark"><?= $pending ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Approved</h5>
                    <span class="badge bg-success"><?= $approved ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Rejected</h5>
                    <span class="badge bg-danger"><?= $rejected ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-hover align-middle shadow-sm bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th><th>Name</th><th>DOB</th><th>Phone</th><th>Status</th><th>Photo</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td>
                <b><?= htmlspecialchars($row['name']) ?></b><br>
                <small class="text-muted"><?= htmlspecialchars($row['address']) ?></small>
            </td>
            <td><?= $row['dob'] ?></td>
            <td><?= $row['phone'] ?></td>
            <td>
                <span class="badge 
                    <?= $row['status']=='Pending'?'bg-warning text-dark':''; ?>
                    <?= $row['status']=='Approved'?'bg-success':''; ?>
                    <?= $row['status']=='Rejected'?'bg-danger':''; ?>
                "><?= $row['status'] ?></span>
            </td>
            <td>
                <?php if ($row['photo']): ?>
                    <img src="uploads/<?= htmlspecialchars($row['photo']) ?>" class="table-img" alt="Photo">
                <?php endif; ?>
            </td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="reg_id" value="<?= $row['id'] ?>">
                    <select name="status" class="form-select d-inline" style="width:auto;">
                        <option value="Pending" <?= $row['status']=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Approved" <?= $row['status']=='Approved'?'selected':''; ?>>Approved</option>
                        <option value="Rejected" <?= $row['status']=='Rejected'?'selected':''; ?>>Rejected</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-outline-primary btn-sm">Update</button>
                </form>
                <button class="btn btn-info btn-sm text-white" onclick="showComments(<?= $row['id'] ?>)">
                    <i class="bi bi-chat-dots"></i> Comments
                </button>
                <div id="comments-<?= $row['id'] ?>" class="comment-area mt-2" style="display:none;">
                    <!-- Loaded by AJAX -->
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>