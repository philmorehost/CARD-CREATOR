<?php
require_once __DIR__ . '/core/helpers.php';

if (!is_installed()) {
    header('Location: installer/index.php');
    exit;
}

$pdo = get_db_connection();
$siteTitle = get_setting('site_title', 'CARD-CREATOR');

// Fetch templates
$templates = $pdo ? $pdo->query("SELECT * FROM card_templates ORDER BY id ASC")->fetchAll() : [];

// Default active template
$activeTemplate = $templates[0] ?? [
    'id' => 1,
    'title' => 'Default Premium ID Card',
    'type' => 'id_card',
    'orientation' => 'portrait',
    'width_px' => 600,
    'height_px' => 960,
    'front_bg_image' => 'card-sample/corporate-id-card-template-scaled.jpg',
    'back_bg_image' => 'card-sample/modern-office-id-card-design-template-corporate-identity-card-design-vectoe_599186-631.avif',
    'fields_json' => json_encode([
        ['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'default' => 'ALEX JOHNSON', 'x' => 50, 'y' => 450, 'font' => 'Inter', 'size' => 28, 'color' => '#1e293b', 'align' => 'center', 'bold' => true],
        ['id' => 'role', 'label' => 'Job Title / Role', 'type' => 'text', 'default' => 'SENIOR SOFTWARE ENGINEER', 'x' => 50, 'y' => 495, 'font' => 'Inter', 'size' => 16, 'color' => '#3b82f6', 'align' => 'center', 'bold' => true],
        ['id' => 'id_no', 'label' => 'ID Number', 'type' => 'text', 'default' => 'ID NO: EMP-89210', 'x' => 50, 'y' => 540, 'font' => 'Inter', 'size' => 16, 'color' => '#475569', 'align' => 'center', 'bold' => false],
        ['id' => 'dept', 'label' => 'Department', 'type' => 'text', 'default' => 'DEPT: INNOVATION & TECH', 'x' => 50, 'y' => 575, 'font' => 'Inter', 'size' => 16, 'color' => '#475569', 'align' => 'center', 'bold' => false],
        ['id' => 'photo', 'label' => 'Member Photo', 'type' => 'image', 'x' => 50, 'y' => 260, 'width' => 180, 'height' => 220, 'shape' => 'round']
    ])
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?> - ID & Business Card Creator</title>
    <link rel="icon" type="image/svg+xml" href="favicon.php">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- html2pdf & jsPDF libraries for PDF rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .canvas-container { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col">

<!-- Top Navigation Bar -->
<header class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white text-lg shadow-lg shadow-blue-500/30">
            <?= htmlspecialchars(strtoupper(substr($siteTitle, 0, 1))) ?>
        </div>
        <div>
            <h1 class="font-extrabold text-white text-lg tracking-wide"><?= htmlspecialchars($siteTitle) ?></h1>
            <span class="text-xs text-blue-400 font-medium">Live Identity Portal & Canvas Studio</span>
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <a href="admin/dashboard.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-xl border border-slate-600 transition">
            Admin Console ⚙
        </a>
    </div>
</header>

<main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

    <!-- LEFT COLUMN: TEMPLATE SELECTOR & EDITABLE FORM BUILDER (7 Cols) -->
    <div class="lg:col-span-7 space-y-6">

        <!-- Template Selector -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 shadow-lg">
            <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-3 flex items-center space-x-2">
                <span>Select Card Template</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($templates as $t): ?>
                    <button onclick="selectTemplate(<?= htmlspecialchars(json_encode($t)) ?>)"
                            class="p-3 text-left rounded-xl border border-slate-700 hover:border-blue-500 bg-slate-900/60 transition group flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm">
                            <?= $t['type'] === 'id_card' ? '📇' : '💼' ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white group-hover:text-blue-400"><?= htmlspecialchars($t['title']) ?></p>
                            <p class="text-[10px] text-slate-400 uppercase"><?= $t['type'] ?> · <?= $t['orientation'] ?></p>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Editable Form Builder -->
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-lg space-y-6">
            <div class="flex justify-between items-center border-b border-slate-700 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Fill Card Information</h2>
                    <p class="text-xs text-slate-400">See your ID or Business card generate live in real-time as you type!</p>
                </div>
                <button onclick="addCustomField()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-md transition flex items-center space-x-1">
                    <span>+ Add Field</span>
                </button>
            </div>

            <!-- Dynamic Form Fields Container -->
            <div id="dynamicFormFields" class="space-y-4">
                <!-- Javascript will dynamically render fields here -->
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: LIVE CANVAS INTERACTIVE PREVIEW & DOWNLOAD (5 Cols) -->
    <div class="lg:col-span-5 space-y-6 flex flex-col items-center">

        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-xl w-full flex flex-col items-center space-y-6">
            <div class="flex justify-between items-center w-full border-b border-slate-700 pb-3">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Live Interactive Canvas Preview</h2>
                <div class="flex space-x-2">
                    <button id="btnViewFront" onclick="switchView('front')" class="px-3 py-1 bg-blue-600 text-white font-bold text-xs rounded-lg shadow">Front Side</button>
                    <button id="btnViewBack" onclick="switchView('back')" class="px-3 py-1 bg-slate-700 text-slate-300 font-bold text-xs rounded-lg">Back Side</button>
                </div>
            </div>

            <!-- CANVAS WORKSPACE -->
            <div id="canvasWrapper" class="relative bg-slate-950 rounded-xl p-3 border border-slate-700 flex items-center justify-center overflow-hidden">
                <canvas id="cardCanvas" class="canvas-container rounded-lg max-w-full h-auto cursor-pointer"></canvas>
            </div>

            <!-- DOWNLOAD ACTIONS -->
            <div class="grid grid-cols-3 gap-2 w-full pt-2">
                <button onclick="downloadCard('png')" class="py-2.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                    <span>🖼 PNG</span>
                </button>
                <button onclick="downloadCard('jpg')" class="py-2.5 px-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                    <span>📷 JPG</span>
                </button>
                <button onclick="downloadCard('pdf')" class="py-2.5 px-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center space-x-1">
                    <span>📄 PDF</span>
                </button>
            </div>
        </div>

    </div>

</main>

<script>
let currentTemplate = <?= json_encode($activeTemplate) ?>;
let activeView = 'front'; // 'front' or 'back'
let formFields = JSON.parse(currentTemplate.fields_json || '[]');
let fieldValues = {};
let loadedImages = {};

const canvas = document.getElementById('cardCanvas');
const ctx = canvas.getContext('2d');

// Initialize Canvas & Form Engine
document.addEventListener('DOMContentLoaded', () => {
    initForm();
});

function selectTemplate(tmpl) {
    currentTemplate = tmpl;
    formFields = JSON.parse(tmpl.fields_json || '[]');
    activeView = 'front';
    updateViewButtons();
    initForm();
}

function initForm() {
    const container = document.getElementById('dynamicFormFields');
    container.innerHTML = '';
    fieldValues = {};

    formFields.forEach((field, index) => {
        fieldValues[field.id] = field.default || '';

        const fieldDiv = document.createElement('div');
        fieldDiv.className = "p-4 bg-slate-900/80 rounded-xl border border-slate-700/80 space-y-2 relative group";

        if (field.type === 'text') {
            fieldDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">${field.label}</label>
                    <button onclick="removeField(${index})" class="text-xs text-red-400 hover:underline opacity-0 group-hover:opacity-100 transition">Remove</button>
                </div>
                <input type="text" value="${field.default || ''}" oninput="updateFieldValue('${field.id}', this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
            `;
        } else if (field.type === 'image') {
            fieldDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">${field.label}</label>
                    <button onclick="removeField(${index})" class="text-xs text-red-400 hover:underline opacity-0 group-hover:opacity-100 transition">Remove</button>
                </div>
                <div class="flex items-center space-x-3">
                    <input type="file" accept="image/*" onchange="uploadImageField('${field.id}', this)" class="text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500">
                </div>
            `;
        }

        container.appendChild(fieldDiv);
    });

    renderCanvas();
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

function addCustomField() {
    const label = prompt('Enter Field Label:');
    if (!label) return;

    const id = 'custom_' + Date.now();
    const newField = {
        id: id,
        label: label,
        type: 'text',
        default: 'Sample Text',
        x: 50,
        y: 600,
        font: 'Inter',
        size: 18,
        color: '#1e293b',
        align: 'center',
        bold: true
    };

    formFields.push(newField);
    initForm();
}

function removeField(index) {
    formFields.splice(index, 1);
    initForm();
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

function renderCanvas() {
    const width = parseInt(currentTemplate.width_px || 600);
    const height = parseInt(currentTemplate.height_px || 960);

    canvas.width = width;
    canvas.height = height;

    const bgUrl = activeView === 'front' ? currentTemplate.front_bg_image : currentTemplate.back_bg_image;

    if (bgUrl) {
        getLoadedImage(bgUrl, (bgImg) => {
            ctx.clearRect(0, 0, width, height);
            ctx.drawImage(bgImg, 0, 0, width, height);
            drawFields();
        });
    } else {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        drawFields();
    }
}

function drawFields() {
    formFields.forEach(field => {
        const targetSide = field.side || 'both';
        if (targetSide !== 'both' && targetSide !== activeView) {
            return;
        }
        const val = fieldValues[field.id] || field.default || '';

        if (field.type === 'text' && val) {
            ctx.save();
            ctx.font = `${field.bold ? 'bold ' : ''}${field.size || 20}px ${field.font || 'Inter'}, sans-serif`;
            ctx.fillStyle = field.color || '#000000';
            ctx.textAlign = field.align || 'left';

            let drawX = field.x;
            if (field.align === 'center') drawX = canvas.width / 2;
            if (field.align === 'right') drawX = canvas.width - field.x;

            ctx.fillText(val, drawX, field.y);
            ctx.restore();
        } else if (field.type === 'image' && val) {
            getLoadedImage(val, (img) => {
                ctx.save();
                const imgW = field.width || 180;
                const imgH = field.height || 220;
                let imgX = field.x;
                if (field.x === 50) imgX = (canvas.width - imgW) / 2; // Center default

                if (field.shape === 'round') {
                    ctx.beginPath();
                    ctx.roundRect(imgX, field.y, imgW, imgH, 16);
                    ctx.clip();
                }

                ctx.drawImage(img, imgX, field.y, imgW, imgH);
                ctx.restore();
            });
        }
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

function downloadCard(format) {
    if (format === 'png' || format === 'jpg') {
        const link = document.createElement('a');
        link.download = `card_${activeView}_${Date.now()}.${format}`;
        link.href = canvas.toDataURL(format === 'png' ? 'image/png' : 'image/jpeg', 0.95);
        link.click();
    } else if (format === 'pdf') {
        const { jsPDF } = window.jspdf;
        const imgData = canvas.toDataURL('image/jpeg', 1.0);
        const pdf = new jsPDF({
            orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
            unit: 'px',
            format: [canvas.width, canvas.height]
        });
        pdf.addImage(imgData, 'JPEG', 0, 0, canvas.width, canvas.height);
        pdf.save(`card_${activeView}_${Date.now()}.pdf`);
    }
}
</script>

</body>
</html>
