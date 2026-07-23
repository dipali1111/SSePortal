<?php
/**
 * Samruddha Shala E-Portal
 * Landing page for bilingual toggle, gallery upload, live camera capture, and login.
 */

$uploadDir = realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadMessage = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $capturedData = trim($_POST['captured_image'] ?? '');
    $projectNotes = trim($_POST['project_notes'] ?? '');

    if ($capturedData !== '') {
        if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $capturedData, $matches)) {
            $type = strtolower($matches[1]);
            $data = substr($capturedData, strpos($capturedData, ',') + 1);
            $decoded = base64_decode($data);
            if ($decoded === false) {
                $uploadError = 'Unable to decode captured image. Please try again.';
            } else {
                $filename = sprintf('captured_%s.%s', date('YmdHis'), $type === 'jpeg' ? 'jpg' : $type);
                $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                if (file_put_contents($destination, $decoded) !== false) {
                    $uploadMessage = 'Captured photo uploaded successfully.';
                } else {
                    $uploadError = 'Failed to save captured photo. Check folder permissions.';
                }
            }
        } else {
            $uploadError = 'Captured image format is not supported.';
        }
    } elseif (!empty($_FILES['photoFile']['name'])) {
        $file = $_FILES['photoFile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadError = 'File upload error. Please try again.';
        } else {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $uploadError = 'Invalid file type. Please upload a JPG, PNG, GIF, or WEBP image.';
            } else {
                $filename = sprintf('gallery_%s.%s', date('YmdHis'), $extension);
                $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $uploadMessage = 'Gallery photo uploaded successfully.';
                } else {
                    $uploadError = 'Failed to save uploaded file. Please check server permissions.';
                }
            }
        }
    } else {
        $uploadError = 'Please select a file or capture a photo before uploading.';
    }
}

$alerts = [
    [
        'en' => 'New Update: Uploading geo-tagged photos for all school projects is mandatory.',
        'mr' => 'नवीन अपडेट: सर्व शाळा प्रकल्पांसाठी जिओ-टॅग केलेले फोटो अपलोड करणे अनिवार्य आहे.'
    ],
    [
        'en' => 'Alert: The list of pending/delayed projects is available on the CEO Dashboard.',
        'mr' => 'अलर्ट: प्रलंबित/विलंब झालेल्या प्रकल्पांची यादी मुख्य कार्यकारी अधिकारी (CEO) डॅशबोर्डवर उपलब्ध आहे.'
    ],
    [
        'en' => 'Notice: Please submit the financial Utilization Certificate (UC) for ongoing works immediately.',
        'mr' => 'सूचना: चालू असलेल्या कामांसाठी कृपया आर्थिक उपयुक्तता प्रमाणपत्र (UC) त्वरित सादर करा.'
    ]
];

$galleryImages = [
    [
        'title_en' => 'Classroom Construction Works',
        'title_mr' => 'वर्गखोली बांधकाम कार्य',
        'stage_en' => 'Completed (100%)',
        'stage_mr' => 'पूर्ण (१००%)',
        'badge_class' => 'badge-faint-blue',
        'url' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'title_en' => 'School Sanitation Facility',
        'title_mr' => 'शाळा स्वच्छतागृह सुविधा',
        'stage_en' => 'Pending (20%)',
        'stage_mr' => 'प्रलंबित (२०%)',
        'badge_class' => 'badge-faint-orange',
        'url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'title_en' => 'School Grounds & Infrastructure',
        'title_mr' => 'शाळा मैदान आणि पायाभूत सुविधा',
        'stage_en' => 'Completed (100%)',
        'stage_mr' => 'पूर्ण (१००%)',
        'badge_class' => 'badge-faint-blue',
        'url' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80'
    ]
];

$heroBgUrl = 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1600&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-en="Samruddha Shala E-Portal | Integrated Tracking System" data-mr="समृद्ध शाळा ई-पोर्टल | एकात्मिक मागोवा प्रणाली">Samruddha Shala E-Portal | Integrated Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --faint-orange: #f4a261;
            --faint-orange-light: #fdf0ed;
            --faint-orange-hover: #e76f51;
            --faint-blue: #5b8fb9;
            --faint-blue-light: #e8f1f5;
            --faint-blue-dark: #3b6070;
            --text-dark: #2c3e50;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7fbff;
            color: var(--text-dark);
            padding-top: 110px;
        }
        .fixed-header-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            background: #ffffff;
            box-shadow: 0 5px 18px rgba(0,0,0,0.08);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.35rem;
        }
        .navbar-light .nav-link {
            color: rgba(0,0,0,0.72);
        }
        .navbar-light .nav-link:hover,
        .navbar-light .nav-link.active {
            color: #000000;
        }
        .btn-faint-blue {
            background-color: var(--faint-blue);
            color: #ffffff;
        }
        .btn-faint-orange {
            background-color: var(--faint-orange);
            color: #ffffff;
        }
        .hero-section {
            background: linear-gradient(180deg, rgba(91,143,185,0.84), rgba(244,162,97,0.72)), url('<?= htmlspecialchars($heroBgUrl); ?>') center/cover no-repeat;
            color: #ffffff;
            padding: 100px 0 80px;
            text-align: center;
        }
        .gallery-img {
            min-height: 220px;
            object-fit: cover;
        }
        #cameraStream,
        #capturedPreview {
            width: 100%;
            border-radius: 10px;
            display: none;
        }
        #cameraStream {
            background: #000;
        }
        #capturedCanvas {
            display: none;
        }
        .data-card {
            border: 1px solid #e6eef6;
        }
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 120px;
        }
    </style>
</head>
<body>
    <div class="fixed-header-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="#home">
                    <i class="fa-solid fa-school me-2" style="color: var(--faint-blue);"></i>
                    <span data-en="Samruddha Shala E-Portal" data-mr="समृद्ध शाळा ई-पोर्टल">Samruddha Shala E-Portal</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item"><a class="nav-link active" href="#home" data-en="Home" data-mr="मुख्य पृष्ठ">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about" data-en="About" data-mr="माहिती">About</a></li>
                        <li class="nav-item"><a class="nav-link" href="#gallery" data-en="Gallery" data-mr="गॅलरी">Gallery</a></li>
                        <li class="nav-item ms-2">
                            <button class="btn btn-faint-blue btn-sm" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                                <i class="fa-solid fa-camera me-1"></i><span data-en="Upload Photo" data-mr="फोटो अपलोड करा">Upload Photo</span>
                            </button>
                        </li>
                        <!-- Language Toggle Button in Header -->
                        <li class="nav-item ms-2">
                            <button id="langToggleBtn" class="btn btn-outline-primary btn-sm fw-bold" onclick="toggleLanguage()">
                                <i class="fa-solid fa-globe me-1"></i><span id="langBtnText">मराठी</span>
                            </button>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-light btn-sm border" href="login.php">
                                <i class="fa-solid fa-right-to-bracket me-1"></i><span data-en="Login" data-mr="लॉगिन">Login</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <section id="home" class="hero-section">
        <div class="container">
            <h1 class="display-5 fw-bold" data-en="Kolhapur ZP School Infrastructure Tracking System" data-mr="कोल्हापूर जिल्हा परिषद शाळा पायाभूत सुविधा ट्रॅकिंग प्रणाली">Kolhapur ZP School Infrastructure Tracking System</h1>
            <p class="lead mt-4 mb-4" data-en="Integrated Tracking and Management System for School Project Progress and Photo Uploads." data-mr="शाळा प्रकल्प प्रगती आणि फोटो अपलोडसाठी एकात्मिक ट्रॅकिंग आणि व्यवस्थापन प्रणाली.">Integrated Tracking and Management System for School Project Progress and Photo Uploads.</p>
            <a href="#gallery" class="btn btn-faint-orange btn-lg me-2" data-en="View Gallery" data-mr="गॅलरी पहा">View Gallery</a>
            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal" data-en="Upload Photo" data-mr="फोटो अपलोड करा">Upload Photo</button>
        </div>
    </section>

    <section id="about" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold" data-en="About the Portal" data-mr="पोर्टलबद्दल माहिती">About the Portal</h2>
                <p class="text-muted" data-en="A portal for Kolhapur ZP school project monitoring, bilingual reporting, and geo-tagged photo upload." data-mr="कोल्हापूर ZP शाळा प्रकल्प देखरेख, द्विभाषिक अहवाल आणि जिओ-टॅग फोटो अपलोडसाठी पोर्टल.">A portal for Kolhapur ZP school project monitoring, bilingual reporting, and geo-tagged photo upload.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card data-card shadow-sm h-100 p-4">
                        <div class="card-body">
                            <h5 class="fw-bold" data-en="Photo Upload" data-mr="फोटो अपलोड">Photo Upload</h5>
                            <p class="text-muted" data-en="Upload images from the gallery or capture them live using a camera." data-mr="गॅलरीमधून प्रतिमा अपलोड करा किंवा कॅमेरा वापरून थेट टिपा.">Upload images from the gallery or capture them live using a camera.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card data-card shadow-sm h-100 p-4">
                        <div class="card-body">
                            <h5 class="fw-bold" data-en="Bilingual Support" data-mr="द्विभाषिक समर्थन">Bilingual Support</h5>
                            <p class="text-muted" data-en="Toggle the portal interface between English and Marathi with one click." data-mr="एका क्लिकमध्ये पोर्टल इंटरफेस इंग्रजी आणि मराठी मध्ये बदल करा.">Toggle the portal interface between English and Marathi with one click.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card data-card shadow-sm h-100 p-4">
                        <div class="card-body">
                            <h5 class="fw-bold" data-en="Project Gallery" data-mr="प्रकल्प गॅलरी">Project Gallery</h5>
                            <p class="text-muted" data-en="View featured progress photos for ongoing and completed school infrastructure works." data-mr="चालू आणि पूर्ण झालेल्या शाळा पायाभूत सुविधा कामांसाठी वैशिष्ट्यीकृत प्रगतीचे फोटो पहा.">View featured progress photos for ongoing and completed school infrastructure works.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="gallery" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold" data-en="Project Gallery" data-mr="प्रकल्प गॅलरी">Project Gallery</h2>
                <p class="text-muted" data-en="Photo highlights from Kolhapur ZP school projects." data-mr="कोल्हापूर Zपी शाळा प्रकल्पांमधील फोटो हायलाइट्स.">Photo highlights from Kolhapur ZP school projects.</p>
            </div>
            <div class="row g-4">
                <?php foreach ($galleryImages as $img): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <img src="<?= htmlspecialchars($img['url']); ?>" class="card-img-top gallery-img" alt="<?= htmlspecialchars($img['title_en']); ?>">
                            <div class="card-body">
                                <h6 class="fw-bold" data-en="<?= htmlspecialchars($img['title_en']); ?>" data-mr="<?= htmlspecialchars($img['title_mr']); ?>"><?= htmlspecialchars($img['title_en']); ?></h6>
                                <span class="badge bg-secondary" data-en="<?= htmlspecialchars($img['stage_en']); ?>" data-mr="<?= htmlspecialchars($img['stage_mr']); ?>"><?= htmlspecialchars($img['stage_en']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="py-4 bg-dark text-white">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 data-en="Contact" data-mr="संपर्क">Contact</h6>
                    <p class="small" data-en="Education Department, Zilla Parishad Kolhapur" data-mr="शिक्षण विभाग, जिल्हा परिषद कोल्हापूर">Education Department, Zilla Parishad Kolhapur</p>
                </div>
                <div class="col-md-6 mb-3">
                    <p class="small">support@zpkolhapur-eportal.gov.in</p>
                </div>
            </div>
            <p class="mb-0 small">&copy; <?= date('Y'); ?> Samruddha Shala E-Portal</p>
        </div>
    </footer>

    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-faint-blue text-white">
                    <h5 class="modal-title" id="uploadPhotoModalLabel" data-en="Upload Project Photo" data-mr="प्रकल्प फोटो अपलोड करा">Upload Project Photo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data" class="modal-body">
                    <?php if ($uploadMessage): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($uploadMessage); ?></div>
                    <?php elseif ($uploadError): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($uploadError); ?></div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="photoFile" class="form-label" data-en="Select from Gallery" data-mr="गॅलरीमधून निवडा">Select from Gallery</label>
                            <input type="file" class="form-control" id="photoFile" name="photoFile" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" data-en="Capture from Camera" data-mr="कॅमेराद्वारे कॅप्चर करा">Capture from Camera</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-faint-blue" onclick="startCamera()" data-en="Start Camera" data-mr="कॅमेरा सुरू करा">Start Camera</button>
                                <button type="button" id="captureBtn" class="btn btn-faint-orange" onclick="capturePhoto()" disabled data-en="Capture Photo" data-mr="फोटो कॅप्चर करा">Capture Photo</button>
                            </div>
                        </div>
                        <div class="col-12">
                            <video id="cameraStream" autoplay playsinline muted></video>
                            <canvas id="capturedCanvas"></canvas>
                            <img id="capturedPreview" alt="Captured preview">
                            <input type="hidden" name="captured_image" id="captured_image" value="">
                        </div>
                        <div class="col-12">
                            <label for="projectNotes" class="form-label" data-en="Project Notes (optional)" data-mr="प्रकल्प टिप्पण्या (ऐच्छिक)">Project Notes (optional)</label>
                            <textarea id="projectNotes" class="form-control" name="project_notes" rows="3" placeholder="Optional remarks about this upload..." data-en="Optional remarks about this upload..." data-mr="हा अपलोड बद्दल ऐच्छिक टिप्पण्या..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-en="Close" data-mr="बंद करा">Close</button>
                        <button type="submit" name="upload_photo" class="btn btn-faint-blue" data-en="Upload Photo" data-mr="फोटो अपलोड करा">Upload Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentLang = 'en';

        function setLanguage(lang) {
            currentLang = lang;
            
            // 1. Update text for all elements carrying translation attributes
            document.querySelectorAll('[data-en][data-mr]').forEach(el => {
                const text = el.getAttribute(lang === 'mr' ? 'data-mr' : 'data-en');
                if (text) {
                    const tagName = el.tagName.toLowerCase();
                    if (tagName === 'input' || tagName === 'textarea') {
                        el.placeholder = text;
                    } else {
                        el.textContent = text;
                    }
                }
            });

            // 2. Update Document Title
            const titleEl = document.querySelector('title');
            if (titleEl) {
                const titleText = titleEl.getAttribute(lang === 'mr' ? 'data-mr' : 'data-en');
                if (titleText) {
                    document.title = titleText;
                }
            }

            // 3. Update Toggle Button Text
            const langBtnText = document.getElementById('langBtnText');
            if (langBtnText) {
                langBtnText.textContent = lang === 'en' ? 'मराठी' : 'English';
            }

            // Save preference to localStorage
            localStorage.setItem('portal_lang', lang);
        }

        function toggleLanguage() {
            const newLang = currentLang === 'en' ? 'mr' : 'en';
            setLanguage(newLang);
        }

        // Initialize language state on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedLang = localStorage.getItem('portal_lang') || 'en';
            setLanguage(savedLang);
        });
    </script>
</body>
</html>