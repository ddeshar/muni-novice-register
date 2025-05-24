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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Creative Registration Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #6f86d6 0%, #48c6ef 100%);
        min-height: 100vh;
    }
    .card {
        margin-top: 40px;
        box-shadow: 0 8px 32px 0 rgba( 31, 38, 135, 0.23 );
    }    .photo-preview {
        display: block;
        max-width: 120px;
        max-height: 120px;
        margin: 10px auto 15px;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        object-fit: cover;
    }
    @media print {
        body {
            background: none !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .btn {
            display: none !important;
        }
        .alert {
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .alert-heading {
            margin-bottom: 20px !important;
        }
    }
    </style>
    <script>
    function previewPhoto(input) {
        var file = input.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photo-preview').src = e.target.result;
                document.getElementById('photo-preview').style.display = "block";
            }
            reader.readAsDataURL(file);
        }
    }
    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card p-4">
                    <div class="card-header bg-primary text-white text-center rounded-3">
                        <h2>
                            <i class="bi bi-person-plus"></i> Registration Form
                        </h2>
                    </div>                    <div class="card-body">
                        <?php if ($upload_error): ?>
                            <div class='alert alert-danger'><?php echo $upload_error; ?></div>
                        <?php endif; ?>

                        <?php if ($message === "success"): ?>
                            <div class='alert alert-success'>
                                <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Registration Successful!</h4>
                                <p>Thank you for registering. Below are your registration details:</p>
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center mb-3">
                                                <?php if ($_SESSION['registration_data']['photo']): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($_SESSION['registration_data']['photo']); ?>" 
                                                         class="img-fluid rounded-circle border" 
                                                         style="width: 120px; height: 120px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-9">
                                                <dl class="row">
                                                    <dt class="col-sm-4">Registration ID:</dt>
                                                    <dd class="col-sm-8">#<?php echo $_SESSION['registration_data']['id']; ?></dd>
                                                    
                                                    <dt class="col-sm-4">Name:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['registration_data']['name']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Date of Birth:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['registration_data']['dob']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Phone:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['registration_data']['phone']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">School Name:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['registration_data']['school_name']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Passed Class:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($_SESSION['registration_data']['passed_class']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Parent Names:</dt>
                                                    <dd class="col-sm-8">
                                                        Father: <?php echo htmlspecialchars($_SESSION['registration_data']['father_name']); ?><br>
                                                        Mother: <?php echo htmlspecialchars($_SESSION['registration_data']['mother_name']); ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Address:</dt>
                                                    <dd class="col-sm-8">
                                                        Permanent: <?php echo htmlspecialchars($_SESSION['registration_data']['permanent_address']); ?><br>
                                                        Temporary: <?php echo htmlspecialchars($_SESSION['registration_data']['temporary_address']); ?>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <p class="mb-0">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> New Registration
                                    </a>
                                    <button onclick="window.print()" class="btn btn-outline-primary">
                                        <i class="bi bi-printer"></i> Print Details
                                    </button>
                                </p>
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Passed Class</label>
                                <input type="text" name="passed_class" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name of School</label>
                                <input type="text" name="school_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name of Mother</label>
                                <input type="text" name="mother_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name of Father</label>
                                <input type="text" name="father_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Permanent Address</label>
                                <input type="text" name="permanent_address" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Temporary Address</label>
                                <input type="text" name="temporary_address" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile/Tel No.</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6 text-center">
                                <label class="form-label">Photo</label>
                                <input type="file" name="photo" accept="image/*" onchange="previewPhoto(this)" class="form-control">
                                <img id="photo-preview" class="photo-preview" style="display:none;" alt="Photo Preview"/>
                            </div>
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Register
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-link">
                                <i class="bi bi-shield-lock"></i> Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</body>
</html>