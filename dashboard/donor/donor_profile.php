<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('donor');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------------- DB CONNECTION ---------------- */
$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

/* ---------------- FETCH USER (SECURE PREPARED STATEMENT) ---------------- */
$user_stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$userQuery = $user_stmt->get_result();
$donor = $userQuery->fetch_assoc();

if (!$donor) {
    die("User not found");
}

$donor_id = $donor['id'];

/* ---------------- UPDATE PROFILE ---------------- */
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $age = intval($_POST['age']);
    $designation = trim($_POST['designation']);
    $bio = trim($_POST['bio']);

    $update_stmt = $conn->prepare("UPDATE users SET name = ?, age = ?, designation = ?, bio = ? WHERE id = ?");
    $update_stmt->bind_param("sissi", $name, $age, $designation, $bio, $donor_id);

    if ($update_stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();
    } else {
        die("Update Failed: " . $conn->error);
    }
}

/* ---------------- UPLOAD PHOTO ---------------- */
if (isset($_POST['upload_photo']) && !empty($_FILES['photo']['name'])) {
    $file = $_FILES['photo'];
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($file_ext, $allowed_extensions)) {
        $filename = time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
        $target = "uploads/" . $filename;

        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $photo_stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $photo_stmt->bind_param("si", $filename, $donor_id);
            $photo_stmt->execute();
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?photo=1");
            exit();
        }
    }
}

// CONDITION TO DISPLAY PLACEHOLDERS: 
// If the profile was just updated, we clear these variables so the inputs fall back to placeholders.
$input_name = isset($_GET['updated']) ? '' : ($donor['name'] ?? '');
$input_age = isset($_GET['updated']) ? '' : ($donor['age'] ?? '');
$input_designation = isset($_GET['updated']) ? '' : ($donor['designation'] ?? '');
$input_bio = isset($_GET['updated']) ? '' : ($donor['bio'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Premium Profile Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .animated-bg {
            background-color: #f8fafc;
            position: relative;
            overflow-x: hidden;
        }

        .blob {
            position: absolute;
            background-image: radial-gradient(circle, rgba(16, 185, 129, 0.16) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        .blob-1 { width: 550px; height: 550px; top: -10%; left: -5%; animation-duration: 22s; }
        .blob-2 { width: 650px; height: 650px; bottom: -5%; right: -5%; background-image: radial-gradient(circle, rgba(59, 130, 246, 0.14) 0%, transparent 70%); animation-duration: 30s; }

        @keyframes floatAround {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 30px) scale(1.1); }
            100% { transform: translate(-30px, 60px) scale(0.95); }
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px) saturate(120%);
            -webkit-backdrop-filter: blur(16px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.01), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(203, 213, 225, 0.7);
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
            outline: none;
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<header class="glass-header sticky top-0 z-50 border-b border-slate-200/50 shadow-sm px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2 text-2xl font-black text-emerald-600 tracking-tight">
        <span>🍱</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        <h1>Donor Profile</h1>
    </div>
    <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>   
</header>

<main class="flex-1 max-w-4xl w-full mx-auto p-4 md:p-8 space-y-8 relative z-10">

    <?php if (isset($_GET['updated']) || isset($_GET['photo'])): ?>
        <div class="p-4 bg-slate-900/95 backdrop-blur-xl border border-white/10 text-emerald-400 text-sm font-semibold rounded-2xl shadow-xl text-center animate-fade-in">
            ✓ Profile updated successfully.
        </div>
    <?php endif; ?>

    <?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
// include 'db_connection.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle photo upload
    if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                // Update database with new photo path
                // $user_id = $_SESSION['user_id'];
                // $update_photo = "UPDATE users SET photo = '$new_filename' WHERE id = $user_id";
                // mysqli_query($conn, $update_photo);
                
                $_SESSION['photo_updated'] = $new_filename;
                $_SESSION['success_message'] = "Profile photo updated successfully!";
                
                // Refresh to show new photo
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to upload photo.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type. Please upload JPG, PNG, GIF, or WEBP.";
        }
    }
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        // Sanitize and validate input
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $age = htmlspecialchars(trim($_POST['age'] ?? ''));
        $designation = htmlspecialchars(trim($_POST['designation'] ?? ''));
        $bio = htmlspecialchars(trim($_POST['bio'] ?? ''));
        
        // Validate inputs
        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (empty($age) || !is_numeric($age) || $age < 1) $errors[] = "Valid age is required";
        if (empty($designation)) $errors[] = "Designation is required";
        if (empty($bio)) $errors[] = "Bio is required";
        
        if (empty($errors)) {
            // Update database (adjust according to your database structure)
            // $user_id = $_SESSION['user_id'];
            // $update_query = "UPDATE users SET name='$name', age='$age', designation='$designation', bio='$bio' WHERE id=$user_id";
            // mysqli_query($conn, $update_query);
            
            // Store updated values in session
            $_SESSION['updated_name'] = $name;
            $_SESSION['updated_age'] = $age;
            $_SESSION['updated_designation'] = $designation;
            $_SESSION['updated_bio'] = $bio;
            $_SESSION['success_message'] = "Profile updated successfully!";
            
            // Refresh to show updated data
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error_message'] = implode(", ", $errors);
            // Store posted values for form repopulation
            $_SESSION['posted_name'] = $name;
            $_SESSION['posted_age'] = $age;
            $_SESSION['posted_designation'] = $designation;
            $_SESSION['posted_bio'] = $bio;
        }
    }
}

// Get donor data from session or database
// For demo purposes, using session data first
if (isset($_SESSION['updated_name'])) {
    $donor_name = $_SESSION['updated_name'];
    $donor_age = $_SESSION['updated_age'];
    $donor_designation = $_SESSION['updated_designation'];
    $donor_bio = $_SESSION['updated_bio'];
} elseif (isset($_SESSION['posted_name'])) {
    $donor_name = $_SESSION['posted_name'];
    $donor_age = $_SESSION['posted_age'];
    $donor_designation = $_SESSION['posted_designation'];
    $donor_bio = $_SESSION['posted_bio'];
} else {
    // Default values from database
    $donor_name = $donor['name'] ?? 'Identity Pending';
    $donor_age = $donor['age'] ?? 'Not added';
    $donor_designation = $donor['designation'] ?? 'Donor Agent';
    $donor_bio = $donor['bio'] ?? 'No operational description has been configured for this organization grid module yet.';
}

// Get photo from session or database
$donor_photo = $_SESSION['photo_updated'] ?? ($donor['photo'] ?? '');

// Clear messages after displaying
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// For form fields (edit profile)
$input_name = isset($_SESSION['updated_name']) ? $_SESSION['updated_name'] : ($donor['name'] ?? '');
$input_age = isset($_SESSION['updated_age']) ? $_SESSION['updated_age'] : ($donor['age'] ?? '');
$input_designation = isset($_SESSION['updated_designation']) ? $_SESSION['updated_designation'] : ($donor['designation'] ?? '');
$input_bio = isset($_SESSION['updated_bio']) ? $_SESSION['updated_bio'] : ($donor['bio'] ?? '');
?>

<!-- Profile Display Section -->
<div class="grid md:grid-cols-3 gap-6 items-start">
    
    <!-- Profile Photo Card -->
    <div class="glass-card p-6 rounded-2xl text-center border border-white/80 flex flex-col items-center">
        <div class="relative group w-40 h-40 rounded-full border-4 border-white shadow-xl overflow-hidden mb-4 bg-slate-100">
            <?php if (!empty($donor_photo)): ?>
                <img src="uploads/<?= htmlspecialchars($donor_photo) ?>?v=<?= time() ?>" class="w-full h-full object-cover" alt="Profile Photo">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-4xl bg-emerald-50 text-emerald-600 font-bold">
                    <?= strtoupper(substr($donor_name, 0, 1)) ?>
                </div>
            <?php endif; ?>

            <div onclick="document.getElementById('photoInput').click()" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col items-center justify-center text-white text-xs cursor-pointer font-bold gap-1">
                <span>📷</span>
                <span>Change Photo</span>
            </div>
        </div>

        <h3 class="font-black text-slate-900 text-lg tracking-tight"><?= htmlspecialchars($donor_name) ?></h3>
        <p class="text-xs text-emerald-600 font-bold tracking-wide uppercase mt-0.5"><?= htmlspecialchars($donor_designation) ?></p>
        
        <form method="POST" enctype="multipart/form-data" id="photoForm" class="w-full mt-4">
            <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*" onchange="this.form.submit()">
            <input type="hidden" name="upload_photo" value="1">
            <button type="button" onclick="document.getElementById('photoInput').click()" class="w-full py-2 px-4 rounded-xl text-xs font-bold text-slate-700 bg-white/80 hover:bg-white border border-slate-200 transition shadow-sm">
                Upload profile photo
            </button>
        </form>
    </div>

    <!-- Profile Details Card -->
    <div class="glass-card p-6 rounded-2xl border border-white/80 md:col-span-2 space-y-4">
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-medium flex items-center gap-2 animate-fade-in">
            <span class="text-lg">✅</span>
            <?= $success_message ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm font-medium flex items-center gap-2 animate-fade-in">
            <span class="text-lg">❌</span>
            <?= $error_message ?>
        </div>
        <?php endif; ?>

        <div class="border-b border-slate-200/50 pb-3 flex justify-between items-center">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Donor Profile</h3>
            
        </div>
        
        <!-- Display Mode -->
        <div id="displayMode" class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm">
            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide">Authorized User</span>
                <span class="font-semibold text-slate-800"><?= htmlspecialchars($donor_name) ?></span>
            </div>
            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide">Age Criteria</span>
                <span class="font-semibold text-slate-800"><?= htmlspecialchars($donor_age) ?> yrs</span>
            </div>
            <div class="col-span-2">
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide">Profession</span>
                <span class="font-semibold text-slate-800"><?= htmlspecialchars($donor_designation) ?></span>
            </div>
            <div class="col-span-2 border-t border-slate-100 pt-3">
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Mission & Vision (Bio)</span>
                <p class="text-xs text-slate-600 leading-relaxed font-medium bg-white/30 p-3 rounded-xl border border-slate-200/30">
                    <?= nl2br(htmlspecialchars($donor_bio)) ?>
                </p>
            </div>
        </div>

        <!-- Edit Mode (Hidden by default) -->
        <div id="editMode" class="hidden">
            <form method="POST" class="space-y-4" id="profileForm">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($input_name) ?>" placeholder="Enter Name" required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Age</label>
                        <input type="number" name="age" value="<?= htmlspecialchars($input_age) ?>" placeholder="Enter Age" required 
                            class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Designation</label>
                    <input type="text" name="designation" value="<?= htmlspecialchars($input_designation) ?>" placeholder="Your Designation" required 
                        class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Bio</label>
                    <textarea name="bio" placeholder="Your Bio ..." rows="4" required
                        class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium resize-none"><?= htmlspecialchars($input_bio) ?></textarea>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="submit" name="update_profile" class="flex-1 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-600/20 hover:shadow-xl transition duration-200 text-sm tracking-wide uppercase">
                        Update Profile
                    </button>
                    <button type="button" onclick="toggleEdit()" class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition duration-200 text-sm uppercase tracking-wide">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
}

.glass-input {
    background: rgba(255, 255, 255, 0.7);
    border: 2px solid rgba(226, 232, 240, 0.8);
    transition: all 0.3s ease;
    color: #0f172a;
}

.glass-input:focus {
    background: rgba(255, 255, 255, 0.95);
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    outline: none;
}

.glass-input:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: #cbd5e1;
}

.glass-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}
</style>

<script>

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.animate-fade-in');
    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => {
                msg.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-rose-400');
                    isValid = false;
                } else {
                    input.classList.remove('border-rose-400');
                }
            });
            
            // Validate age
            const ageInput = form.querySelector('input[name="age"]');
            if (ageInput && (ageInput.value < 1 || ageInput.value > 150)) {
                ageInput.classList.add('border-rose-400');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    }
});
</script>

    <?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Sanitize and validate input
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $age = htmlspecialchars(trim($_POST['age'] ?? ''));
    $designation = htmlspecialchars(trim($_POST['designation'] ?? ''));
    $bio = htmlspecialchars(trim($_POST['bio'] ?? ''));
    
    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($age) || !is_numeric($age) || $age < 1) $errors[] = "Valid age is required";
    if (empty($designation)) $errors[] = "Designation is required";
    if (empty($bio)) $errors[] = "Bio is required";
    
    if (empty($errors)) {
        // Update database (example - adjust according to your database structure)
        // Assuming you have a session with user_id
        // $user_id = $_SESSION['user_id'];
        // $update_query = "UPDATE users SET name='$name', age='$age', designation='$designation', bio='$bio' WHERE id=$user_id";
        // mysqli_query($conn, $update_query);
        
        // Store updated values in session or variables
        $_SESSION['success_message'] = "Profile updated successfully!";
        $_SESSION['updated_name'] = $name;
        $_SESSION['updated_age'] = $age;
        $_SESSION['updated_designation'] = $designation;
        $_SESSION['updated_bio'] = $bio;
        
        // Refresh the page to show updated values
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Display errors
        $error_message = implode(", ", $errors);
    }
}

// Get current values (from session or database)
// If you have session data, use that first
if (isset($_SESSION['updated_name'])) {
    $input_name = $_SESSION['updated_name'];
    $input_age = $_SESSION['updated_age'];
    $input_designation = $_SESSION['updated_designation'];
    $input_bio = $_SESSION['updated_bio'];
} else {
    // Default values from database or empty
    $input_name = isset($user_data['name']) ? $user_data['name'] : '';
    $input_age = isset($user_data['age']) ? $user_data['age'] : '';
    $input_designation = isset($user_data['designation']) ? $user_data['designation'] : '';
    $input_bio = isset($user_data['bio']) ? $user_data['bio'] : '';
}

// Check for success/error messages
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']); // Clear after displaying
?>

<div class="glass-card p-6 md:p-8 rounded-2xl border border-white/80 max-w-4xl relative overflow-hidden">
    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-emerald-500 to-emerald-600"></div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm font-medium flex items-center gap-2 animate-fade-in">
        <span class="text-lg">✅</span>
        <?= $success_message ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <div class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm font-medium flex items-center gap-2 animate-fade-in">
        <span class="text-lg">❌</span>
        <?= $error_message ?>
    </div>
    <?php endif; ?>

    <div class="mb-6">
        <h3 class="text-base font-bold text-slate-900 tracking-tight">Edit Profile</h3>
        <p class="text-xs text-slate-500 mt-0.5">Update Personal Information.</p>
    </div>

    <form method="POST" class="space-y-4" id="profileForm">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($input_name) ?>" placeholder="Enter Name" required 
                    class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium
                    <?= (!empty($errors) && in_array('Name is required', $errors)) ? 'border-rose-400 focus:border-rose-400' : '' ?>">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Age</label>
                <input type="number" name="age" value="<?= htmlspecialchars($input_age) ?>" placeholder="Enter Age" required 
                    class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium
                    <?= (!empty($errors) && in_array('Valid age is required', $errors)) ? 'border-rose-400 focus:border-rose-400' : '' ?>">
            </div>
        </div>

        <div>
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Designation</label>
            <input type="text" name="designation" value="<?= htmlspecialchars($input_designation) ?>" placeholder="Your Designation" required 
                class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium
                <?= (!empty($errors) && in_array('Designation is required', $errors)) ? 'border-rose-400 focus:border-rose-400' : '' ?>">
        </div>

        <div>
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Bio</label>
            <textarea name="bio" placeholder="Your Bio ..." rows="4" required
                class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium resize-none
                <?= (!empty($errors) && in_array('Bio is required', $errors)) ? 'border-rose-400 focus:border-rose-400' : '' ?>"><?= htmlspecialchars($input_bio) ?></textarea>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" name="update_profile" id="submitBtn" 
                class="flex-1 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-600/20 hover:shadow-xl transition duration-200 text-sm tracking-wide uppercase relative">
                <span id="buttonText">Update Profile</span>
                <span id="loadingSpinner" class="hidden absolute inset-0 flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
            
            <button type="reset" class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition duration-200 text-sm uppercase tracking-wide">
                Reset
            </button>
        </div>
    </form>
</div>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.glass-input {
    background: rgba(255, 255, 255, 0.7);
    border: 2px solid rgba(226, 232, 240, 0.8);
    transition: all 0.3s ease;
    color: #0f172a;
}

.glass-input:focus {
    background: rgba(255, 255, 255, 0.95);
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    outline: none;
}

.glass-input:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: #cbd5e1;
}

.glass-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const submitBtn = document.getElementById('submitBtn');
    const buttonText = document.getElementById('buttonText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    // Auto-hide success/error messages after 5 seconds
    const messages = document.querySelectorAll('.animate-fade-in');
    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(() => {
                msg.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Add loading state on form submit
    form.addEventListener('submit', function(e) {
        // Validate form before submitting
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('border-rose-400', 'focus:border-rose-400');
                isValid = false;
            } else {
                input.classList.remove('border-rose-400', 'focus:border-rose-400');
            }
        });
        
        // Validate age
        const ageInput = form.querySelector('input[name="age"]');
        if (ageInput && (ageInput.value < 1 || ageInput.value > 150)) {
            ageInput.classList.add('border-rose-400', 'focus:border-rose-400');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Show error at the top
            const errorDiv = document.createElement('div');
            errorDiv.className = 'mb-4 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm font-medium flex items-center gap-2 animate-fade-in';
            errorDiv.innerHTML = `
                <span class="text-lg">⚠️</span>
                Please fill in all required fields correctly.
            `;
            form.insertBefore(errorDiv, form.firstChild);
            
            setTimeout(() => {
                errorDiv.style.transition = 'opacity 0.5s ease';
                errorDiv.style.opacity = '0';
                setTimeout(() => {
                    errorDiv.remove();
                }, 500);
            }, 4000);
            return;
        }
        
        // Show loading state
        buttonText.textContent = 'Updating...';
        buttonText.style.opacity = '0.5';
        loadingSpinner.classList.remove('hidden');
        submitBtn.disabled = true;
    });
    
    // Real-time validation on input
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-rose-400', 'focus:border-rose-400');
            }
            
            // Special validation for age
            if (this.name === 'age' && this.value > 0 && this.value <= 150) {
                this.classList.remove('border-rose-400', 'focus:border-rose-400');
            }
        });
    });
    
    // Reset button functionality
    const resetBtn = form.querySelector('button[type="reset"]');
    resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset all inputs to their original values
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            const originalValue = input.getAttribute('data-original-value') || input.defaultValue;
            input.value = originalValue;
            input.classList.remove('border-rose-400', 'focus:border-rose-400');
        });
        
        // Remove any error messages
        const errors = form.querySelectorAll('.animate-fade-in');
        errors.forEach(error => {
            if (error.classList.contains('bg-rose-50')) {
                error.remove();
            }
        });
        
        // Show reset confirmation
        const resetDiv = document.createElement('div');
        resetDiv.className = 'mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm font-medium flex items-center gap-2 animate-fade-in';
        resetDiv.innerHTML = `
            <span class="text-lg">🔄</span>
            Form has been reset to original values.
        `;
        form.insertBefore(resetDiv, form.firstChild);
        
        setTimeout(() => {
            resetDiv.style.transition = 'opacity 0.5s ease';
            resetDiv.style.opacity = '0';
            setTimeout(() => {
                resetDiv.remove();
            }, 500);
        }, 3000);
    });
    
    // Store original values for reset
    const originalInputs = form.querySelectorAll('input, textarea');
    originalInputs.forEach(input => {
        input.setAttribute('data-original-value', input.value);
    });
});

// If you want to refresh the page after successful update using AJAX
async function updateProfile(formData) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Reload the page to show updated data
            window.location.reload();
        }
    } catch (error) {
        console.error('Error updating profile:', error);
    }
}
</script>

</main>

<script>
document.getElementById("photoInput").addEventListener("change", function () {
    if (this.files.length > 0) {
        document.getElementById("photoForm").submit();
    }
});
</script>
</body>
</html>