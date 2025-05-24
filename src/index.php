<?php
require 'db.php';
$message = "";
$upload_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $passed_class = $_POST['passed_class'] ?? '';
    $school_name = $_POST['school_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $permanent_address = $_POST['permanent_address'] ?? '';
    $temporary_address = $_POST['temporary_address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $photo = '';

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $photo = uniqid() . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
        } else {
            $upload_error = "Only JPG, JPEG, PNG, GIF files are allowed.";
        }
    }

    if (!$upload_error) {
        $stmt = $conn->prepare("INSERT INTO registrations 
            (name, address, dob, passed_class, school_name, mother_name, father_name, permanent_address, temporary_address, phone, photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $name, $address, $dob, $passed_class, $school_name, $mother_name, $father_name, $permanent_address, $temporary_address, $phone, $photo);        if ($stmt->execute()) {
            $reg_id = $conn->insert_id;
            $message = "success";
            // Store registration data in session for display
            session_start();
            $_SESSION['registration_data'] = [
                'id' => $reg_id,
                'name' => $name,
                'address' => $address,
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
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<?php 
$title = "Student Registration Form";
require 'includes/header.php';
?>

<!-- Custom styles for this page -->
<style>
    .photo-preview {
        display: block;
        width: 120px;
        height: 120px;
        margin: 10px auto 15px;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        object-fit: cover;
        background: #f8f9fa;
    }
    
    .form-floating {
        margin-bottom: 1rem;
    }
    
    .form-floating > label {
        padding-left: 1rem;
    }
    
    .registration-success {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .registration-card {
        border-radius: 20px;
        overflow: hidden;
    }
    
    .registration-header {
        background: var(--primary-gradient);
        padding: 2rem;
        color: var(--primary);
        text-align: center;
        margin: -1.5rem -1.5rem 1.5rem;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 0.5rem;
        align-items: baseline;
    }
    
    .detail-label {
        font-weight: 600;
        min-width: 150px;
        color: #6c757d;
    }
    
    .detail-value {
        flex: 1;
    }
    
    @media (max-width: 768px) {
        .card {
            margin: 1rem;
            border-radius: 15px;
        }
        
        .detail-row {
            flex-direction: column;
        }
        
        .detail-label {
            margin-bottom: 0.25rem;
        }
        
        .registration-header {
            padding: 1.5rem;
        }
    }
</style>

<script>
function previewPhoto(input) {
    var file = input.files[0];
    var preview = document.getElementById('photo-preview');
    
    if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            handleError({message: 'File size should not exceed 5MB'});
            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            return;
        }
        
        // Validate file type
        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            handleError({message: 'Please select a valid image file (JPG, PNG, or GIF)'});
            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            preview.classList.add('animate__animated', 'animate__fadeIn');
        }
        reader.readAsDataURL(file);
    }
}
</script>
    <main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card p-4 animate__animated animate__fadeInUp">
                <?php if ($message === "success"): ?>
                <div class="registration-success">
                    <div class="registration-header">
                        <i class="bi bi-check-circle-fill display-1"></i>
                        <h2 class="mt-3">Registration Successful!</h2>
                        <p class="lead mb-0">Thank you for registering with us</p>
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
                            <div class="detail-row">
                                <div class="detail-label">Registration ID</div>
                                <div class="detail-value">#<?php echo $_SESSION['registration_data']['id']; ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Student Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($_SESSION['registration_data']['name']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Date of Birth</div>
                                <div class="detail-value"><?php echo htmlspecialchars($_SESSION['registration_data']['dob']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Contact Number</div>
                                <div class="detail-value"><?php echo htmlspecialchars($_SESSION['registration_data']['phone']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Academic Details</div>
                                <div class="detail-value">
                                    <strong>School:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['school_name']); ?><br>
                                    <strong>Class Passed:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['passed_class']); ?>
                                </div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Parents</div>
                                <div class="detail-value">
                                    <strong>Father:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['father_name']); ?><br>
                                    <strong>Mother:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['mother_name']); ?>
                                </div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Addresses</div>
                                <div class="detail-value">
                                    <strong>Permanent:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['permanent_address']); ?><br>
                                    <strong>Temporary:</strong> <?php echo htmlspecialchars($_SESSION['registration_data']['temporary_address']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4 d-print-none">
                            <a href="index.php" class="btn btn-primary me-2">
                                <i class="bi bi-plus-circle"></i> New Registration
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-primary">
                                <i class="bi bi-printer"></i> Print Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="registration-header">
                    <h2 class="mb-0"><i class="bi bi-person-plus"></i> Student Registration</h2>
                </div>
                
                <?php if ($upload_error): ?>
                <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <?php echo $upload_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation row g-3" novalidate>
                    <div class="col-md-6 text-center mb-4">
                        <div class="photo-upload-container">
                            <img id="photo-preview" class="photo-preview" 
                                 src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 24 24'%3E%3Cpath fill='%23ccc' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" 
                                 alt="Profile Picture">
                            <div class="mt-2">
                                <label class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-camera"></i> Choose Photo
                                    <input type="file" name="photo" accept="image/*" 
                                           onchange="previewPhoto(this)" class="d-none">
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="name" class="form-control" id="name" placeholder="Full Name" required>
                            <label for="name">Full Name</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="date" name="dob" class="form-control" id="dob" required>
                            <label for="dob">Date of Birth</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="tel" name="phone" class="form-control" id="phone" 
                                   pattern="[0-9]{10}" placeholder="Mobile Number" required>
                            <label for="phone">Mobile Number</label>
                            <div class="form-text">Enter 10-digit mobile number</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="school_name" class="form-control" id="school" required>
                            <label for="school">School Name</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="passed_class" class="form-control" id="class" required>
                            <label for="class">Passed Class</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="father_name" class="form-control" id="father" required>
                            <label for="father">Father's Name</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="mother_name" class="form-control" id="mother" required>
                            <label for="mother">Mother's Name</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea name="permanent_address" class="form-control" id="permanent_addr" 
                                      style="height: 100px" required></textarea>
                            <label for="permanent_addr">Permanent Address</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-floating mb-3">
                            <textarea name="temporary_address" class="form-control" id="temp_addr" 
                                      style="height: 100px"></textarea>
                            <label for="temp_addr">Temporary Address (if different)</label>
                        </div>
                    </div>
                    
                    <div class="col-12 d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Submit Registration
                        </button>
                        <a href="login.php" class="btn btn-link text-decoration-none">
                            <i class="bi bi-shield-lock"></i> Admin Login
                        </a>
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