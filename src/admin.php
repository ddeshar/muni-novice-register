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

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_records = $conn->query("SELECT COUNT(*) FROM registrations")->fetch_row()[0];
$total_pages = ceil($total_records / $per_page);

// List registrations with pagination
$res = $conn->prepare("SELECT * FROM registrations ORDER BY created_at DESC LIMIT ? OFFSET ?");
$res->bind_param("ii", $per_page, $offset);
$res->execute();
$res = $res->get_result();
?>
<?php 
$title = "Admin Dashboard";
require 'includes/header.php';
?>

<style>    .stats-card {
        border-radius: 15px;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }
    
    .stats-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1.5rem;
    }
      .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .stats-card:hover .stats-icon::after {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0.15;
    }
      .stats-icon {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        display: inline-block;
        position: relative;
        z-index: 1;
    }
    
    .stats-card:nth-child(1) .stats-icon { color: #4361ee; }
    .stats-card:nth-child(2) .stats-icon { color: #f7b731; }
    .stats-card:nth-child(3) .stats-icon { color: #2ecc71; }
    .stats-card:nth-child(4) .stats-icon { color: #e74c3c; }
    
    .stats-icon::after {
        content: '';
        position: absolute;
        width: 2.5em;
        height: 2.5em;
        background: currentColor;
        border-radius: 50%;
        opacity: 0.1;
        z-index: -1;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        transition: all 0.3s ease;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 600;
    }
    
    .table-img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: opacity 0.3s ease-in-out;
    }
    
    .lazy {
        opacity: 0;
    }
    
    img:not(.lazy) {
        opacity: 1;
    }
    
    .registration-row {
        transition: background-color 0.3s;
    }
    
    .registration-row:hover {
        background-color: rgba(111, 134, 214, 0.05);
    }
      .comment-area {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }
    
    .comment-bubble {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 0.5rem;
        padding: 1rem;
    }
    
    .comment-list {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.2) transparent;
    }
    
    .comment-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .comment-list::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .comment-list::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.2);
        border-radius: 3px;
    }
      .comment-area textarea {
        resize: none;
        border-radius: 8px;
        transition: all 0.2s ease;
        min-height: 200px;
        height: auto;
    }
    
    .comment-area textarea:focus {
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        border-color: var(--primary);
    }

    @media (max-width: 768px) {
        .comment-area textarea {
            min-height: 250px;
            font-size: 16px; /* Prevents iOS zoom on focus */
            line-height: 1.4;
            padding: 12px;
        }
    }
    
    .comment-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
    }
    
    .mobile-menu {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        padding: 0.5rem;
    }    @media (max-width: 768px) {
        .stats-card {
            margin-bottom: 0.5rem;
        }
        
        .stats-card .card-body {
            padding: 1rem;
            justify-content: flex-start;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 0;
        }
        
        .stats-icon-wrapper {
            min-width: 60px;
            display: flex;
            justify-content: center;
        }
        
        .stats-number {
            font-size: 1.5rem;
            line-height: 1.2;
        }
        
        .card-title {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .table-responsive {
            margin: 0 -1rem;
        }
        
        .mobile-menu {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .main-container {
            padding-bottom: 70px;
        }
        
        .comment-area {
            margin: 0.5rem -1rem;
            padding: 0.75rem;
            border-radius: 0;
            background: #fff;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.1);
        }
        
        .comment-list .alert {
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        #comment-input {
            font-size: 0.95rem;
            padding: 0.5rem;
        }
        
        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }
    
    /* Status Colors */
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-approved {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>    <script>
    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Cache comments to reduce server load
    const commentCache = new Map();
    
    function showComments(id) {
        const box = document.getElementById('comments-'+id);
        if (box.style.display === 'none') {
            box.style.display = 'block';
            
            // Check cache first
            if (commentCache.has(id)) {
                box.innerHTML = commentCache.get(id);
                return;
            }
            
            // Show loading indicator
            box.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            
            fetch('comment.php?reg_id='+id)
                .then(resp => resp.text())
                .then(html => {
                    box.innerHTML = html;
                    commentCache.set(id, html); // Cache the result
                });
        } else {
            box.style.display = 'none';
        }
    }
    
    const addComment = debounce(function(id) {
        const input = document.getElementById('comment-input-'+id);
        const comment = input.value.trim();
        if (!comment) return;
        
        const box = document.getElementById('comments-'+id);
        const btn = box.querySelector('button[onclick^="addComment"]');
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('reg_id', id);
        formData.append('comment', comment);
        
        fetch('comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if (result.includes('alert-secondary')) {
                box.innerHTML = result;
                input.value = '';
                commentCache.set(id, result); // Update cache
            } else {
                throw new Error('Failed to add comment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding comment');
        })
        .finally(() => {
            btn.disabled = false;
        });
    }, 500);

    const editComment = debounce(function(regId, commentId, oldComment) {
        const newComment = prompt('Edit comment:', oldComment);
        if (!newComment || newComment === oldComment) return;
        
        const box = document.getElementById('comments-'+regId);
        const btn = box.querySelector(`button[onclick*="editComment(${regId}, ${commentId}"]`);
        if (btn) btn.disabled = true;
        
        const formData = new FormData();
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
                box.innerHTML = result;
                commentCache.set(regId, result); // Update cache
            } else {
                throw new Error('Failed to update comment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating comment');
        })
        .finally(() => {
            if (btn) btn.disabled = false;
        });
    }, 500);
    </script>
</head>
<div class="main-container container-fluid py-4">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-speedometer2"></i>
                <span class="d-none d-sm-inline">Admin Dashboard</span>
            </h2>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-primary d-none d-sm-flex align-items-center gap-2">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="logout.php" class="btn btn-outline-danger d-none d-sm-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center d-flex align-items-center">
                    <div class="stats-icon-wrapper me-3">
                        <i class="bi bi-people-fill stats-icon"></i>
                    </div>
                    <div class="stats-info text-start">
                        <div class="stats-number"><?= $total ?></div>
                        <h5 class="card-title mb-0">Total Registrations</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center d-flex align-items-center">
                    <div class="stats-icon-wrapper me-3">
                        <i class="bi bi-hourglass-split stats-icon"></i>
                    </div>
                    <div class="stats-info text-start">
                        <div class="stats-number"><?= $pending ?></div>
                        <h5 class="card-title mb-0">Pending Review</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center d-flex align-items-center">
                    <div class="stats-icon-wrapper me-3">
                        <i class="bi bi-check-circle-fill stats-icon"></i>
                    </div>
                    <div class="stats-info text-start">
                        <div class="stats-number"><?= $approved ?></div>
                        <h5 class="card-title mb-0">Approved</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center d-flex align-items-center">
                    <div class="stats-icon-wrapper me-3">
                        <i class="bi bi-x-circle-fill stats-icon"></i>
                    </div>
                    <div class="stats-info text-start">
                        <div class="stats-number"><?= $rejected ?></div>
                        <h5 class="card-title mb-0">Rejected</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-check"></i> Registration List
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Student</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $res->fetch_assoc()): ?>
                    <tr class="registration-row">
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">                                <?php if ($row['photo']): ?>
                                    <img src="<?= generatePlaceholder() ?>" 
                                         data-src="uploads/<?= htmlspecialchars($row['photo']) ?>"
                                         class="table-img lazy" 
                                         loading="lazy"
                                         alt="Photo of <?= htmlspecialchars($row['name']) ?>"
                                         width="48" height="48">
                                <?php else: ?>
                                    <div class="table-img bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-muted"></i>
                                    </div>
                                <?php endif; ?>                                <div>
                                    <a href="user_details.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                        <small class="text-muted">ID: #<?= $row['id'] ?></small>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?= $row['phone'] ?></div>
                            <small class="text-muted"><?= $row['dob'] ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <form method="POST" style="display:inline-flex;">
                                    <input type="hidden" name="reg_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-select form-select-sm me-2" 
                                            onchange="this.form.submit()" style="width:auto;">
                                        <option value="Pending" <?= $row['status']=='Pending'?'selected':''; ?>>Pending</option>
                                        <option value="Approved" <?= $row['status']=='Approved'?'selected':''; ?>>Approved</option>
                                        <option value="Rejected" <?= $row['status']=='Rejected'?'selected':''; ?>>Rejected</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>                                <a href="user_details.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i>
                                    <span class="d-none d-md-inline ms-1">View Details</span>
                                </a>
                            </div>
                        </td>
                    </tr>                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav aria-label="Registration pages">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++) {
                        echo '<li class="page-item '.($i == $page ? 'active' : '').'">
                              <a class="page-link" href="?page='.$i.'">'.$i.'</a>
                              </li>';
                    }
                    
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <a href="index.php" class="btn btn-link text-dark">
            <i class="bi bi-house"></i>
            <small class="d-block">Home</small>
        </a>
        <button class="btn btn-link text-dark" onclick="document.documentElement.scrollTop = 0">
            <i class="bi bi-arrow-up-circle"></i>
            <small class="d-block">Top</small>
        </button>
        <a href="logout.php" class="btn btn-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <small class="d-block">Logout</small>
        </a>
    </div>
</div>

<!-- Image lazy loading -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for lazy loading
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px'
    });

    // Observe all lazy images
    document.querySelectorAll('img.lazy').forEach(img => {
        imageObserver.observe(img);
    });

    // Handle pagination state
    const params = new URLSearchParams(window.location.search);
    const currentPage = params.get('page') || '1';
    
    // Preserve page parameter when updating status
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.querySelector('input[name="page"]')) {
                const pageInput = document.createElement('input');
                pageInput.type = 'hidden';
                pageInput.name = 'page';
                pageInput.value = currentPage;
                this.appendChild(pageInput);
            }
        });
    });
});
</script>

<?php require 'includes/footer.php'; ?>
</body>
</html>