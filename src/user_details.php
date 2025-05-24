<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Get user details
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: admin.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: admin.php");
    exit;
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE registrations SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $user['status'] = $status;
}

$title = "User Details - " . htmlspecialchars($user['name']);
require 'includes/header.php';
?>

<style>    .back-btn {
        color: #6c757d;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .back-btn:hover {
        color: #495057;
        background: #f8f9fa;
        transform: translateX(-5px);
    }    .status-label {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-select {
        min-width: 200px;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        background: white;
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: #495057;
        font-weight: 500;
        cursor: pointer;
    }

    .status-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
    }    .status-select option {
        background: var(--surface);
        color: var(--text-secondary);
        padding: 10px;
    }

    .status-badge {
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
    }

    .status-badge i {
        font-size: 0.5rem;
    }    .status-badge.status-pending i { color: var(--warning); }
    .status-badge.status-approved i { color: var(--success); }
    .status-badge.status-rejected i { color: var(--danger); }

    .profile-header {
        background: var(--primary-gradient);
        padding: 2rem;
        color: white;
        border-radius: 15px;
        margin-bottom: 2rem;
    }

    .profile-photo {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }

    .detail-card {
        border-radius: 12px;
        overflow: hidden;
    }

    .detail-section {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .detail-section:last-child {
        border-bottom: none;
    }    .detail-label {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }    .detail-value {
        font-size: 1rem;
        color: var(--text-primary);
    }

    .comments-section {
        margin-top: 2rem;
    }    .comment-box {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .comment-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        background: #f8f9fa;
    }

    .comment-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        max-height: 400px;
    }

    .comment-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        border-left: 3px solid var(--primary);
    }

    .comment-meta {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }

    .comment-text {
        color: #212529;
    }

    @media (max-width: 768px) {
        .profile-header {
            border-radius: 0;
            margin: -1rem -1rem 2rem -1rem;
            text-align: center;
        }

        .detail-section {
            padding: 1rem;
        }

        .comment-box {
            border-radius: 0;
            margin: 0 -1rem;
        }
    }
</style>

<div class="container py-4">    <!-- Back button -->
    <a href="admin.php" class="btn mb-3 back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <!-- Profile Header -->
    <div class="profile-header text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-auto text-center">
                <?php if ($user['photo']): ?>
                    <img src="uploads/<?= htmlspecialchars($user['photo']) ?>" 
                         class="profile-photo" 
                         alt="Profile photo">
                <?php else: ?>
                    <div class="profile-photo bg-light d-flex align-items-center justify-content-center">
                        <i class="bi bi-person display-4 text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md">
                <h1 class="h3 mb-2"><?= htmlspecialchars($user['name']) ?></h1>                <p class="mb-3">ID: #<?= $user['id'] ?></p>
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-3">
                    <div class="status-indicator">
                        <div class="status-label mb-2">Current Status:</div>
                        <span class="status-badge status-<?= strtolower($user['status']) ?>">
                            <i class="bi bi-circle-fill me-2"></i><?= $user['status'] ?>
                        </span>
                    </div>
                    <div class="status-change">
                        <div class="status-label mb-2">Change Status:</div>
                        <form method="POST" class="d-flex align-items-center">
                            <select name="status" class="form-select status-select" 
                                    onchange="this.form.submit()">
                                <option value="" disabled selected>Select new status...</option>
                                <option value="Pending" <?= $user['status']=='Pending'?'selected':'' ?>>Pending</option>
                                <option value="Approved" <?= $user['status']=='Approved'?'selected':'' ?>>Approved</option>
                                <option value="Rejected" <?= $user['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Details -->
        <div class="col-lg-8">
            <div class="card detail-card mb-4">
                <div class="detail-section">
                    <h5 class="mb-3">Personal Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Date of Birth</div>
                            <div class="detail-value"><?= htmlspecialchars($user['dob']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Phone Number</div>
                            <div class="detail-value"><?= htmlspecialchars($user['phone']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="mb-3">Educational Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">School Name</div>
                            <div class="detail-value"><?= htmlspecialchars($user['school_name']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Passed Class</div>
                            <div class="detail-value"><?= htmlspecialchars($user['passed_class']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="mb-3">Family Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">Father's Name</div>
                            <div class="detail-value"><?= htmlspecialchars($user['father_name']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Mother's Name</div>
                            <div class="detail-value"><?= htmlspecialchars($user['mother_name']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="mb-3">Address Information</h5>
                    <div class="mb-3">
                        <div class="detail-label">Permanent Address</div>
                        <div class="detail-value"><?= htmlspecialchars($user['permanent_address']) ?></div>
                    </div>
                    <?php if ($user['temporary_address']): ?>
                    <div>
                        <div class="detail-label">Temporary Address</div>
                        <div class="detail-value"><?= htmlspecialchars($user['temporary_address']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>        <!-- Comments Section -->
        <div class="col-lg-4">
            <div class="comment-box">
                <div class="comment-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Comments</h5>
                    <span class="badge bg-primary rounded-pill" id="comment-count"></span>
                </div>
                  <div class="p-3">
                    <form id="comment-form-<?= $user['id'] ?>" onsubmit="handleCommentSubmit(event, <?= $user['id'] ?>)" class="mb-0">
                        <div class="input-group">
                            <textarea id="comment-input-<?= $user['id'] ?>" class="form-control" 
                                    rows="2" placeholder="Write a comment..." required></textarea>
                            <input type="hidden" id="comment-edit-id-<?= $user['id'] ?>" value="">
                            <button type="submit" class="btn btn-primary" id="comment-submit-<?= $user['id'] ?>">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="comment-list" id="comments-<?= $user['id'] ?>">
                    <!-- Comments loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load comments immediately
    showComments(<?= $user['id'] ?>);
});

// Cache comments to reduce server load
const commentCache = new Map();

function showComments(id) {
    const box = document.getElementById('comments-'+id);
    
    // Show loading indicator
    box.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch('comment.php?reg_id='+id)
        .then(resp => resp.text())
        .then(html => {
            box.innerHTML = html;
            commentCache.set(id, html); // Cache the result
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            box.innerHTML = '<div class="alert alert-danger">Error loading comments. Please try again.</div>';
        });
}

function handleCommentSubmit(event, id) {
    event.preventDefault();
    const input = document.getElementById('comment-input-'+id);
    const editId = document.getElementById('comment-edit-id-'+id);
    const submitBtn = document.getElementById('comment-submit-'+id);
    const comment = input.value.trim();
    
    if (!comment) return;
    
    submitBtn.disabled = true;
    
    const data = new FormData();
    data.append('reg_id', id);
    data.append('comment', comment);
    
    if (editId.value) {
        data.append('edit_id', editId.value);
    }
      fetch('comment.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.text())
    .then(html => {
        if (html.includes('comment-list')) {
            // Comment saved successfully, refresh the page
            window.location.reload();
        } else {
            throw new Error('Failed to save comment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving comment');
    })
    .finally(() => {
        submitBtn.disabled = false;
    });
}

function editComment(regId, commentId, oldComment) {
    const input = document.getElementById('comment-input-'+regId);
    const editId = document.getElementById('comment-edit-id-'+regId);
    const submitBtn = document.getElementById('comment-submit-'+regId);
    
    input.value = oldComment;
    editId.value = commentId;
    submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Update';
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-warning');
    input.focus();
};
</script>

<?php require 'includes/footer.php'; ?>
