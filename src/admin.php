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

$title = "Admin Dashboard";
require 'includes/header.php';
?>

<style>
    .stats-card {
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        background: white;
        border: none !important;
        height: 100%;
        transform: translateZ(0);
        will-change: transform;
    }

    .stats-card .card-body {
        padding: 1.5rem;
        position: relative;
        z-index: 2;
    }

    .stats-card:hover {
        transform: translateY(-2px) translateZ(0);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    @media (hover: hover) {
        .stats-card:active {
            transform: translateY(0) translateZ(0);
        }
    }

    @media (hover: none) {
        .stats-card:hover {
            transform: none;
            box-shadow: none;
        }
    }

    .stats-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .stats-icon-wrapper::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        opacity: 0.2;
        transition: all 0.3s ease;
    }

    .stats-card:hover .stats-icon-wrapper::before {
        opacity: 0.3;
        transform: scale(1.05);
    }

    .total-stats .stats-icon-wrapper::before {
        background: var(--primary);
    }

    .pending-stats .stats-icon-wrapper::before {
        background: var(--warning);
    }

    .approved-stats .stats-icon-wrapper::before {
        background: var(--success);
    }

    .rejected-stats .stats-icon-wrapper::before {
        background: var(--danger);
    }

    .total-stats .stats-icon-wrapper i {
        color: var(--primary);
    }

    .pending-stats .stats-icon-wrapper i {
        color: var(--warning);
    }

    .approved-stats .stats-icon-wrapper i {
        color: var(--success);
    }

    .rejected-stats .stats-icon-wrapper i {
        color: var(--danger);
    }

    .stats-number {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .stats-title {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin: 0;
    }

    .stats-progress {
        height: 4px;
        border-radius: 2px;
        margin-top: 1rem;
        background: rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .stats-card:hover .stats-progress {
        transform: scaleY(1.2);
    }

    .stats-progress-bar {
        transition: width 0.6s ease, transform 0.3s ease;
    }

    @media (hover: none) {
        .stats-card:hover .stats-progress {
            transform: none;
        }
    }

    .stats-progress-bar {
        height: 100%;
        border-radius: inherit;
        transition: all 0.3s ease;
    }

    .stats-percentage {
        font-size: 1.125rem;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .pending-stats .stats-percentage {
        color: var(--warning);
        background: rgba(var(--bs-warning-rgb), 0.1);
    }

    .approved-stats .stats-percentage {
        color: var(--success);
        background: rgba(var(--bs-success-rgb), 0.1);
    }

    .rejected-stats .stats-percentage {
        color: var(--danger);
        background: rgba(var(--bs-danger-rgb), 0.1);
    }

    .total-stats .stats-percentage {
        color: var(--primary);
        background: rgba(var(--bs-primary-rgb), 0.1);
    }

    .stats-card:hover .stats-percentage {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.15);
    }

    @media (hover: none) {
        .stats-card:hover .stats-percentage {
            transform: none;
            box-shadow: none;
        }
    }

    .stats-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
        transform: translateX(100%) skewX(-15deg);
        transition: all 0.5s ease;
    }

    .stats-card:hover::after {
        transform: translateX(-100%) skewX(-15deg);
    }

    .bg-primary-soft {
        background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
    }

    .progress {
        background-color: rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .progress-bar {
        transition: width 0.6s ease, transform 0.3s ease;
    }

    .stats-card:hover .progress-bar,
    .stats-card:hover .stats-progress-bar {
        transform: scaleY(1.2);
    }

    @media (max-width: 768px) {
        .stats-card {
            margin-bottom: 1rem;
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }

        .stats-card .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stats-icon-wrapper {
            width: 56px;
            height: 56px;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
            background: rgba(var(--bs-primary-rgb), 0.1);
        }

        .pending-stats .stats-icon-wrapper {
            background: rgba(var(--bs-warning-rgb), 0.1);
        }

        .approved-stats .stats-icon-wrapper {
            background: rgba(var(--bs-success-rgb), 0.1);
        }

        .rejected-stats .stats-icon-wrapper {
            background: rgba(var(--bs-danger-rgb), 0.1);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stats-title {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stats-percentage {
            display: none;
        }

        .stats-progress {
            width: 100%;
            height: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            overflow: hidden;
            margin-top: auto;
        }

        .stats-progress-bar {
            height: 100%;
            border-radius: inherit;
            transition: transform 0.3s ease;
        }

        .stats-card:active {
            transform: scale(0.98);
        }
    }

    @media (max-width: 576px) {
        .stats-card .card-body {
            padding: 1rem;
        }

        .stats-icon-wrapper {
            width: 48px;
            height: 48px;
            font-size: 1.5rem;
        }

        .stats-number {
            font-size: 2rem;
        }

        .stats-title {
            font-size: 0.938rem;
        }

        .stats-progress {
            height: 6px;
        }
    }

    .table-img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--surface);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: opacity 0.3s ease-in-out;
    }

    .lazy {
        opacity: 0;
    }

    img:not(.lazy) {
        opacity: 1;
    }

    .registration-row {
        transition: all 0.3s ease;
    }

    .registration-row:hover {
        background-color: rgba(139, 69, 19, 0.05);
    }

    .registration-row td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        vertical-align: middle;
    }

    .vstack {
        display: flex;
        flex-direction: column;
    }

    .vstack.gap-1>* {
        margin-bottom: 0.5rem;
    }

    .vstack.gap-1>*:last-child {
        margin-bottom: 0;
    }

    .table> :not(caption)>*>* {
        padding: 1rem 0.75rem;
    }

    .small {
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .comment-area {
        background: var(--surface-soft);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }

    .comment-bubble {
        background: var(--surface);
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 0.5rem;
        padding: 1rem;
    }

    .comment-list {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
    }

    .comment-list::-webkit-scrollbar {
        width: 6px;
    }

    .comment-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .comment-list::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
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
        box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        border-color: var(--primary);
    }

    @media (max-width: 768px) {
        .comment-area textarea {
            min-height: 250px;
            font-size: 16px;
            /* Prevents iOS zoom on focus */
            line-height: 1.4;
            padding: 12px;
        }

        .table-responsive {
            margin: 0 -1rem;
        }

        .table> :not(caption)>*>* {
            white-space: normal;
            min-width: 200px;
            padding: 0.75rem;
        }

        .vstack.gap-1>* {
            margin-bottom: 0.35rem;
        }

        .table .small {
            font-size: 0.813rem;
        }

        td .bi {
            font-size: 0.875rem;
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
        background: var(--surface);
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none;
        padding: 0.5rem;
    }

    @media (max-width: 768px) {
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
            background: var(--surface);
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
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

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }

    /* Enhanced Status Select and Action Buttons */
    .status-select-wrapper {
        position: relative;
        width: 130px;
    }

    .status-select {
        appearance: none;
        padding: 0.4rem 2rem 0.4rem 0.75rem;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 16px 12px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .status-select:hover {
        border-color: var(--primary);
    }

    .status-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }

    .action-btn {
        padding: 0.4rem 0.75rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.5);
    }

    .action-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-1px);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    .btn-text {
        display: none;
    }

    @media (min-width: 768px) {
        .btn-text {
            display: inline;
        }

        .status-select-wrapper {
            width: 150px;
        }

        .status-select {
            padding: 0.45rem 2rem 0.45rem 0.75rem;
        }
    }

    .status-indicator {
        position: absolute;
        left: 0;
        bottom: -2px;
        height: 2px;
        width: 100%;
        background: transparent;
        transition: transform 0.3s ease;
        transform-origin: left;
    }

    [data-status="Pending"]~.status-indicator {
        background: var(--warning);
    }

    [data-status="Approved"]~.status-indicator {
        background: var(--success);
    }

    [data-status="Rejected"]~.status-indicator {
        background: var(--danger);
    }

    /* Stats Card Hover Effects and Transitions */
    .stats-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateZ(0);
        will-change: transform;
    }

    .stats-card:hover {
        transform: translateY(-2px) translateZ(0);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .stats-icon-wrapper {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stats-card:hover .stats-icon-wrapper {
        transform: scale(1.05);
    }

    .stats-percentage {
        transition: all 0.3s ease;
    }

    .stats-card:hover .stats-percentage {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.15);
    }

    .stats-progress {
        transition: transform 0.3s ease;
    }

    .stats-card:hover .stats-progress {
        transform: scaleY(1.2);
    }

    .stats-progress-bar {
        transition: width 0.6s ease, transform 0.3s ease;
    }

    @media (hover: hover) {
        .stats-card:active {
            transform: translateY(0) translateZ(0);
        }
    }

    @media (hover: none) {
        .stats-card:hover {
            transform: none;
            box-shadow: none;
        }

        .stats-card:hover .stats-icon-wrapper {
            transform: none;
        }

        .stats-card:hover .stats-percentage {
            transform: none;
            box-shadow: none;
        }

        .stats-card:hover .stats-progress {
            transform: none;
        }
    }

    @media (max-width: 768px) {
        .comment-area textarea {
            min-height: 250px;
            font-size: 16px;
            /* Prevents iOS zoom on focus */
            line-height: 1.4;
            padding: 12px;
        }

        .table-responsive {
            margin: 0 -1rem;
        }

        .table> :not(caption)>*>* {
            white-space: normal;
            min-width: 200px;
            padding: 0.75rem;
        }

        .vstack.gap-1>* {
            margin-bottom: 0.35rem;
        }

        .table .small {
            font-size: 0.813rem;
        }

        td .bi {
            font-size: 0.875rem;
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
        background: var(--surface);
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none;
        padding: 0.5rem;
    }

    @media (max-width: 768px) {
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
            background: var(--surface);
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
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

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }

    /* Status Colors */
    .status-pending {
        background-color: var(--warning-light);
        color: var(--warning);
    }

    .status-approved {
        background-color: var(--success-light);
        color: var(--success);
    }

    .status-rejected {
        background-color: var(--danger-light);
        color: var(--danger);
    }
</style>

<script>
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
        const box = document.getElementById('comments-' + id);
        if (box.style.display === 'none') {
            box.style.display = 'block';

            // Check cache first
            if (commentCache.has(id)) {
                box.innerHTML = commentCache.get(id);
                return;
            }

            // Show loading indicator
            box.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

            fetch('comment.php?reg_id=' + id)
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
        const input = document.getElementById('comment-input-' + id);
        const comment = input.value.trim();
        if (!comment) return;

        const box = document.getElementById('comments-' + id);
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

        const box = document.getElementById('comments-' + regId);
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

    function updateStatus(select) {
        const form = select.closest('form');
        const statusIndicator = select.parentElement.querySelector('.status-indicator');

        // Update status indicator
        select.dataset.status = select.value;

        // Animate the indicator
        statusIndicator.style.transform = 'scaleX(0)';
        setTimeout(() => {
            statusIndicator.style.transform = 'scaleX(1)';
        }, 50);

        // Submit the form
        form.submit();
    }

    // Initialize status indicators
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.status-select').forEach(select => {
            select.dataset.status = select.value;
        });
    });
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
    </div>
    <!-- Stats Section -->
    <div class="row g-4 mb-4">
        <!-- Overview Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-primary"></i>
                            Registration Overview
                        </h5>
                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                            <i class="bi bi-calendar3 me-1"></i>
                            Today
                        </span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <span class="text-muted">Overall Progress</span>
                                <span class="badge bg-primary-soft text-primary px-2 py-1 rounded-pill">
                                    <?= $total > 0 ? round((($approved + $rejected) / $total) * 100) : 0 ?>% Processed
                                </span>
                            </div>
                            <div class="progress bg-light" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: <?= $total > 0 ? ($approved / $total) * 100 : 0 ?>%"
                                    title="Approved"></div>
                                <div class="progress-bar bg-danger" role="progressbar"
                                    style="width: <?= $total > 0 ? ($rejected / $total) * 100 : 0 ?>%"
                                    title="Rejected"></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="display-6 fw-bold text-primary mb-1"><?= $total ?></div>
                            <span class="text-muted">Total Applications</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Stats Cards -->
        <div class="col-6 col-lg-3">
            <div class="card stats-card pending-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stats-percentage">
                            <i class="bi bi-percent"></i>
                            <span><?= $total > 0 ? round(($pending / $total) * 100) : 0 ?></span>
                        </div>
                    </div>
                    <h3 class="stats-number text-warning"><?= $pending ?></h3>
                    <p class="stats-title">Pending Review</p>
                    <div class="stats-progress">
                        <div class="stats-progress-bar bg-warning"
                            style="width: <?= $total > 0 ? ($pending / $total) * 100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stats-card approved-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stats-percentage">
                            <?= $total > 0 ? round(($approved / $total) * 100) : 0 ?>%
                        </div>
                    </div>
                    <h3 class="stats-number text-success"><?= $approved ?></h3>
                    <p class="stats-title">Approved</p>
                    <div class="stats-progress">
                        <div class="stats-progress-bar bg-success"
                            style="width: <?= $total > 0 ? ($approved / $total) * 100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stats-card rejected-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="stats-percentage">
                            <?= $total > 0 ? round(($rejected / $total) * 100) : 0 ?>%
                        </div>
                    </div>
                    <h3 class="stats-number text-danger"><?= $rejected ?></h3>
                    <p class="stats-title">Rejected</p>
                    <div class="stats-progress">
                        <div class="stats-progress-bar bg-danger"
                            style="width: <?= $total > 0 ? ($rejected / $total) * 100 : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stats-card total-stats">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stats-icon-wrapper">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stats-percentage">100%</div>
                    </div>
                    <h3 class="stats-number text-primary"><?= $total ?></h3>
                    <p class="stats-title">Total Applications</p>
                    <div class="stats-progress">
                        <div class="stats-progress-bar bg-primary" style="width: 100%"></div>
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
                        <th>Personal Info</th>
                        <th>Contact & Address</th>
                        <th>Education</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()):
                        // Calculate age
                        $dob = new DateTime($row['dob']);
                        $now = new DateTime();
                        $age = $now->diff($dob)->y;
                    ?>
                        <tr class="registration-row">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($row['photo']): ?>
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
                                    <?php endif; ?>
                                    <div>
                                        <a href="user_details.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                            <small class="text-muted">ID: #<?= $row['id'] ?></small>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="vstack gap-1">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar3 me-2 text-primary"></i>
                                        <span><?= htmlspecialchars($row['dob']) ?> (<?= $age ?> years)</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people me-2 text-success"></i>
                                        <span>
                                            <?= htmlspecialchars($row['father_name']) ?> (Father)<br>
                                            <?= htmlspecialchars($row['mother_name']) ?> (Mother)
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="vstack gap-1">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-phone me-2 text-primary"></i>
                                        <span><?= htmlspecialchars($row['phone']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-house me-2 text-danger"></i>
                                        <div class="small">
                                            <div><?= htmlspecialchars($row['permanent_address']) ?></div>
                                            <?php if ($row['temporary_address']): ?>
                                                <div class="text-muted"><?= htmlspecialchars($row['temporary_address']) ?> (Temp)</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="vstack gap-1">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building me-2 text-primary"></i>
                                        <span><?= htmlspecialchars($row['school_name']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-mortarboard me-2 text-warning"></i>
                                        <span>Class <?= htmlspecialchars($row['passed_class']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex align-items-center gap-2">
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="reg_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <div class="status-select-wrapper">
                                            <select name="status" class="form-select form-select-sm status-select"
                                                onchange="updateStatus(this)" data-id="<?= $row['id'] ?>">
                                                <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : ''; ?>>
                                                    <i class="bi bi-hourglass-split"></i> Pending
                                                </option>
                                                <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : ''; ?>>
                                                    <i class="bi bi-check-circle"></i> Approved
                                                </option>
                                                <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : ''; ?>>
                                                    <i class="bi bi-x-circle"></i> Rejected
                                                </option>
                                            </select>
                                            <div class="status-indicator"></div>
                                        </div>
                                    </form>
                                    <a href="user_details.php?id=<?= $row['id'] ?>"
                                        class="btn btn-outline-primary btn-sm action-btn">
                                        <i class="bi bi-eye"></i>
                                        <span class="btn-text">View Details</span>
                                    </a>
                                </div>
                            </td>
                        </tr> <?php endwhile; ?>
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
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
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
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                              <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                              </li>';
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <a href="index.php" class="btn btn-link text-dark">
            <i class="bi bi-house"></i>
            <small class="d-block">Home</small>
        </a>
        <button class="btn btn-link text-dark" onclick="document.documentElement.scrollTop = 0">
            <i class="bi bi-arrow-up-circle"></i>
            <small class="d-block">Top</small>
            </a>
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