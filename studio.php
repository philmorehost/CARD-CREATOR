<?php
require_once __DIR__ . '/core/helpers.php';

if (!is_installed()) {
    header('Location: installer/index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db_connection();
$siteTitle = get_setting('site_title', 'CARD-CREATOR');
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'staff';

// 8 Premium Seed Templates
$templates = [
    // 1. Corporate Premium ID Card
    [
        'id' => 1,
        'title' => 'Corporate Executive ID Card',
        'type' => 'id_card',
        'orientation' => 'portrait',
        'width_px' => 600,
        'height_px' => 960,
        'primary_color' => '#1e3a8a',
        'secondary_color' => '#2563eb',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'default' => 'GLOBAL TECH SYSTEMS', 'x' => 300, 'y' => 60, 'font' => 'Inter', 'size' => 22, 'color' => '#ffffff', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'subtitle', 'label' => 'Header Subtitle', 'type' => 'text', 'default' => 'AUTHORIZED ACCESS BADGE', 'x' => 300, 'y' => 85, 'font' => 'Inter', 'size' => 12, 'color' => '#93c5fd', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'photo', 'label' => 'Member Photo', 'type' => 'file', 'x' => 200, 'y' => 180, 'width' => 200, 'height' => 240, 'shape' => 'round', 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'ALEXANDER PIERCE', 'x' => 300, 'y' => 470, 'font' => 'Inter', 'size' => 26, 'color' => '#0f172a', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Job Title / Role', 'type' => 'select', 'default' => 'Senior Tech Director', 'options' => 'Senior Tech Director,Lead Software Engineer,Product Manager,UI/UX Designer', 'x' => 300, 'y' => 510, 'font' => 'Inter', 'size' => 16, 'color' => '#2563eb', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'id_no', 'label' => 'ID Badge Number', 'type' => 'number', 'default' => '89210', 'prefix' => 'ID NO: EMP-', 'x' => 300, 'y' => 550, 'font' => 'Inter', 'size' => 15, 'color' => '#475569', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'dept', 'label' => 'Department', 'type' => 'radio', 'default' => 'Engineering', 'options' => 'Engineering,Design,Marketing,Management', 'x' => 300, 'y' => 585, 'font' => 'Inter', 'size' => 15, 'color' => '#475569', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'issue_date', 'label' => 'Issue Date', 'type' => 'date', 'default' => date('Y-m-d'), 'prefix' => 'Issued: ', 'x' => 300, 'y' => 620, 'font' => 'Inter', 'size' => 14, 'color' => '#64748b', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'verified', 'label' => 'Security Verified', 'type' => 'checkbox', 'default' => '1', 'check_label' => 'OFFICIALLY VERIFIED MEMBER', 'x' => 300, 'y' => 660, 'font' => 'Inter', 'size' => 14, 'color' => '#059669', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'address', 'label' => 'Office Address & Notes', 'type' => 'textarea', 'default' => "Headquarters: 500 Technology Way\nSuite 400, Innovation District\nSan Francisco, CA 94105", 'x' => 300, 'y' => 320, 'font' => 'Inter', 'size' => 15, 'color' => '#1e293b', 'align' => 'center', 'bold' => false, 'side' => 'back'],
            ['id' => 'emergency_tel', 'label' => 'Emergency Contact Tel', 'type' => 'tel', 'default' => '+1 (555) 019-2834', 'prefix' => 'Emergency: ', 'x' => 300, 'y' => 450, 'font' => 'Inter', 'size' => 15, 'color' => '#dc2626', 'align' => 'center', 'bold' => true, 'side' => 'back'],
            ['id' => 'contact_email', 'label' => 'Official Contact Email', 'type' => 'email', 'default' => 'alex.pierce@corp-domain.com', 'prefix' => 'Email: ', 'x' => 300, 'y' => 490, 'font' => 'Inter', 'size' => 15, 'color' => '#2563eb', 'align' => 'center', 'bold' => false, 'side' => 'back']
        ])
    ],

    // 2. Cyber Modern Dark ID Card
    [
        'id' => 2,
        'title' => 'Cyber Modern Dark ID Badge',
        'type' => 'id_card',
        'orientation' => 'portrait',
        'width_px' => 600,
        'height_px' => 960,
        'primary_color' => '#0f172a',
        'secondary_color' => '#6366f1',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'default' => 'CYBERDYNE LABS', 'x' => 300, 'y' => 60, 'font' => 'Inter', 'size' => 24, 'color' => '#f8fafc', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'default' => 'SECURITY PASS LEVEL 5', 'x' => 300, 'y' => 85, 'font' => 'Inter', 'size' => 12, 'color' => '#818cf8', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'photo', 'label' => 'Member Photo', 'type' => 'file', 'x' => 200, 'y' => 180, 'width' => 200, 'height' => 240, 'shape' => 'round', 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'SARAH CONNOR', 'x' => 300, 'y' => 470, 'font' => 'Inter', 'size' => 26, 'color' => '#ffffff', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Job Title', 'type' => 'text', 'default' => 'Chief Operations Officer', 'x' => 300, 'y' => 510, 'font' => 'Inter', 'size' => 16, 'color' => '#818cf8', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'id_no', 'label' => 'Security Clearance ID', 'type' => 'number', 'default' => '77291', 'prefix' => 'CLEARANCE: CY-', 'x' => 300, 'y' => 550, 'font' => 'Inter', 'size' => 15, 'color' => '#94a3b8', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'verified', 'label' => 'Biometric Verification', 'type' => 'checkbox', 'default' => '1', 'check_label' => 'BIOMETRIC ENCRYPTED', 'x' => 300, 'y' => 600, 'font' => 'Inter', 'size' => 14, 'color' => '#10b981', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'notes', 'label' => 'Security Instructions', 'type' => 'textarea', 'default' => "Property of Cyberdyne Labs.\nRestricted access strictly enforced.\nReport loss immediately.", 'x' => 300, 'y' => 320, 'font' => 'Inter', 'size' => 15, 'color' => '#e2e8f0', 'align' => 'center', 'bold' => false, 'side' => 'back']
        ])
    ],

    // 3. Medical & Hospital Pass ID
    [
        'id' => 3,
        'title' => 'Healthcare & Hospital Staff Pass',
        'type' => 'id_card',
        'orientation' => 'portrait',
        'width_px' => 600,
        'height_px' => 960,
        'primary_color' => '#0284c7',
        'secondary_color' => '#0d9488',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Hospital Name', 'type' => 'text', 'default' => 'METROPOLITAN MEDICAL CENTER', 'x' => 300, 'y' => 60, 'font' => 'Inter', 'size' => 20, 'color' => '#ffffff', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'photo', 'label' => 'Doctor Photo', 'type' => 'file', 'x' => 200, 'y' => 180, 'width' => 200, 'height' => 240, 'shape' => 'round', 'side' => 'front'],
            ['id' => 'name', 'label' => 'Doctor Name', 'type' => 'text', 'default' => 'DR. ELENA ROSTOVA', 'x' => 300, 'y' => 470, 'font' => 'Inter', 'size' => 26, 'color' => '#0f172a', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Speciality / Designation', 'type' => 'text', 'default' => 'Chief Cardiologist', 'x' => 300, 'y' => 510, 'font' => 'Inter', 'size' => 16, 'color' => '#0284c7', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'dept', 'label' => 'Medical Dept', 'type' => 'text', 'default' => 'Cardiovascular Unit', 'prefix' => 'Dept: ', 'x' => 300, 'y' => 550, 'font' => 'Inter', 'size' => 15, 'color' => '#475569', 'align' => 'center', 'bold' => false, 'side' => 'front'],
            ['id' => 'verified', 'label' => 'Licensed Physician', 'type' => 'checkbox', 'default' => '1', 'check_label' => 'BOARD CERTIFIED MEDICAL STAFF', 'x' => 300, 'y' => 600, 'font' => 'Inter', 'size' => 14, 'color' => '#0d9488', 'align' => 'center', 'bold' => true, 'side' => 'front']
        ])
    ],

    // 4. University Academic Student ID
    [
        'id' => 4,
        'title' => 'University Academic Campus ID',
        'type' => 'id_card',
        'orientation' => 'portrait',
        'width_px' => 600,
        'height_px' => 960,
        'primary_color' => '#4338ca',
        'secondary_color' => '#be185d',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'University Name', 'type' => 'text', 'default' => 'STANFORD STATE UNIVERSITY', 'x' => 300, 'y' => 60, 'font' => 'Inter', 'size' => 20, 'color' => '#ffffff', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'photo', 'label' => 'Student Photo', 'type' => 'file', 'x' => 200, 'y' => 180, 'width' => 200, 'height' => 240, 'shape' => 'round', 'side' => 'front'],
            ['id' => 'name', 'label' => 'Student Name', 'type' => 'text', 'default' => 'MARCUS VANCE', 'x' => 300, 'y' => 470, 'font' => 'Inter', 'size' => 26, 'color' => '#0f172a', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Academic Degree', 'type' => 'text', 'default' => 'B.S. Computer Science', 'x' => 300, 'y' => 510, 'font' => 'Inter', 'size' => 16, 'color' => '#4338ca', 'align' => 'center', 'bold' => true, 'side' => 'front'],
            ['id' => 'id_no', 'label' => 'Student ID Number', 'type' => 'number', 'default' => '2025990', 'prefix' => 'STUDENT ID: ', 'x' => 300, 'y' => 550, 'font' => 'Inter', 'size' => 15, 'color' => '#475569', 'align' => 'center', 'bold' => false, 'side' => 'front']
        ])
    ],

    // 5. Executive Premium Business Card
    [
        'id' => 5,
        'title' => 'Executive Premium Business Card',
        'type' => 'business_card',
        'orientation' => 'landscape',
        'width_px' => 1050,
        'height_px' => 600,
        'primary_color' => '#0f172a',
        'secondary_color' => '#2563eb',
        'fields_json' => json_encode([
            ['id' => 'logo', 'label' => 'Company Logo / Badge', 'type' => 'file', 'x' => 80, 'y' => 80, 'width' => 140, 'height' => 100, 'shape' => 'rect', 'side' => 'front'],
            ['id' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'default' => 'APEX INNOVATIONS INC.', 'x' => 240, 'y' => 135, 'font' => 'Inter', 'size' => 26, 'color' => '#0f172a', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'SARAH CONNOR', 'x' => 80, 'y' => 280, 'font' => 'Inter', 'size' => 34, 'color' => '#1e293b', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Job Title', 'type' => 'select', 'default' => 'Chief Executive Officer', 'options' => 'Chief Executive Officer,Managing Director,VP of Operations,Marketing Lead', 'x' => 80, 'y' => 325, 'font' => 'Inter', 'size' => 18, 'color' => '#2563eb', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'phone', 'label' => 'Direct Phone Number', 'type' => 'tel', 'default' => '+1 (555) 987-6543', 'prefix' => 'Mobile: ', 'x' => 80, 'y' => 410, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false, 'side' => 'front'],
            ['id' => 'email', 'label' => 'Email Address', 'type' => 'email', 'default' => 'sarah@apex-innovations.com', 'prefix' => 'Email: ', 'x' => 80, 'y' => 445, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false, 'side' => 'front'],
            ['id' => 'vip_member', 'label' => 'Executive Status', 'type' => 'checkbox', 'default' => '1', 'check_label' => 'VIP EXECUTIVE MEMBER', 'x' => 80, 'y' => 485, 'font' => 'Inter', 'size' => 15, 'color' => '#d97706', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'services', 'label' => 'Services Provided', 'type' => 'textarea', 'default' => "• Strategic Enterprise Consulting\n• Digital Transformation & AI\n• Venture Capital Investment", 'x' => 525, 'y' => 240, 'font' => 'Inter', 'size' => 18, 'color' => '#f8fafc', 'align' => 'center', 'bold' => false, 'side' => 'back']
        ])
    ],

    // 6. Minimal Gold Luxury Business Card
    [
        'id' => 6,
        'title' => 'Minimal Gold Luxury Business Card',
        'type' => 'business_card',
        'orientation' => 'landscape',
        'width_px' => 1050,
        'height_px' => 600,
        'primary_color' => '#1e1b4b',
        'secondary_color' => '#d97706',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'default' => 'LUXURY CAPITAL PARTNERS', 'x' => 80, 'y' => 100, 'font' => 'Inter', 'size' => 24, 'color' => '#d97706', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'VICTORIA CROSS', 'x' => 80, 'y' => 280, 'font' => 'Inter', 'size' => 36, 'color' => '#0f172a', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Designation', 'type' => 'text', 'default' => 'Senior Wealth Managing Director', 'x' => 80, 'y' => 325, 'font' => 'Inter', 'size' => 18, 'color' => '#d97706', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'default' => '+1 (555) 777-8899', 'prefix' => 'T: ', 'x' => 80, 'y' => 420, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false, 'side' => 'front'],
            ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'default' => 'v.cross@luxurycapital.com', 'prefix' => 'E: ', 'x' => 80, 'y' => 455, 'font' => 'Inter', 'size' => 16, 'color' => '#334155', 'align' => 'left', 'bold' => false, 'side' => 'front']
        ])
    ],

    // 7. Dark Neon Modern Business Card
    [
        'id' => 7,
        'title' => 'Dark Neon Modern Business Card',
        'type' => 'business_card',
        'orientation' => 'landscape',
        'width_px' => 1050,
        'height_px' => 600,
        'primary_color' => '#090d16',
        'secondary_color' => '#10b981',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'default' => 'NEXTGEN AI LABS', 'x' => 80, 'y' => 100, 'font' => 'Inter', 'size' => 26, 'color' => '#10b981', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'DR. EMETT BROWN', 'x' => 80, 'y' => 280, 'font' => 'Inter', 'size' => 36, 'color' => '#ffffff', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Role', 'type' => 'text', 'default' => 'Principal AI Researcher', 'x' => 80, 'y' => 325, 'font' => 'Inter', 'size' => 18, 'color' => '#10b981', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'email', 'label' => 'Email', 'type' => 'email', 'default' => 'emett@nextgen.ai', 'prefix' => 'Email: ', 'x' => 80, 'y' => 430, 'font' => 'Inter', 'size' => 16, 'color' => '#94a3b8', 'align' => 'left', 'bold' => false, 'side' => 'front']
        ])
    ],

    // 8. Creative Studio Agency Business Card
    [
        'id' => 8,
        'title' => 'Creative Agency Visual Card',
        'type' => 'business_card',
        'orientation' => 'landscape',
        'width_px' => 1050,
        'height_px' => 600,
        'primary_color' => '#ec4899',
        'secondary_color' => '#8b5cf6',
        'fields_json' => json_encode([
            ['id' => 'company_name', 'label' => 'Agency Name', 'type' => 'text', 'default' => 'PIXEL CRAFT STUDIO', 'x' => 80, 'y' => 100, 'font' => 'Inter', 'size' => 26, 'color' => '#ec4899', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'SOPHIA CHEN', 'x' => 80, 'y' => 280, 'font' => 'Inter', 'size' => 36, 'color' => '#0f172a', 'align' => 'left', 'bold' => true, 'side' => 'front'],
            ['id' => 'role', 'label' => 'Designation', 'type' => 'text', 'default' => 'Lead UI/UX Architect', 'x' => 80, 'y' => 325, 'font' => 'Inter', 'size' => 18, 'color' => '#8b5cf6', 'align' => 'left', 'bold' => true, 'side' => 'front']
        ])
    ]
];

$activeTemplate = $templates[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?> - Vector Studio</title>
    <link rel="icon" type="image/svg+xml" href="favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .canvas-shadow { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col">

<!-- Header Navigation Bar -->
<header class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-3">
        <a href="index.php" class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white text-lg shadow-lg shadow-blue-500/30">
            <?= htmlspecialchars(strtoupper(substr($siteTitle, 0, 1))) ?>
        </a>
        <div>
            <h1 class="font-extrabold text-white text-lg tracking-wide"><?= htmlspecialchars($siteTitle) ?></h1>
            <span class="text-xs text-blue-400 font-semibold">Protected Vector Card Studio</span>
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <span class="text-xs text-slate-300 font-medium">Logged in as <strong class="text-white"><?= htmlspecialchars($username) ?></strong> (<?= htmlspecialchars($userRole) ?>)</span>
        <?php if ($userRole === 'admin'): ?>
            <a href="admin/dashboard.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl border border-slate-600 transition">
                Admin Console ⚙
            </a>
        <?php endif; ?>
        <a href="admin/logout.php" class="px-3 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 text-xs font-semibold rounded-xl transition">Logout</a>
    </div>
</header>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

    <!-- LEFT COLUMN: TEMPLATES & RICH FORM BUILDER (7 Cols) -->
    <div class="lg:col-span-7 space-y-6">

        <!-- Template Selector -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 shadow-lg">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center justify-between">
                <span>Select Seeded Premium Template (8 Available)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($templates as $idx => $t): ?>
                    <button onclick="selectTemplate(<?= htmlspecialchars(json_encode($t)) ?>)"
                            class="p-3 text-left rounded-xl border border-slate-700 hover:border-blue-500 bg-slate-900/60 transition group flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm">
                            <?= $t['type'] === 'id_card' ? '📇' : '💼' ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white group-hover:text-blue-400"><?= htmlspecialchars($t['title']) ?></p>
                            <p class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($t['type']) ?> · <?= htmlspecialchars($t['orientation']) ?></p>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Palette & Styling Customizer -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 shadow-lg grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Primary Theme Color</label>
                <input type="color" id="primaryColorPicker" value="#1e3a8a" onchange="updateColors()" class="w-full h-10 bg-slate-900 rounded-xl cursor-pointer border border-slate-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Secondary Accent Color</label>
                <input type="color" id="secondaryColorPicker" value="#2563eb" onchange="updateColors()" class="w-full h-10 bg-slate-900 rounded-xl cursor-pointer border border-slate-700">
            </div>
        </div>

        <!-- Rich Form Builder -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-lg space-y-6">
            <div class="flex justify-between items-center border-b border-slate-700 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Card Form Builder</h2>
                    <p class="text-xs text-slate-400">Add, edit or remove fields. Changes render instantly on canvas.</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="openAddFieldModal()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-md transition flex items-center space-x-1">
                        <span>+ Add Custom Field</span>
                    </button>
                </div>
            </div>

            <!-- Form Inputs Container -->
            <div id="dynamicFormFields" class="space-y-4">
                <!-- Javascript dynamic fields render here -->
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: LIVE CANVAS INTERACTIVE PREVIEW & DOWNLOAD (5 Cols) -->
    <div class="lg:col-span-5 space-y-6 flex flex-col items-center">

        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-xl w-full flex flex-col items-center space-y-6 sticky top-24">
            <div class="flex justify-between items-center w-full border-b border-slate-700 pb-3">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Live Canvas Preview</h2>
                <div class="flex space-x-2">
                    <button id="btnViewFront" onclick="switchView('front')" class="px-3 py-1 bg-blue-600 text-white font-bold text-xs rounded-lg shadow">Front Side</button>
                    <button id="btnViewBack" onclick="switchView('back')" class="px-3 py-1 bg-slate-700 text-slate-300 font-bold text-xs rounded-lg">Back Side</button>
                </div>
            </div>

            <!-- CANVAS WORKSPACE -->
            <div id="canvasWrapper" class="relative bg-slate-950 rounded-xl p-4 border border-slate-700 flex items-center justify-center overflow-hidden w-full">
                <canvas id="cardCanvas" class="canvas-shadow rounded-lg max-w-full h-auto cursor-pointer border border-slate-800"></canvas>
            </div>

            <!-- ONE-CLICK COMBINED DOWNLOAD ACTIONS -->
            <div class="space-y-2 w-full pt-2">
                <p class="text-[11px] text-center text-slate-400 font-semibold">Single-Click Combined Front & Back Exports</p>
                <div class="grid grid-cols-3 gap-2 w-full">
                    <button onclick="downloadCombinedCard('png')" class="py-2.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                        <span>🖼 Stitched PNG</span>
                    </button>
                    <button onclick="downloadCombinedCard('jpg')" class="py-2.5 px-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                        <span>📷 Stitched JPG</span>
                    </button>
                    <button onclick="downloadCombinedCard('pdf')" class="py-2.5 px-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                        <span>📄 2-Page PDF</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

</main>

<!-- ADD FIELD MODAL -->
<div id="addFieldModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <h3 class="text-lg font-bold text-white">Add Custom Field to Card</h3>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Field Label</label>
            <input type="text" id="modalFieldLabel" placeholder="e.g. Blood Group" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Field Type</label>
            <select id="modalFieldType" onchange="toggleModalOptionsInput()" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="text">Single Line Text</option>
                <option value="textarea">Multi-line Text (Textarea)</option>
                <option value="select">Dropdown Choice (Select)</option>
                <option value="radio">Radio Options</option>
                <option value="checkbox">Checkbox Switch</option>
                <option value="date">Date Picker</option>
                <option value="number">Number Input</option>
                <option value="tel">Telephone / Phone</option>
                <option value="email">Email Address</option>
                <option value="file">Image File Upload</option>
            </select>
        </div>

        <div id="modalOptionsWrapper" class="hidden">
            <label class="block text-xs font-medium text-slate-300 mb-1">Options (Comma Separated)</label>
            <input type="text" id="modalFieldOptions" placeholder="Option 1, Option 2, Option 3" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Card Side Target</label>
            <select id="modalFieldSide" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">
                <option value="front">Front Side</option>
                <option value="back">Back Side</option>
            </select>
        </div>

        <div class="flex justify-end space-x-3 pt-3 border-t border-slate-700">
            <button onclick="closeAddFieldModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-semibold rounded-xl">Cancel</button>
            <button onclick="confirmAddField()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow">Add Field</button>
        </div>
    </div>
</div>

<script>
let currentTemplate = <?= json_encode($activeTemplate) ?>;
let activeView = 'front';
let formFields = [];
let fieldValues = {};
let loadedImages = {};
let primaryColor = currentTemplate.primary_color || '#1e3a8a';
let secondaryColor = currentTemplate.secondary_color || '#2563eb';

const canvas = document.getElementById('cardCanvas');
const ctx = canvas.getContext('2d');

document.addEventListener('DOMContentLoaded', () => {
    loadTemplate(currentTemplate);
});

function selectTemplate(tmpl) {
    currentTemplate = tmpl;
    primaryColor = tmpl.primary_color || '#1e3a8a';
    secondaryColor = tmpl.secondary_color || '#2563eb';
    document.getElementById('primaryColorPicker').value = primaryColor;
    document.getElementById('secondaryColorPicker').value = secondaryColor;
    activeView = 'front';
    updateViewButtons();
    loadTemplate(tmpl);
}

function updateColors() {
    primaryColor = document.getElementById('primaryColorPicker').value;
    secondaryColor = document.getElementById('secondaryColorPicker').value;
    renderCanvas();
}

function loadTemplate(tmpl) {
    try {
        formFields = typeof tmpl.fields_json === 'string' ? JSON.parse(tmpl.fields_json || '[]') : (tmpl.fields_json || []);
    } catch (e) {
        formFields = [];
    }

    fieldValues = {};
    formFields.forEach(f => {
        fieldValues[f.id] = f.default !== undefined ? f.default : '';
    });

    initFormUI();
    renderCanvas();
}

function initFormUI() {
    const container = document.getElementById('dynamicFormFields');
    container.innerHTML = '';

    formFields.forEach((field, index) => {
        const sideBadge = field.side ? (field.side === 'front' ? 'FRONT' : 'BACK') : 'BOTH';
        const sideColor = field.side === 'back' ? 'bg-indigo-600/30 text-indigo-300 border-indigo-500/40' : 'bg-blue-600/30 text-blue-300 border-blue-500/40';

        const fieldDiv = document.createElement('div');
        fieldDiv.className = "p-4 bg-slate-900/80 rounded-xl border border-slate-700/80 space-y-2 relative group";

        let inputHtml = '';
        const currentVal = fieldValues[field.id] !== undefined ? fieldValues[field.id] : (field.default || '');

        switch (field.type) {
            case 'textarea':
                inputHtml = `<textarea oninput="updateFieldValue('${field.id}', this.value)" rows="3" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">${currentVal}</textarea>`;
                break;

            case 'select':
                const opts = (field.options || '').split(',').map(o => o.trim());
                inputHtml = `<select onchange="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">
                    ${opts.map(o => `<option value="${o}" ${o === currentVal ? 'selected' : ''}>${o}</option>`).join('')}
                </select>`;
                break;

            case 'radio':
                const radioOpts = (field.options || '').split(',').map(o => o.trim());
                inputHtml = `<div class="flex flex-wrap gap-4 pt-1">
                    ${radioOpts.map(o => `
                        <label class="inline-flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                            <input type="radio" name="radio_${field.id}" value="${o}" ${o === currentVal ? 'checked' : ''} onchange="updateFieldValue('${field.id}', this.value)" class="text-blue-600 focus:ring-blue-500">
                            <span>${o}</span>
                        </label>
                    `).join('')}
                </div>`;
                break;

            case 'checkbox':
                const isChecked = currentVal === '1' || currentVal === true || currentVal === 'true';
                inputHtml = `<label class="flex items-center space-x-3 text-sm text-slate-200 cursor-pointer pt-1">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="updateFieldValue('${field.id}', this.checked ? '1' : '0')" class="w-4 h-4 text-blue-600 bg-slate-800 border-slate-700 rounded focus:ring-blue-500">
                    <span class="text-xs font-semibold text-slate-300">${field.check_label || 'Enable / Verify'}</span>
                </label>`;
                break;

            case 'file':
                inputHtml = `<div class="flex items-center space-x-3">
                    <input type="file" accept="image/*" onchange="uploadImageField('${field.id}', this)" class="text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                </div>`;
                break;

            case 'date':
                inputHtml = `<input type="date" value="${currentVal}" onchange="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">`;
                break;

            case 'number':
                inputHtml = `<input type="number" value="${currentVal}" oninput="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">`;
                break;

            case 'tel':
                inputHtml = `<input type="tel" value="${currentVal}" oninput="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">`;
                break;

            case 'email':
                inputHtml = `<input type="email" value="${currentVal}" oninput="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">`;
                break;

            case 'text':
            default:
                inputHtml = `<input type="text" value="${currentVal}" oninput="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-blue-500">`;
                break;
        }

        fieldDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider">${field.label}</label>
                    <span class="text-[9px] px-2 py-0.5 rounded-md border font-extrabold ${sideColor}">${sideBadge}</span>
                </div>
                <button onclick="removeField(${index})" class="text-xs text-red-400 hover:underline opacity-80 hover:opacity-100 transition">Remove</button>
            </div>
            ${inputHtml}
        `;

        container.appendChild(fieldDiv);
    });
}

function updateFieldValue(id, val) {
    fieldValues[id] = val;
    renderCanvas();
}

function uploadImageField(id, input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('file', input.files[0]);

        fetch('api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 1) {
                fieldValues[id] = data.url;
                renderCanvas();
            } else {
                alert(data.message || 'Image upload failed.');
            }
        });
    }
}

function openAddFieldModal() {
    document.getElementById('addFieldModal').classList.remove('hidden');
}

function closeAddFieldModal() {
    document.getElementById('addFieldModal').classList.add('hidden');
}

function toggleModalOptionsInput() {
    const type = document.getElementById('modalFieldType').value;
    const wrapper = document.getElementById('modalOptionsWrapper');
    if (type === 'select' || type === 'radio') {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
    }
}

function confirmAddField() {
    const label = document.getElementById('modalFieldLabel').value.trim();
    if (!label) {
        alert('Field label is required.');
        return;
    }

    const type = document.getElementById('modalFieldType').value;
    const options = document.getElementById('modalFieldOptions').value.trim();
    const side = document.getElementById('modalFieldSide').value;

    const id = 'custom_' + Date.now();
    const newField = {
        id: id,
        label: label,
        type: type,
        options: options,
        default: type === 'checkbox' ? '1' : (type === 'textarea' ? 'Sample text content...' : 'Sample Text'),
        side: side,
        x: currentTemplate.type === 'id_card' ? 300 : 80,
        y: currentTemplate.type === 'id_card' ? 700 : 500,
        font: 'Inter',
        size: 15,
        color: '#1e293b',
        align: currentTemplate.type === 'id_card' ? 'center' : 'left',
        bold: false
    };

    formFields.push(newField);
    fieldValues[id] = newField.default;
    closeAddFieldModal();
    initFormUI();
    renderCanvas();
}

function removeField(index) {
    formFields.splice(index, 1);
    initFormUI();
    renderCanvas();
}

function switchView(side) {
    activeView = side;
    updateViewButtons();
    renderCanvas();
}

function updateViewButtons() {
    const btnFront = document.getElementById('btnViewFront');
    const btnBack = document.getElementById('btnViewBack');

    if (activeView === 'front') {
        btnFront.className = 'px-3 py-1 bg-blue-600 text-white font-bold text-xs rounded-lg shadow';
        btnBack.className = 'px-3 py-1 bg-slate-700 text-slate-300 font-bold text-xs rounded-lg';
    } else {
        btnFront.className = 'px-3 py-1 bg-slate-700 text-slate-300 font-bold text-xs rounded-lg';
        btnBack.className = 'px-3 py-1 bg-blue-600 text-white font-bold text-xs rounded-lg shadow';
    }
}

// RENDER CANVAS SIDE SPECIFIC
function renderSideToCanvas(targetCtx, sideToRender, width, height) {
    targetCtx.clearRect(0, 0, width, height);

    if (currentTemplate.type === 'id_card') {
        renderIDCardBackground(targetCtx, sideToRender, width, height);
    } else {
        renderBusinessCardBackground(targetCtx, sideToRender, width, height);
    }

    drawFieldsToCtx(targetCtx, sideToRender);
}

function renderCanvas() {
    const width = parseInt(currentTemplate.width_px || 600);
    const height = parseInt(currentTemplate.height_px || 960);

    canvas.width = width;
    canvas.height = height;

    renderSideToCanvas(ctx, activeView, width, height);
}

function renderIDCardBackground(cCtx, side, w, h) {
    if (side === 'front') {
        cCtx.fillStyle = '#ffffff';
        cCtx.fillRect(0, 0, w, h);

        const headGrad = cCtx.createLinearGradient(0, 0, w, 220);
        headGrad.addColorStop(0, primaryColor);
        headGrad.addColorStop(1, secondaryColor);
        cCtx.fillStyle = headGrad;
        cCtx.beginPath();
        cCtx.moveTo(0, 0);
        cCtx.lineTo(w, 0);
        cCtx.lineTo(w, 180);
        cCtx.quadraticCurveTo(w / 2, 230, 0, 180);
        cCtx.closePath();
        cCtx.fill();

        cCtx.save();
        cCtx.fillStyle = '#334155';
        cCtx.beginPath();
        cCtx.roundRect(w / 2 - 40, 12, 80, 14, 7);
        cCtx.fill();
        cCtx.restore();

        const footGrad = cCtx.createLinearGradient(0, h - 80, w, h);
        footGrad.addColorStop(0, secondaryColor);
        footGrad.addColorStop(1, '#0f172a');
        cCtx.fillStyle = footGrad;
        cCtx.beginPath();
        cCtx.moveTo(0, h - 50);
        cCtx.quadraticCurveTo(w / 2, h - 90, w, h - 50);
        cCtx.lineTo(w, h);
        cCtx.lineTo(0, h);
        cCtx.closePath();
        cCtx.fill();
    } else {
        cCtx.fillStyle = '#f8fafc';
        cCtx.fillRect(0, 0, w, h);

        cCtx.fillStyle = '#0f172a';
        cCtx.fillRect(0, 40, w, 80);

        cCtx.fillStyle = '#cbd5e1';
        cCtx.fillRect(30, 150, w - 60, 40);
        cCtx.fillStyle = '#475569';
        cCtx.font = 'bold 12px Inter, sans-serif';
        cCtx.textAlign = 'right';
        cCtx.fillText('SECURITY CODE: 8910-A', w - 45, 175);

        cCtx.font = '11px Inter, sans-serif';
        cCtx.fillStyle = '#94a3b8';
        cCtx.textAlign = 'center';
        cCtx.fillText('If found, please return to official headquarters.', w / 2, h - 40);
    }
}

function renderBusinessCardBackground(cCtx, side, w, h) {
    if (side === 'front') {
        cCtx.fillStyle = '#ffffff';
        cCtx.fillRect(0, 0, w, h);

        const barGrad = cCtx.createLinearGradient(0, 0, 0, h);
        barGrad.addColorStop(0, secondaryColor);
        barGrad.addColorStop(1, primaryColor);
        cCtx.fillStyle = barGrad;
        cCtx.fillRect(0, 0, 24, h);

        const geomGrad = cCtx.createLinearGradient(w - 300, 0, w, h);
        geomGrad.addColorStop(0, primaryColor);
        geomGrad.addColorStop(1, '#1e293b');
        cCtx.fillStyle = geomGrad;
        cCtx.beginPath();
        cCtx.moveTo(w - 200, 0);
        cCtx.lineTo(w, 0);
        cCtx.lineTo(w, h);
        cCtx.lineTo(w - 350, h);
        cCtx.closePath();
        cCtx.fill();
    } else {
        const darkGrad = cCtx.createLinearGradient(0, 0, w, h);
        darkGrad.addColorStop(0, primaryColor);
        darkGrad.addColorStop(1, '#1e293b');
        cCtx.fillStyle = darkGrad;
        cCtx.fillRect(0, 0, w, h);

        cCtx.fillStyle = secondaryColor;
        cCtx.fillRect(w / 2 - 100, h / 2 + 100, 200, 4);
    }
}

function drawFieldsToCtx(cCtx, side) {
    formFields.forEach(field => {
        const targetSide = field.side || 'front';
        if (targetSide !== side) return;

        let rawVal = fieldValues[field.id];
        if (rawVal === undefined || rawVal === null) {
            rawVal = field.default !== undefined ? field.default : '';
        }

        const prefix = field.prefix || '';

        cCtx.save();

        if (field.type === 'file') {
            const imgUrl = rawVal;
            const imgX = field.x || 200;
            const imgY = field.y || 180;
            const imgW = field.width || 200;
            const imgH = field.height || 240;

            if (imgUrl && imgUrl.length > 5) {
                getLoadedImage(imgUrl, (img) => {
                    cCtx.save();
                    if (field.shape === 'round') {
                        cCtx.beginPath();
                        cCtx.roundRect(imgX, imgY, imgW, imgH, 20);
                        cCtx.clip();
                    }
                    cCtx.drawImage(img, imgX, imgY, imgW, imgH);
                    cCtx.restore();
                });
            } else {
                cCtx.save();
                cCtx.fillStyle = '#e2e8f0';
                cCtx.strokeStyle = '#cbd5e1';
                cCtx.lineWidth = 3;
                if (field.shape === 'round') {
                    cCtx.beginPath();
                    cCtx.roundRect(imgX, imgY, imgW, imgH, 20);
                    cCtx.fill();
                    cCtx.stroke();
                } else {
                    cCtx.fillRect(imgX, imgY, imgW, imgH);
                    cCtx.strokeRect(imgX, imgY, imgW, imgH);
                }

                cCtx.fillStyle = '#94a3b8';
                cCtx.textAlign = 'center';
                cCtx.font = 'bold 13px Inter, sans-serif';
                cCtx.fillText('PHOTO / LOGO', imgX + imgW / 2, imgY + imgH / 2);
                cCtx.restore();
            }

        } else if (field.type === 'checkbox') {
            const isChecked = rawVal === '1' || rawVal === true || rawVal === 'true';
            if (isChecked) {
                cCtx.font = `${field.bold ? 'bold ' : ''}${field.size || 14}px ${field.font || 'Inter'}, sans-serif`;
                cCtx.fillStyle = field.color || '#059669';
                cCtx.textAlign = field.align || 'center';

                let drawX = field.x;
                if (field.align === 'center') drawX = canvas.width / 2;

                const text = '✓ ' + (field.check_label || 'VERIFIED');
                cCtx.fillText(text, drawX, field.y);
            }

        } else if (field.type === 'textarea') {
            cCtx.font = `${field.bold ? 'bold ' : ''}${field.size || 15}px ${field.font || 'Inter'}, sans-serif`;
            cCtx.fillStyle = field.color || '#1e293b';
            cCtx.textAlign = field.align || 'center';

            let drawX = field.x;
            if (field.align === 'center') drawX = canvas.width / 2;

            const lines = String(rawVal).split('\n');
            let currentY = field.y;
            const lineHeight = (field.size || 15) * 1.4;

            lines.forEach(line => {
                cCtx.fillText(line, drawX, currentY);
                currentY += lineHeight;
            });

        } else {
            const displayText = prefix + String(rawVal);
            if (displayText.trim().length > 0) {
                cCtx.font = `${field.bold ? 'bold ' : ''}${field.size || 16}px ${field.font || 'Inter'}, sans-serif`;
                cCtx.fillStyle = field.color || '#1e293b';
                cCtx.textAlign = field.align || 'center';

                let drawX = field.x;
                if (field.align === 'center') drawX = canvas.width / 2;

                cCtx.fillText(displayText, drawX, field.y);
            }
        }

        cCtx.restore();
    });
}

function getLoadedImage(src, callback) {
    if (loadedImages[src]) {
        callback(loadedImages[src]);
        return;
    }

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = src;
    img.onload = () => {
        loadedImages[src] = img;
        callback(img);
    };
}

// ONE-CLICK DUAL-SIDE STITCHED COMBINED EXPORTS
function getPersonName() {
    const nameVal = fieldValues['name'] || fieldValues['person_name'] || fieldValues['doctor_name'] || fieldValues['student_name'] || 'Cardholder';
    return String(nameVal).trim().replace(/[^a-zA-Z0-9_\-]/g, '_');
}

function downloadCombinedCard(format) {
    const width = parseInt(currentTemplate.width_px || 600);
    const height = parseInt(currentTemplate.height_px || 960);
    const personName = getPersonName();

    // Create Offscreen Front & Back Canvases
    const frontCanvas = document.createElement('canvas');
    frontCanvas.width = width;
    frontCanvas.height = height;
    const fCtx = frontCanvas.getContext('2d');
    renderSideToCanvas(fCtx, 'front', width, height);

    const backCanvas = document.createElement('canvas');
    backCanvas.width = width;
    backCanvas.height = height;
    const bCtx = backCanvas.getContext('2d');
    renderSideToCanvas(bCtx, 'back', width, height);

    if (format === 'png' || format === 'jpg') {
        const gap = 40;
        const combinedCanvas = document.createElement('canvas');

        if (currentTemplate.orientation === 'portrait') {
            combinedCanvas.width = width * 2 + gap * 3;
            combinedCanvas.height = height + gap * 2;
        } else {
            combinedCanvas.width = width + gap * 2;
            combinedCanvas.height = height * 2 + gap * 3;
        }

        const cCtx = combinedCanvas.getContext('2d');
        cCtx.fillStyle = '#0f172a';
        cCtx.fillRect(0, 0, combinedCanvas.width, combinedCanvas.height);

        if (currentTemplate.orientation === 'portrait') {
            cCtx.drawImage(frontCanvas, gap, gap);
            cCtx.drawImage(backCanvas, width + gap * 2, gap);
        } else {
            cCtx.drawImage(frontCanvas, gap, gap);
            cCtx.drawImage(backCanvas, gap, height + gap * 2);
        }

        const link = document.createElement('a');
        link.download = `${personName}_Card.${format}`;
        link.href = combinedCanvas.toDataURL(format === 'png' ? 'image/png' : 'image/jpeg', 0.95);
        link.click();

        // Save card record to history
        saveCardHistory(personName, frontCanvas.toDataURL('image/png'), backCanvas.toDataURL('image/png'));

    } else if (format === 'pdf') {
        const { jsPDF } = window.jspdf;
        const frontImg = frontCanvas.toDataURL('image/jpeg', 0.95);
        const backImg = backCanvas.toDataURL('image/jpeg', 0.95);

        const pdf = new jsPDF({
            orientation: currentTemplate.orientation === 'landscape' ? 'landscape' : 'portrait',
            unit: 'px',
            format: [width, height]
        });

        // Page 1: Front
        pdf.addImage(frontImg, 'JPEG', 0, 0, width, height);

        // Page 2: Back
        pdf.addPage([width, height], currentTemplate.orientation === 'landscape' ? 'landscape' : 'portrait');
        pdf.addImage(backImg, 'JPEG', 0, 0, width, height);

        pdf.save(`${personName}_Card.pdf`);

        // Save card record to history
        saveCardHistory(personName, frontCanvas.toDataURL('image/png'), backCanvas.toDataURL('image/png'));
    }
}

function saveCardHistory(name, frontDataUrl, backDataUrl) {
    const payload = new FormData();
    payload.append('cardholder_name', name);
    payload.append('card_type', currentTemplate.type);
    payload.append('template_title', currentTemplate.title);
    payload.append('data_json', JSON.stringify(fieldValues));
    payload.append('preview_front', frontDataUrl);
    payload.append('preview_back', backDataUrl);

    fetch('api/save_history.php', {
        method: 'POST',
        body: payload
    });
}
</script>

</body>
</html>
