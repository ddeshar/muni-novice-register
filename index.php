<?php
require 'db.php';
require 'includes/security.php';
// Note: session_start() is already called in db.php
$message = "";
$upload_error = "";

function old_value($key)
{
    return htmlspecialchars($_POST[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function export_token_for_id($id)
{
    return hash_hmac('sha256', (string)$id, APP_SECRET);
}

function send_telegram_notification($text)
{
    if (TELEGRAM_BOT_TOKEN === '' || TELEGRAM_CHAT_ID === '') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    $payload = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_POSTFIELDS => http_build_query($payload)
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('Telegram send failed: ' . $error);
            return false;
        }

        return is_string($response) && strpos($response, '"ok":true') !== false;
    }

    // Fallback for shared hosting without cURL extension.
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 8
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    return is_string($response) && strpos($response, '"ok":true') !== false;
}

// Check for database connection error
if (isset($_SESSION['db_error'])) {
    $message = $_SESSION['db_error'];
    unset($_SESSION['db_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Check rate limit (5 submissions per 5 minutes)
    if (!check_rate_limit('registration', 5, 300)) {
        $message = "Too many registration attempts. Please try again in a few minutes.";
        http_response_code(429);
    } else {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $dob = htmlspecialchars(trim($_POST['dob'] ?? ''));
        $passed_class = htmlspecialchars(trim($_POST['passed_class'] ?? ''));
        $school_name = htmlspecialchars(trim($_POST['school_name'] ?? ''));
        $mother_name = htmlspecialchars(trim($_POST['mother_name'] ?? ''));
        $father_name = htmlspecialchars(trim($_POST['father_name'] ?? ''));
        $permanent_address = htmlspecialchars(trim($_POST['permanent_address'] ?? ''));
        $temporary_address = htmlspecialchars(trim($_POST['temporary_address'] ?? ''));
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $photo = '';

        // Validate required fields
        $required_fields = [
            'name' => $name,
            'dob' => $dob,
            'passed_class' => $passed_class,
            'school_name' => $school_name,
            'mother_name' => $mother_name,
            'father_name' => $father_name,
            'permanent_address' => $permanent_address,
            'phone' => $phone
        ];

        $missing_fields = [];
        foreach ($required_fields as $field => $value) {
            if (empty(trim($value))) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $message = "Error: Please fill in all required fields: " . implode(', ', $missing_fields);
        }

        if (empty($message) && !preg_match('/^\\d{10}$/', $phone)) {
            $message = "Error: Please enter a valid 10-digit mobile number.";
        }

        if (empty($message) && (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE)) {
            $upload_error = "Photo is mandatory. Please upload a valid image under 5MB.";
        }

        // Handle photo upload using secure file upload function
        if (empty($message) && !$upload_error && isset($_FILES['photo'])) {
            try {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0777, true)) {
                        throw new RuntimeException("Failed to create upload directory.");
                    }
                }

                // Use the secure file upload function
                $photo = secure_file_upload(
                    $_FILES['photo'],
                    $target_dir,
                    ['image/jpeg', 'image/png', 'image/gif'],
                    5242880 // 5MB limit
                );

                if (!$photo) {
                    $upload_error = "File upload failed. Please ensure it's a valid image file under 5MB.";
                }
            } catch (RuntimeException $e) {
                $upload_error = $e->getMessage();
            }
        } else if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {
            $error_codes = [
                1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
                2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
                3 => "The uploaded file was only partially uploaded.",
                4 => "No file was uploaded.",
                6 => "Missing a temporary folder.",
                7 => "Failed to write file to disk.",
                8 => "A PHP extension stopped the file upload."
            ];
            $error_code = $_FILES['photo']['error'];
            $upload_error = isset($error_codes[$error_code]) ? $error_codes[$error_code] : "Unknown upload error.";
        }

        // Continue with database insertion if no errors
        if (empty($message) && !$upload_error) {
            try {
                // Prepare SQL with correct number of parameters
                $stmt = $conn->prepare("INSERT INTO registrations 
                        (name, dob, passed_class, school_name, mother_name, father_name, permanent_address, temporary_address, phone, photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param(
                    "ssssssssss",
                    $name,
                    $dob,
                    $passed_class,
                    $school_name,
                    $mother_name,
                    $father_name,
                    $permanent_address,
                    $temporary_address,
                    $phone,
                    $photo
                );

                if ($stmt->execute()) {
                    $reg_id = $conn->insert_id;

                    // Store data in session
                    $_SESSION['registration_data'] = [
                        'id' => $reg_id,
                        'name' => $name,
                        'dob' => $dob,
                        'passed_class' => $passed_class,
                        'school_name' => $school_name,
                        'mother_name' => $mother_name,
                        'father_name' => $father_name,
                        'permanent_address' => $permanent_address,
                        'temporary_address' => $temporary_address,
                        'phone' => $phone,
                        'photo' => $photo
                    ];
                    $_SESSION['message'] = "success";

                    $telegram_text =
                        "<b>नयाँ दर्ता प्राप्त भयो</b>\n" .
                        "ID: #" . $reg_id . "\n" .
                        "नाम: " . $name . "\n" .
                        "मोबाइल: " . $phone . "\n" .
                        "कक्षा: " . $passed_class . "\n" .
                        "विद्यालय: " . $school_name;

                    send_telegram_notification($telegram_text);

                    // Close database connection before redirect
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    $conn->close();

                    // Make sure session is written
                    session_write_close();

                    // Debug info
                    // Redirect to success page with a parameter to avoid caching issues
                    header("Location: index.php?registration=success");
                    exit();
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                $message = "Error: Unable to complete registration. Please try again.";
            } finally {
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
            }
        } else {
            if ($upload_error && empty($message)) {
                $message = "Error: " . $upload_error;
            }
        }
    }
}

$title = "Student Registration Form";
require 'includes/header.php';
?>

<!-- Custom styles for this page -->
<style>
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        border-radius: 20px;
        background: var(--surface);
    }

    .form-floating {
        margin-bottom: 1rem;
    }

    .form-floating>label {
        padding-left: 1rem;
        color: var(--text-muted);
    }

    .custom-input {
        border: 2px solid var(--border-light);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .custom-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem var(--primary-light);
    }

    .photo-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--surface);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .photo-preview:hover {
        transform: scale(1.05);
    }

    .btn-outline-warning:hover {
        background-color: var(--primary);
        color: white;
    }

    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .form-check-label {
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .card {
            margin: 0.5rem;
            border-radius: 15px;
        }

        .monastery-header h3 {
            font-size: 1.5rem;
        }

        .monastery-header p {
            font-size: 0.9rem;
        }
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
    }

    .monastery-header {
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

    .legal-consent {
        background: var(--background-soft) !important;
        color: var(--text-primary);
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
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

        .photo-upload-container {
            margin-bottom: 1rem;
        }

        .photo-preview {
            width: 100px !important;
            height: 100px !important;
        }

        .form-floating>label {
            font-size: 0.9rem;
            padding-left: 0.75rem;
        }

        .custom-input {
            font-size: 16px;
            /* Prevents zoom on iOS */
            padding: 0.875rem 0.75rem;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }

        .legal-consent {
            font-size: 0.9rem !important;
            padding: 1rem !important;
        }

        h5 {
            font-size: 1.1rem !important;
        }

        .detail-row {
            flex-direction: column;
        }

        .detail-label {
            margin-bottom: 0.25rem;
        }

        .registration-header {
            padding: 1rem;
        }

        /* Better spacing for mobile */
        .row.g-4 {
            gap: 1rem !important;
        }

        /* Stack form fields on mobile */
        .col-md-6 {
            margin-bottom: 1rem;
        }
    }

    /* Extra small devices (phones, less than 576px) */
    @media (max-width: 575.98px) {
        .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

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
            padding: 0.5rem 1rem;
        }

        .form-floating>label {
            font-size: 0.85rem;
        }

        h5 {
            font-size: 1rem !important;
        }

        /* Improve touch targets */
        .btn,
        .form-control,
        .form-select {
            min-height: 48px;
        }

        /* Better form spacing on small screens */
        .form-floating {
            margin-bottom: 1.25rem;
        }

        /* Adjust alert padding */
        .alert {
            padding: 0.75rem;
            font-size: 0.9rem;
        }
    }
</style>

<script>
    // Set max date to 7 years ago from today
    window.addEventListener('load', function() {
        var today = new Date();
        var sevenYearsAgo = new Date();
        sevenYearsAgo.setFullYear(today.getFullYear() - 7);

        var dobInput = document.getElementById('dob');
        if (dobInput) {
            dobInput.max = sevenYearsAgo.toISOString().split('T')[0];
        }
    });

    // Simplified form validation that doesn't prevent submission
    document.addEventListener('DOMContentLoaded', function() {
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                console.log('Form submit event triggered');

                // Check basic validation
                var isValid = form.checkValidity();
                var consentChecked = document.getElementById('consent').checked;
                var photoInput = form.querySelector('input[name="photo"]');
                var hasPhoto = photoInput && photoInput.files && photoInput.files.length > 0;

                if (!isValid || !consentChecked || !hasPhoto) {
                    console.log('Form validation failed:', {
                        isValid: isValid,
                        consentChecked: consentChecked,
                        hasPhoto: hasPhoto
                    });
                    event.preventDefault();
                    event.stopPropagation();
                    if (!hasPhoto) {
                        alert('कृपया फोटो अनिवार्य रूपमा अपलोड गर्नुहोस्।');
                    } else {
                        alert('कृपया सबै आवश्यक फिल्डहरू भर्नुहोस् र सहमति चेकबक्समा टिक गर्नुहोस्।');
                    }
                } else {
                    console.log('Form validation passed, submitting...');
                    // Show loading indicator
                    if (typeof showLoader === 'function') {
                        showLoader();
                    }
                }

                form.classList.add('was-validated');
            }, false);
        });
    });

    function validateAge(input) {
        var dob = new Date(input.value);
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear();
        var monthDiff = today.getMonth() - dob.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        if (age < 11) {
            input.setCustomValidity('उमेर ११ वर्ष भन्दा माथि हुनुपर्छ');
            input.classList.add('is-invalid');
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }

    function previewPhoto(input) {
        var file = input.files[0];
        var preview = document.getElementById('photo-preview');

        if (!preview) {
            console.error('Preview element not found');
            return;
        }

        if (!file) {
            preview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23daa520\' d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E';
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size should not exceed 5MB');
            input.value = '';
            preview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23daa520\' d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E';
            return;
        }

        // Validate file type
        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            alert('Please select a valid image file (JPG, PNG, or GIF)');
            input.value = '';
            preview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23daa520\' d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            preview.classList.remove('animate__fadeOut');
            preview.classList.add('animate__animated', 'animate__fadeIn');
        }

        reader.onerror = function() {
            console.error('Error reading file');
            preview.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23daa520\' d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E';
        }

        reader.readAsDataURL(file);
    }
</script>

<main class="container-fluid px-2 py-3">
    <div class="row justify-content-center g-0">
        <div class="col-12 col-lg-8 col-md-10">
            <div class="card p-2 p-md-4 animate__animated animate__fadeIn">
                <?php
                // Check for either session message or URL parameter for success
                if ((isset($_SESSION['message']) && $_SESSION['message'] === "success" && isset($_SESSION['registration_data'])) ||
                    (isset($_GET['registration']) && $_GET['registration'] === "success" && isset($_SESSION['registration_data']))
                ):
                    $message = "success";
                    // Don't unset the message until after displaying all data
                ?>
                    <div class="registration-success">
                        <div class="registration-header">
                            <div class="monastery-header mb-4 text-center">
                                <h3 class="mb-2 fw-bold">मुनि विहार (श्रीधर्म्मोत्तम महाविहार)</h3>
                                <p class="mb-2">इनाचो टोल, वडा नं. ७, भक्तपुर नगरपालिका, भक्तपुर जिल्ला,<br>
                                    बागमती प्रदेश, नेपाल। फोन नं. ०१-६६१६४६४</p>
                                <p class="mb-4">मित्रराष्ट्र थाइल्याण्डका १९ अैां परमपूज्य राजगुरू भिक्षु समतेच बर संघराजचाउ क्रमल्हुवङ वजिरञाणसंवर<br>
                                    (सुवड्ढन महाथेर) को संरक्षणमा संचालित सामूहिक प्रब्रज्या तथा उपसम्पदा योजनामा सहभागिताको लागि<br>
                                    आवेदन-पत्र</p>
                            </div>
                            <!-- <i class="bi bi-flower1 success-icon animate__animated animate__bounceIn"></i>
                        <h2 class="display-5 fw-bold text-white mb-3">
                            <span class="animate__animated animate__fadeInDown">🙏 दर्ता सफल भयो! 🙏</span>
                        </h2>
                        <p class="lead text-white mb-0 animate__animated animate__fadeIn animate__delay-1s">
                            तपाईंलाई भिक्षु अध्ययन कार्यक्रम (२०८१/८२) मा<br>दर्ता भएकोमा धन्यवाद!
                        </p> -->
                        </div>

                        <div class="p-4">
                            <?php if ($_SESSION['registration_data']['photo']): ?>
                                <div class="text-center mb-4">
                                    <img src="uploads/<?php echo htmlspecialchars($_SESSION['registration_data']['photo']); ?>"
                                        class="photo-preview animate__animated animate__fadeIn"
                                        alt="Student Photo">
                                </div>
                            <?php endif; ?>
                            <div class="registration-details">
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
                                                <div class="value">#<?php echo $_SESSION['registration_data']['id']; ?></div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>पुरा नाम</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-calendar3 text-info"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>जन्म मिति</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['dob']); ?></div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-phone text-success"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>सम्पर्क नम्बर</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['phone']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="details-section mt-4">
                                    <div class="section-header">
                                        <i class="bi bi-book text-warning"></i>
                                        <h5 class="mb-3">शैक्षिक विवरण</h5>
                                    </div>
                                    <div class="detail-grid">
                                        <div class="detail-item wide">
                                            <div class="detail-icon">
                                                <i class="bi bi-building text-danger"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>विद्यालय</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['school_name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-mortarboard text-success"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>उत्तीर्ण कक्षा</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['passed_class']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['father_name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-person-heart text-danger"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>आमाको नाम</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['mother_name']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="details-section mt-4">
                                    <div class="section-header">
                                        <i class="bi bi-geo-alt text-success"></i>
                                        <h5 class="mb-3">ठेगाना विवरण</h5>
                                    </div>
                                    <div class="detail-grid">
                                        <div class="detail-item wide">
                                            <div class="detail-icon">
                                                <i class="bi bi-house-door text-primary"></i>
                                            </div>
                                            <div class="detail-content">
                                                <label>स्थायी ठेगाना</label>
                                                <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['permanent_address']); ?></div>
                                            </div>
                                        </div>
                                        <?php if ($_SESSION['registration_data']['temporary_address']): ?>
                                            <div class="detail-item wide">
                                                <div class="detail-icon">
                                                    <i class="bi bi-house text-secondary"></i>
                                                </div>
                                                <div class="detail-content">
                                                    <label>अस्थायी ठेगाना</label>
                                                    <div class="value"><?php echo htmlspecialchars($_SESSION['registration_data']['temporary_address']); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="success-message animate__animated animate__fadeInUp animate__delay-1s">
                            <div class="text-center mt-4 d-print-none">
                                <a href="index.php" class="btn btn-primary btn-lg me-2 px-4 py-2">
                                    <i class="bi bi-plus-circle"></i> नयाँ दर्ता
                                </a>
                                <a href="export_registration_pdf.php?id=<?php echo (int)$_SESSION['registration_data']['id']; ?>&token=<?php echo export_token_for_id((int)$_SESSION['registration_data']['id']); ?>" class="btn btn-outline-secondary btn-lg me-2 px-4 py-2" target="_blank" rel="noopener">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF Export
                                </a>
                                <button onclick="window.print()" class="btn btn-outline-primary btn-lg px-4 py-2">
                                    <i class="bi bi-printer"></i> विवरण प्रिन्ट गर्नुहोस्
                                </button>
                            </div>
                            <div class="alert alert-info mt-4 mb-0" role="alert" style="line-height:1.7;">
                                हाम्रो टिमले तपाईंले प्रदान गर्नुभएको मोबाइल नम्बरमा छिट्टै सम्पर्क गर्नेछ। कृपया आफ्नो फोन उपलब्ध राख्नुहोस्।
                            </div>
                        </div>
                        <!-- 
                    <div class="success-message animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <div class="message-item h-100">
                                    <i class="bi bi-gem message-icon" style="color: var(--danger)"></i>
                                    <div>
                                        <h5 class="mb-2">मुनि विहार</h5>
                                        <p class="mb-2">भक्तपुर</p>
                                        <small class="text-muted d-block">पवित्र स्थान</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="message-item h-100">
                                    <i class="bi bi-moon-stars message-icon" style="color: var(--primary)"></i>
                                    <div>
                                        <h5 class="mb-2">दीक्षा कार्यक्रम मिति</h5>
                                        <p class="mb-2">२०८२ असार २०</p>
                                        <small class="text-muted d-block">(जुलाई ५, २०२५)</small>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="col-md-4">
                                <div class="message-item h-100">
                                    <i class="bi bi-bell message-icon" style="color: var(--success)"></i>
                                    <div>
                                        <h5 class="mb-2">सम्पर्क जानकारी</h5>
                                        <p class="mb-2">हाम्रो टिमले थप जानकारीका लागि चाँडै सम्पर्क गर्नेछ।</p>
                                        <small class="text-muted d-block">कृपया मोबाइलमा सम्पर्क गर्न सकिने अवस्था राख्नुहोस्।</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="blessing-message">
                            <i class="bi bi-flower1 mb-2" style="font-size: 2rem; display: block;"></i>
                            <p class="mb-2" style="font-size: 1.1rem;">धम्म यात्रामा तपाईंको सफलता र शान्तिको कामना!</p>
                            <div class="mt-3">
                                <i class="bi bi-flower2" style="margin: 0 10px;"></i>
                                <i class="bi bi-flower1" style="margin: 0 10px;"></i>
                                <i class="bi bi-flower3" style="margin: 0 10px;"></i>
                            </div>
                        </div>

                        <div class="text-center mt-4 d-print-none">
                            <a href="index.php" class="btn btn-primary btn-lg me-2 px-4 py-2">
                                <i class="bi bi-plus-circle"></i> नयाँ दर्ता
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-primary btn-lg px-4 py-2">
                                <i class="bi bi-printer"></i> विवरण प्रिन्ट गर्नुहोस्
                            </button>
                        </div>
                    </div>
                    <div class="legal-consent mt-4 p-4 bg-light border rounded-3">
                        <p class="mb-0 text-center" style="line-height: 1.8;">
                            उपरोक्त विवरणहरु सही छन् भनी म स्वीकार गर्दछु र प्रब्रज्या अवधिभरी म विनीतपूर्वक बस्नेछु।<br>
                            त्यस्तै विहारमा अथवा विहारको बाहिर हुनसकने कुनै दुर्घटना अथवा नोक्सानीको म आफै जिम्मेवार हुनेछु<br>
                            र यसमा मुनि विहार कानूनीरूपले कुनै जवाफदेही नहुने तथ्य स्वीकार गर्दछु। अनुकम्पापूर्वक मलाई प्रब्रज्या दिनुहोस्。
                        </p>
                    </div> -->
                        <?php
                        // Clear session data after displaying everything
                        // Only unset if using session-based approach (not URL param)
                        if (isset($_SESSION['message'])) {
                            unset($_SESSION['message']);
                        }

                        // Always clear registration data since we've displayed it
                        if (isset($_SESSION['registration_data'])) {
                            unset($_SESSION['registration_data']);
                        }

                        // Log that session data was cleared
                        error_log("Session data cleared after displaying success page");
                        ?>
                    </div>
                <?php else: ?>
                    <div class="registration-header">
                        <div class="monastery-header mb-4 text-center">
                            <h3 class="mb-2 fw-bold">मुनि विहार (श्रीधर्म्मोत्तम महाविहार)</h3>
                            <p class="mb-2">इनाचो टोल, वडा नं. ७, भक्तपुर नगरपालिका, भक्तपुर जिल्ला,<br>
                                बागमती प्रदेश, नेपाल। फोन नं. ०१-६६१६४६४</p>
                            <p class="mb-4">मित्रराष्ट्र थाइल्याण्डका १९ अैां परमपूज्य राजगुरू भिक्षु समतेच बर संघराजचाउ क्रमल्हुवङ वजिरञाणसंवर<br>
                                (सुवड्ढन महाथेर) को संरक्षणमा संचालित सामूहिक प्रब्रज्या तथा उपसम्पदा योजनामा सहभागिताको लागि<br>
                                आवेदन-पत्र</p>
                        </div>
                    </div>

                    <!-- <div class="registration-header mb-4" style="background: linear-gradient(135deg, #b8860b, #daa520); padding: 1.5rem; border-radius: 12px; color: white;">
                    <h2 class="mb-0 text-center"><i class="bi bi-person-plus"></i> थाइल्याण्ड अध्ययन तथा दीक्षा कार्यक्रममा सहभागीको दर्ता</h2>
                </div> -->
                    <?php if ($message): ?>
                        <div class='alert alert-<?php echo (strpos($message, "Error") === 0) ? "danger" : "info"; ?> alert-dismissible fade show' role='alert'>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation row g-4" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="col-md-6 text-center mb-4">
                            <div class="photo-upload-container">
                                <div class="photo-frame" style="border: 2px dashed var(--primary); padding: 10px; border-radius: 50%; display: inline-block;">
                                    <img id="photo-preview" class="photo-preview"
                                        style="display: block; width: 150px; height: 150px;"
                                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 24 24'%3E%3Cpath fill='%23daa520' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E"
                                        alt="Profile Picture">
                                </div>
                                <div class="mt-3">
                                    <label class="btn btn-outline-warning btn-sm" style="border-color: var(--primary); color: var(--primary);">
                                        <i class="bi bi-camera"></i> फोटो छान्नुहोस्
                                        <input type="file" name="photo" accept="image/*"
                                            onchange="previewPhoto(this)" class="d-none">
                                    </label>
                                    <div class="form-text mt-2">प्रवेशपत्रको लागि हालसालै खिचिएको पासपोर्ट साइजको फोटो</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" name="name" class="form-control custom-input" id="name" placeholder="Full Name" value="<?php echo old_value('name'); ?>" required>
                                <label for="name"><i class="bi bi-person"></i> पुरा नाम</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="date" name="dob" class="form-control custom-input" id="dob"
                                    max=""
                                    onchange="validateAge(this)"
                                    value="<?php echo old_value('dob'); ?>"
                                    required>
                                <label for="dob"><i class="bi bi-calendar3"></i> जन्म मिति</label>
                                <div class="invalid-feedback">उमेर ११ वर्ष भन्दा माथि हुनुपर्छ</div>
                            </div>
                            <div class="form-floating">
                                <input type="tel" name="phone" class="form-control custom-input" id="phone"
                                    pattern="[0-9]{10}" maxlength="10"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                    placeholder="Mobile Number" value="<?php echo old_value('phone'); ?>" required>
                                <label for="phone"><i class="bi bi-phone"></i> मोबाइल नम्बर</label>
                                <div class="invalid-feedback">१० अङ्कको मोबाइल नम्बर लेख्नुहोस्</div>
                                <div class="form-text">Enter 10-digit mobile number (numbers only)</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr class="my-4" style="border-color: var(--secondary);">
                            <h5 class="mb-3" style="color: var(--primary);"><i class="bi bi-book"></i> शैक्षिक विवरण</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="school_name" class="form-control custom-input" id="school" value="<?php echo old_value('school_name'); ?>" required>
                                <label for="school"><i class="bi bi-building"></i> विद्यालयको नाम</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="passed_class" class="form-control custom-input" id="class" value="<?php echo old_value('passed_class'); ?>" required>
                                <label for="class"><i class="bi bi-mortarboard"></i> उत्तीर्ण कक्षा</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr class="my-4" style="border-color: var(--secondary);">
                            <h5 class="mb-3" style="color: var(--primary);"><i class="bi bi-people"></i> अभिभावक विवरण</h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="father_name" class="form-control custom-input" id="father" value="<?php echo old_value('father_name'); ?>" required>
                                <label for="father"><i class="bi bi-person"></i> बाबुको नाम</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="mother_name" class="form-control custom-input" id="mother" value="<?php echo old_value('mother_name'); ?>" required>
                                <label for="mother"><i class="bi bi-person-heart"></i> आमाको नाम</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr class="my-4" style="border-color: var(--secondary);">
                            <h5 class="mb-3" style="color: var(--primary);"><i class="bi bi-geo-alt"></i> ठेगाना विवरण</h5>
                        </div>

                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea name="permanent_address" class="form-control custom-input" id="permanent_addr"
                                    style="height: 100px" required><?php echo old_value('permanent_address'); ?></textarea>
                                <label for="permanent_addr"><i class="bi bi-house-door"></i> स्थायी ठेगाना</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea name="temporary_address" class="form-control custom-input" id="temp_addr"
                                    style="height: 100px"><?php echo old_value('temporary_address'); ?></textarea>
                                <label for="temp_addr"><i class="bi bi-house"></i> अस्थायी ठेगाना (यदि फरक छ भने)</label>
                            </div>
                        </div>

                        <div class="legal-consent mb-4 p-4 border rounded-3" style="background: var(--background-soft);">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="consent" required>
                                <label class="form-check-label" for="consent" style="line-height: 1.8;">
                                    उपरोक्त विवरणहरू सही छन् भनी म स्वीकार गर्दछु र प्रब्रज्या अवधिभरी म विनीतपूर्वक बस्नेछु।<br>
                                    त्यस्तै विहारमा अथवा विहारको बाहिर हुनसक्ने कुनै दुर्घटना अथवा नोक्सानीको म आफै जिम्मेवार हुनेछु<br>
                                    र यसमा मुनि विहार कानूनीरूपले कुनै जवाफदेही नहुने तथ्य स्वीकार गर्दछु। अनुकम्पापूर्वक मलाई प्रब्रज्या दिनुहोस्।
                                </label>
                            </div>
                            <div class="mt-3 pt-3 border-top">
                                <p class="mb-0" style="line-height: 1.8; color: var(--text-secondary); font-size: 0.95rem;">
                                    जन्मदर्ताको प्रमाणपत्र, विद्यालयको प्रमाणपत्र, मार्कशीट, स्थनान्तरणपत्र,
                                    माता पितासँग नाता प्रमाणपत्र जस्ता महत्त्वपूर्ण कागजातहरू पनि साथमा ल्याउनु होला ।
                                </p>
                            </div>
                        </div>

                        <div class="col-12 d-grid gap-2">
                            <button type="submit" class="btn btn-lg" style="background: var(--primary); color: white;">
                                <i class="bi bi-check-circle"></i> दर्ता पेश गर्नुहोस्
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require 'includes/footer.php'; ?>
</body>

</html>