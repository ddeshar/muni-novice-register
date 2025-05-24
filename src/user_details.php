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

<style>    .monastery-header {
        background: var(--surface-soft);
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .monastery-header h3 {
        color: var(--text-primary);
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }
    
    .monastery-header p {
        color: var(--text-secondary);
        line-height: 1.6;
        font-size: 1.1rem;
    }

    .details-section {
        background: var(--surface);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-light);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-light);
        margin-bottom: 1.5rem;
    }

    .section-header i {
        font-size: 1.5rem;
    }

    .section-header h5 {
        margin: 0;
        color: var(--text-primary);
        font-weight: 600;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .detail-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: var(--surface-soft);
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .detail-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background: var(--surface);
    }

    .detail-item.wide {
        grid-column: 1 / -1;
    }

    .detail-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .detail-icon i {
        font-size: 1.25rem;
    }

    .detail-content {
        flex: 1;
    }

    .detail-content label {
        display: block;
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .detail-content .value {
        font-size: 1rem;
        color: var(--text-primary);
        font-weight: 500;
    }    .photo-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--surface);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    /* Two-column layout fixes */
    .row {
        margin-left: 0;
        margin-right: 0;
    }
    
    .col-lg-8, .col-lg-4 {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    @media (min-width: 992px) {
        .col-lg-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
        }
        .col-lg-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
    }
    
    .photo-preview:hover {
        transform: scale(1.05);
    }    .back-btn {
        color: var(--text-secondary);
        background: var(--surface);
        border: 1px solid var(--border-light);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        text-decoration: none;
    }
    
    .back-btn:hover {
        color: var(--text-primary);
        background: var(--surface-soft);
        transform: translateX(-5px);
    }

    .card {
        border: none;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        border-radius: 20px;
        background: var(--surface);
    }.status-label {
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
        height: fit-content;
        display: flex;
        flex-direction: column;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
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
    }    @media (max-width: 768px) {
        .card {
            margin: 0.5rem;
            border-radius: 15px;
            padding: 1rem !important;
        }
        
        .monastery-header {
            padding: 1rem !important;
            margin-bottom: 1.5rem !important;
        }
        
        .monastery-header h3 {
            font-size: 1.25rem !important;
            line-height: 1.3;
            margin-bottom: 0.75rem !important;
        }
        
        .monastery-header p {
            font-size: 0.85rem !important;
            line-height: 1.4;
            margin-bottom: 0.5rem !important;
        }
        
        .monastery-header br {
            display: none;
        }
        
        .photo-preview {
            width: 100px !important;
            height: 100px !important;
        }
        
        .profile-header {
            border-radius: 0;
            margin: -1rem -1rem 2rem -1rem;
            text-align: center;
            padding: 1rem !important;
        }

        .detail-section {
            padding: 1rem;
        }

        .comment-box {
            border-radius: 0;
            margin: 0 -1rem;
        }
          .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .btn-lg.me-2 {
            margin-right: 0 !important;
        }
        
        .detail-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .section-header h5 {
            font-size: 1.1rem !important;
        }
          /* Action buttons container on mobile */
        .text-center.mt-4 {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        /* Ensure two-column layout collapses properly */
        .col-lg-8, .col-lg-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .col-lg-4 {
            margin-top: 2rem;
        }
    }
    
    /* Extra small devices (phones, less than 576px) */
    @media (max-width: 575.98px) {
        .card {
            margin: 0.25rem !important;
            padding: 0.75rem !important;
            border-radius: 12px !important;
        }
        
        .monastery-header {
            padding: 0.75rem !important;
        }
        
        .monastery-header h3 {
            font-size: 1.1rem !important;
        }
        
        .monastery-header p {
            font-size: 0.8rem !important;
        }
        
        .photo-preview {
            width: 80px !important;
            height: 80px !important;
        }
          .btn {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .btn-lg {
            padding: 0.875rem 1.25rem;
            font-size: 0.95rem;
        }
        
        .section-header h5 {
            font-size: 1rem !important;
        }
        
        .detail-item {
            padding: 0.75rem;
        }
        
        .detail-icon {
            width: 35px;
            height: 35px;
        }
        
        .detail-icon i {
            font-size: 1rem;
        }
        
        /* Action buttons full width on small screens */
        .text-center.mt-4 {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .text-center.mt-4 .btn {
            width: 100%;
            margin: 0;
        }
        
        /* Improve touch targets */
        .btn, .form-control, .form-select {
            min-height: 48px;
        }
    }
</style>

<main class="container-fluid px-2 py-3">
    
    <div class="row justify-content-center g-0">
        <div class="col-12 col-lg-10 col-md-12">
            <div class="card p-2 p-md-4 animate__animated animate__fadeIn">
                <!-- Back button -->
                <a href="admin.php" class="btn mb-3 back-btn d-print-none">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>

                <!-- Status Management Section (Admin Only) -->
                <div class="profile-header text-center text-md-start mb-4 d-print-none">
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
                            <h1 class="h3 mb-2"><?= htmlspecialchars($user['name']) ?></h1>
                            <p class="mb-3">ID: #<?= $user['id'] ?></p>
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
                    </div>                </div>

                <!-- Monastery Header -->
                <div class="monastery-header mb-4 text-center">
                    <h3 class="mb-2 fw-bold">मुनि विहार (श्री धम्मोतम महाविहार)</h3>
                    <p class="mb-2">इनायो टोल, वडा नं. ७, भक्तपुर नगरपालिका, भक्तपुर जिल्ला,<br>
                    बागमती प्रदेश, नेपाल। फोन नं. ०१-६६१६४६४</p>
                    <p class="mb-4">मिर्रराष्ट्र थाइलंडका १९ आ राजगुरु भिक्षु परमपूज्य समतेच बर संपराजचाउ क्रमल्हुवड वजिसप्राणसंवर<br>
                    (सुवड्उन महाधेर) को संरक्षणमा संचालित सामूहिक प्रबज्या तथा उपसम्पदा योजनामा सहभागिताको लागि<br>
                    आवेदन-पत्र</p>
                </div>

                <!-- Student Photo -->
                <?php if ($user['photo']): ?>
                <div class="text-center mb-4">
                    <img src="uploads/<?= htmlspecialchars($user['photo']) ?>" 
                         class="photo-preview animate__animated animate__fadeIn" 
                         alt="Student Photo">
                </div>
                <?php endif; ?>

                <!-- Two Column Layout: Details + Comments -->
                <div class="row">
                    <!-- Student Details Column -->
                    <div class="col-lg-8">
                        <div class="registration-details">
                        <!-- Personal Information Section -->
                        <div class="details-section">
                            <div class="section-header">
                                <i class="bi bi-person-badge text-primary"></i>
                                <h5 class="mb-3">व्यक्तिगत विवरण</h5>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-hash text-secondary"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>दर्ता नं.</label>
                                        <div class="value">#<?= $user['id'] ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>पुरा नाम</label>
                                        <div class="value"><?= htmlspecialchars($user['name']) ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-calendar3 text-info"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>जन्म मिति</label>
                                        <div class="value"><?= htmlspecialchars($user['dob']) ?></div>
                                    </div>
                                </div>
                                <div class="detail-item wide">
                                    <div class="detail-icon">
                                        <i class="bi bi-phone text-success"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>सम्पर्क नम्बर</label>
                                        <div class="value"><a href="tel:<?= htmlspecialchars($user['phone']) ?>"><?= htmlspecialchars($user['phone']) ?></a></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Educational Details Section -->
                        <div class="details-section mt-4">
                            <div class="section-header">
                                <i class="bi bi-book text-warning"></i>
                                <h5 class="mb-3">शैक्षिक विवरण</h5>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-building text-danger"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>विद्यालय</label>
                                        <div class="value"><?= htmlspecialchars($user['school_name']) ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-mortarboard text-success"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>उत्तीर्ण कक्षा</label>
                                        <div class="value"><?= htmlspecialchars($user['passed_class']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family Information Section -->
                        <div class="details-section mt-4">
                            <div class="section-header">
                                <i class="bi bi-people text-info"></i>
                                <h5 class="mb-3">अभिभावक विवरण</h5>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>बाबुको नाम</label>
                                        <div class="value"><?= htmlspecialchars($user['father_name']) ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-person-heart text-danger"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>आमाको नाम</label>
                                        <div class="value"><?= htmlspecialchars($user['mother_name']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="details-section mt-4">
                            <div class="section-header">
                                <i class="bi bi-geo-alt text-success"></i>
                                <h5 class="mb-3">ठेगाना विवरण</h5>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-house-door text-primary"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>स्थायी ठेगाना</label>
                                        <div class="value"><?= htmlspecialchars($user['permanent_address']) ?></div>
                                    </div>
                                </div>
                                <?php if ($user['temporary_address']): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="bi bi-house text-secondary"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>अस्थायी ठेगाना</label>
                                        <div class="value"><?= htmlspecialchars($user['temporary_address']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>                            </div>
                        </div>                        <!-- Action Buttons -->
                        <div class="text-center mt-4 d-print-none">
                            <a href="admin.php" class="btn btn-primary btn-lg me-2 px-4 py-2">
                                <i class="bi bi-arrow-left"></i> बाकसमा जानुहोस्
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-primary btn-lg px-4 py-2">
                                <i class="bi bi-printer"></i> विवरण प्रिन्ट गर्नुहोस्
                            </button>
                        </div>
                        </div> <!-- Close registration-details -->
                    </div> <!-- Close col-lg-8 -->
                    <!-- Comments Section Column -->
                    <div class="col-lg-4 d-print-none">
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
        </div>
    </div>
</main>

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
