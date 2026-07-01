<?php
session_start();
require_once '../../middleware/auth.php';

// Strict Role Enforcement: Block anyone who isn't explicitly a volunteer
checkRole('volunteer');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ---------------- DB CONNECTION ---------------- */
$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

/* ---------------- FETCH VOLUNTEER IDENTITY ---------------- */
$user_stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$userQuery = $user_stmt->get_result();
$volunteer = $userQuery->fetch_assoc();

if (!$volunteer) {
    die("Volunteer profile context not found.");
}

$volunteer_id = $volunteer['id'];

/* ---------------- UPDATE VOLUNTEER PROFILE ---------------- */
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $age = intval($_POST['age']);
    $skills = trim($_POST['skills']); // Clean application layer mapping for volunteer talents
    $bio = trim($_POST['bio']);

    // Updates generic user structural columns with specialized volunteer variables
    $update_stmt = $conn->prepare("UPDATE users SET name = ?, age = ?, designation = ?, bio = ? WHERE id = ?");
    $update_stmt->bind_param("sissi", $name, $age, $skills, $bio, $volunteer_id);

    if ($update_stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();
    } else {
        die("Profile Synchronization Failed: " . $conn->error);
    }
}

/* ---------------- UPLOAD VOLUNTEER PHOTO ---------------- */
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
            $photo_stmt->bind_param("si", $filename, $volunteer_id);
            $photo_stmt->execute();
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?photo=1");
            exit();
        }
    }
}

/* ---------------- RENDERING COMPLIANCE CHECK ---------------- */
// Force active values to empty strings only right after an update so HTML placeholders can display
$input_name = isset($_GET['updated']) ? '' : ($volunteer['name'] ?? '');
$input_age = isset($_GET['updated']) ? '' : ($volunteer['age'] ?? '');
$input_skills = isset($_GET['updated']) ? '' : ($volunteer['designation'] ?? ''); 
$input_bio = isset($_GET['updated']) ? '' : ($volunteer['bio'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F-Destiny | Volunteer Profile Control</title>
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
            background-image: radial-gradient(circle, rgba(59, 130, 246, 0.14) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
            animation: floatAround 25s infinite alternate ease-in-out;
        }
        .blob-1 { width: 550px; height: 550px; top: -10%; left: -5%; animation-duration: 22s; }
        .blob-2 { width: 650px; height: 650px; bottom: -5%; right: -5%; background-image: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%); animation-duration: 30s; }

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
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
            outline: none;
        }
    </style>
</head>
<body class="animated-bg text-slate-800 antialiased min-h-screen flex flex-col">

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<header class="glass-header sticky top-0 z-50 border-b border-slate-200/50 shadow-sm px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-2 text-2xl font-black text-blue-600 tracking-tight">
        <span>🍱</span> F-Destiny
    </div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden md:block">
        <h1>Volunteer Profile</h1>
    </div>
    <a href="volunteer_index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Dashboard</span>
    </a>   
</header>

<main class="flex-1 max-w-4xl w-full mx-auto p-4 md:p-8 space-y-8 relative z-10">

    <?php if (isset($_GET['updated']) || isset($_GET['photo'])): ?>
        <div class="p-4 bg-slate-900/95 backdrop-blur-xl border border-white/10 text-blue-400 text-sm font-semibold rounded-2xl shadow-xl text-center animate-fade-in">
            ✓ Volunteer profile updated successfully.
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-6 items-start">
        
        <div class="glass-card p-6 rounded-2xl text-center border border-white/80 flex flex-col items-center">
            <div class="relative group w-40 h-40 rounded-full border-4 border-white shadow-xl overflow-hidden mb-4 bg-slate-100">
                <?php if (!empty($volunteer['photo'])): ?>
                    <img src="uploads/<?= htmlspecialchars($volunteer['photo']) ?>?v=<?= time() ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-4xl bg-blue-50 text-blue-600 font-bold">
                        <?= strtoupper(substr($volunteer['name'] ?? 'V', 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <div onclick="document.getElementById('photoInput').click()" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col items-center justify-center text-white text-xs cursor-pointer font-bold gap-1">
                    <span>📷</span>
                    <span>Update Photo</span>
                </div>
            </div>

            <h3 class="font-black text-slate-900 text-lg tracking-tight"><?= htmlspecialchars($volunteer['name'] ?? 'Identity Unset') ?></h3>
            <p class="text-xs text-blue-600 font-bold tracking-wide uppercase mt-0.5"><?= htmlspecialchars($volunteer['designation'] ?? 'Field Specialist') ?></p>
            
            <form method="POST" enctype="multipart/form-data" id="photoForm" class="w-full mt-4">
                <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*">
                <input type="hidden" name="upload_photo" value="1">
                <button type="button" onclick="document.getElementById('photoInput').click()" class="w-full py-2 px-4 rounded-xl text-xs font-bold text-slate-700 bg-white/80 hover:bg-white border border-slate-200 transition shadow-sm">
                    Upload profile photo
                </button>
            </form>
        </div>

        <div class="glass-card p-6 rounded-2xl border border-white/80 md:col-span-2 space-y-4">
            <div class="border-b border-slate-200/50 pb-3">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Volunteer Recognition</h3>
            </div>
            
            <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm">
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide"> Name</span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($volunteer['name'] ?? 'Not configured') ?></span>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide">Age </span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($volunteer['age'] ?? 'Not specified') ?> yrs</span>
                </div>
                <div class="col-span-2">
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide"> Role</span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($volunteer['designation'] ?? 'General Task Resource') ?></span>
                </div>
                <div class="col-span-2 border-t border-slate-100 pt-3">
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Profile Information</span>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium bg-white/30 p-3 rounded-xl border border-slate-200/30">
                        <?= nl2br(htmlspecialchars($volunteer['bio'] ?? 'No operational volunteer background description has been configured for this resource node.')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card p-6 md:p-8 rounded-2xl border border-white/80 max-w-4xl relative overflow-hidden">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-blue-600"></div>

        <div class="mb-6">
            <h3 class="text-base font-bold text-slate-900 tracking-tight">Edit  Profile</h3>
            <p class="text-xs text-slate-500 mt-0.5">Manage information used for task assignments.</p>
        </div>

        <form method="POST" class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($input_name) ?>" placeholder="Enter Full Name" required 
                        class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Age</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($input_age) ?>" placeholder="Enter  Age" required 
                        class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                </div>
            </div>

            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Role </label>
                <input type="text" name="skills" value="<?= htmlspecialchars($input_skills) ?>" placeholder="Volunteer,..." required 
                    class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
            </div>

            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5"> Bio</label>
                <textarea name="bio" placeholder="Experience, capabilities,..." rows="4" required
                    class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm font-medium resize-none"><?= htmlspecialchars($input_bio) ?></textarea>
            </div>

            <div class="pt-2">
                <button type="submit" name="update_profile" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-600/20 hover:shadow-xl transition duration-200 text-sm tracking-wide uppercase">
                    Submit 
                </button>
            </div>
        </form>
    </div>

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