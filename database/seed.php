<?php
/**
 * Seeds a freshly-created database. Included by includes/db.php right
 * after the schema is applied — expects $pdo to already be in scope.
 */

/** @var PDO $pdo */

$pdo->beginTransaction();

// --- District & talukas ---
$pdo->prepare('INSERT INTO districts (id, name) VALUES (1, ?)')->execute(['Kolhapur']);

$talukas = [
    'Karvir', 'Panhala', 'Shahuwadi', 'Hatkanangale', 'Shirol', 'Radhanagari',
    'Gaganbawada', 'Bhudargad', 'Ajra', 'Gadhinglaj', 'Kagal', 'Chandgad',
];
$talukaId = [];
$stmt = $pdo->prepare('INSERT INTO talukas (district_id, name) VALUES (1, ?)');
foreach ($talukas as $t) {
    $stmt->execute([$t]);
    $talukaId[$t] = (int) $pdo->lastInsertId();
}

// --- Schools (one ZP school per taluka) ---
$schoolId = [];
$stmt = $pdo->prepare('INSERT INTO schools (taluka_id, name) VALUES (?, ?)');
foreach ($talukas as $t) {
    $stmt->execute([$talukaId[$t], "ZP School, $t"]);
    $schoolId[$t] = (int) $pdo->lastInsertId();
}

// --- Projects ---
// [taluka, project name, type, funding, stage, completion%, delayDays, geotag, officer, sanctioned, utilized, status]
$projects = [
    ['Karvir',       'नवीन वर्गखोली बांधकाम',        'बांधकाम',     'वार्षिक योजना',        'पाया व प्लिंथ',           42, 18, 'सत्यापित', 'R. Deshmukh', 1400000,  840000, 'blocked'],
    ['Panhala',      'शौचालय ब्लॉक दुरुस्ती',         'बिगर-बांधकाम', 'जिल्हा परिषद स्वनिधी',    'भिंती व बांधकाम',         55, 0,  'प्रलंबित',  'S. Patil',    400000,   210000, 'in_progress'],
    ['Hatkanangale', 'सीमा कुंपण',                    'बिगर-बांधकाम', 'गौण खनिज निधी',          'अंतिम काम व हस्तांतरण',   88, 0,  'गहाळ',     'A. Kale',     600000,   560000, 'in_progress'],
    ['Shirol',       'पाणी सुविधा सुधारणा',           'बिगर-बांधकाम', 'सीएसआर निधी',            'अंतिम काम व हस्तांतरण',  100, 0,  'सत्यापित', 'M. Joshi',    320000,   320000, 'completed'],
    ['Radhanagari',  'वर्गखोली नूतनीकरण',             'बांधकाम',     'वार्षिक योजना',          'पाडकाम',                  12, 9,  'सत्यापित', 'R. Deshmukh', 900000,    80000, 'blocked'],
    ['Kagal',        'कंपाऊंड भिंत',                  'बांधकाम',     'जिल्हा परिषद स्वनिधी',    'भिंती व बांधकाम',         60, 0,  'सत्यापित', 'S. Patil',    650000,   400000, 'in_progress'],
    ['Gadhinglaj',   'नवीन वर्गखोली बांधकाम',        'बांधकाम',     'वार्षिक योजना',          'अंतिम काम व हस्तांतरण',   91, 0,  'सत्यापित', 'A. Kale',    1200000,  1120000, 'in_progress'],
    ['Ajra',         'शौचालय ब्लॉक बांधकाम',         'बांधकाम',     'सीएसआर निधी',            'अंतिम काम व हस्तांतरण', 100, 0,  'सत्यापित', 'M. Joshi',    600000,   600000, 'completed'],
    ['Chandgad',     'पाणी सुविधा सुधारणा',           'बिगर-बांधकाम', 'गौण खनिज निधी',          'पाया व प्लिंथ',           20, 22, 'सत्यापित', 'R. Deshmukh', 700000,   110000, 'blocked'],
    ['Shahuwadi',    'वर्गखोली नूतनीकरण',             'बांधकाम',     'जिल्हा परिषद स्वनिधी',    'नियोजन व मंजुरी',           8, 0,  'प्रलंबित',  'S. Patil',    500000,    40000, 'in_progress'],
    ['Gaganbawada',  'सीमा कुंपण',                     'बिगर-बांधकाम', 'सीएसआर निधी',            'अंतिम काम व हस्तांतरण',  95, 0,  'गहाळ',     'A. Kale',     300000,   280000, 'in_progress'],
    ['Bhudargad',    'शौचालय ब्लॉक बांधकाम',         'बांधकाम',     'वार्षिक योजना',          'अंतिम काम व हस्तांतरण', 100, 0,  'सत्यापित', 'M. Joshi',    550000,   550000, 'completed'],
];
$projectId = [];
$stmt = $pdo->prepare('INSERT INTO projects
    (school_id, name, project_type, funding_source, stage, completion_pct, delay_days, geotag_status, officer, sanctioned_amount, utilized_amount, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($projects as $p) {
    [$taluka, $name, $type, $funding, $stage, $completion, $delay, $geotag, $officer, $sanctioned, $utilized, $status] = $p;
    $stmt->execute([$schoolId[$taluka], $name, $type, $funding, $stage, $completion, $delay, $geotag, $officer, $sanctioned, $utilized, $status]);
    $projectId[$taluka] = (int) $pdo->lastInsertId();
}

// --- Notifications ---
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
function daysAgo(int $n, string $time = '12:00:00'): string {
    return date('Y-m-d', strtotime("-$n days")) . " $time";
}

$notifications = [
    // taluka, type, title, description, reason, action, priority label, remarks, created_at, is_read
    ['Karvir', 'critical', 'काम विलंबित', 'बांधकाम 18 दिवसांनी विलंबित झाले आहे.', 'साहित्य टंचाई', 'प्रकल्प पहा', 'विलंबित',
        'स्थानिक पुरवठादाराच्या टंचाईमुळे सिमेंट वितरण विलंबित. नवीन अंदाजे वेळ 5 दिवस.', "$today 10:42:00", 0],
    ['Panhala', 'pending', 'प्रगती अद्यतन प्रलंबित', 'गेल्या 7 दिवसांपासून प्रगती अद्यतन सादर करण्यात आलेले नाही.', null, 'स्मरणपत्र पाठवा', 'प्रलंबित',
        'मुख्याध्यापकांनी गेल्या मंगळवारपासून लॉगिन केलेले नाही.', "$today 09:15:00", 0],
    ['Hatkanangale', 'info', 'जिओ-टॅग गहाळ', 'अपलोड केलेल्या फोटोमध्ये जिओ-लोकेशन नाही.', null, 'अपलोड तपासा', 'माहिती',
        'फोटो कॅमेरा कॅप्चरऐवजी गॅलरीमधून अपलोड करण्यात आला.', "$today 08:50:00", 1],
    ['Shirol', 'success', 'अडथळा निराकरण झाला', 'यापूर्वी नोंदवलेली पाणीपुरवठा समस्या निराकरण झाली आहे.', null, 'तपशील पहा', 'पूर्ण झाले',
        'सचिवांनी बोअरवेल जोडणी पूर्ववत करून तपासली.', "$yesterday 16:20:00", 1],
    ['Radhanagari', 'critical', 'काम विलंबित', 'पाडकाम टप्पा मंजुरीच्या प्रतीक्षेत 9 दिवस विलंबित आहे.', 'मंजुरी प्रलंबित', 'प्रकल्प पहा', 'विलंबित',
        'सुधारित पाडकाम योजनेवर सचिवांच्या मंजुरीची प्रतीक्षा.', "$yesterday 14:05:00", 1],
    ['Kagal', 'pending', 'निधी वापर प्रलंबित', 'अलीकडील वितरणासाठी वापरलेली रक्कम अद्यतनित करण्यात आलेली नाही.', null, 'स्मरणपत्र पाठवा', 'प्रलंबित',
        '₹1.5 लाखांचा अलीकडील हप्ता अद्याप नोंदवलेला नाही.', "$yesterday 11:30:00", 1],
    ['Gadhinglaj', 'info', 'टप्पा अद्यतनित', 'प्रकल्प अंतिम काम व हस्तांतरण टप्प्यात गेला आहे.', null, 'तपशील पहा', 'माहिती',
        'रंगकाम व फिटिंग्जचे काम सुरू आहे.', daysAgo(2, '10:00:00'), 1],
    ['Ajra', 'success', 'अहवाल मंजूर', 'सचिवांनी वापर अहवाल मंजूर केला.', null, 'तपशील पहा', 'पूर्ण झाले',
        'अंतिम अहवालावर स्वाक्षरी झाली, प्रकल्प संग्रहित.', daysAgo(3, '09:30:00'), 1],
    ['Chandgad', 'critical', 'अडथळा नोंदवला', 'जमीन वादामुळे पायाचे काम थांबले आहे.', 'जमीन वाद', 'प्रकल्प पहा', 'विलंबित',
        'जिल्हा कायदेशीर कक्षाकडे पाठवले.', daysAgo(4, '13:00:00'), 1],
    ['Shahuwadi', 'pending', 'प्रगती अद्यतन प्रलंबित', 'गेल्या 10 दिवसांपासून प्रगती अद्यतन सादर करण्यात आलेले नाही.', null, 'स्मरणपत्र पाठवा', 'प्रलंबित',
        'मुख्याध्यापकांची बदली झाली; नवीन प्रभारी अद्याप नेमलेला नाही.', daysAgo(5, '10:15:00'), 1],
    ['Gaganbawada', 'info', 'जिओ-टॅग गहाळ', 'अपलोड केलेल्या फोटोमध्ये जिओ-लोकेशन नाही.', null, 'अपलोड तपासा', 'माहिती',
        'डोंगराळ भागामुळे कॅप्चर दरम्यान जीपीएस सिग्नल कमजोर.', daysAgo(6, '15:40:00'), 1],
    ['Bhudargad', 'success', 'अडथळा निराकरण झाला', 'यापूर्वी नोंदवलेली सिमेंट पुरवठा समस्या निराकरण झाली आहे.', null, 'तपशील पहा', 'पूर्ण झाले',
        'पर्यायी स्थानिक पुरवठादार व्यवस्था करण्यात आली, काम वेळापत्रकानुसार पूर्ण.', daysAgo(7, '12:00:00'), 1],
];
$stmt = $pdo->prepare('INSERT INTO notifications
    (project_id, type, title, description, reason, action_label, priority_label, remarks, created_at, is_read)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($notifications as $n) {
    [$taluka, $type, $title, $desc, $reason, $action, $priority, $remarks, $createdAt, $isRead] = $n;
    $stmt->execute([$projectId[$taluka], $type, $title, $desc, $reason, $action, $priority, $remarks, $createdAt, $isRead]);
}

// --- Deadlines ---
$deadlines = [
    ['Karvir',       'नवीन वर्गखोली बांधकाम', date('Y-m-d', strtotime('+1 day'))],
    ['Panhala',      'शौचालय ब्लॉक दुरुस्ती',  date('Y-m-d', strtotime('+3 days'))],
    ['Hatkanangale', 'सीमा कुंपण',            date('Y-m-d', strtotime('+7 days'))],
    ['Chandgad',     'पाणी सुविधा सुधारणा',   date('Y-m-d', strtotime('-2 days'))],
];
$stmt = $pdo->prepare('INSERT INTO deadlines (project_id, label, due_date) VALUES (?, ?, ?)');
foreach ($deadlines as $d) {
    [$taluka, $label, $due] = $d;
    $stmt->execute([$projectId[$taluka], $label, $due]);
}

// --- Recent activity ---
$activity = [
    ['मुख्याध्यापकांनी ZP School, Karvir साठी फोटो अपलोड केले', 12],
    ['सचिवांनी ZP School, Ajra साठी वापर मंजूर केला', 48],
    ['सीईओंनी Q2 साठी जिल्हा अहवाल तपासला', 120],
    ['प्रकल्प पूर्ण झाला — ZP School, Shirol', 300],
];
$stmt = $pdo->prepare('INSERT INTO activity_log (message, created_at) VALUES (?, ?)');
foreach ($activity as $a) {
    [$msg, $minutesAgo] = $a;
    $stmt->execute([$msg, date('Y-m-d H:i:s', strtotime("-$minutesAgo minutes"))]);
}

$pdo->commit();