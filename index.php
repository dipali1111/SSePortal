<?php require_once __DIR__ . '/includes/db.php'; get_db(); // ensures DB exists/seeded ?>
<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title data-en="Notification Center — Sumruddha Sala E-Portal" data-mr="सूचना केंद्र — समृद्ध शाळा ई-पोर्टल">Notification Center — Sumruddha Sala E-Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Noto+Sans+Devanagari:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  /* Marathi text uses Devanagari font; no colours/layout touched */
  html[data-lang="mr"] body { font-family: 'Noto Sans Devanagari', 'Poppins', 'Inter', sans-serif; }
  .lang-toggle{display:flex;align-items:center;gap:2px;background:var(--panel,#f1f1f4);border-radius:8px;padding:2px;}
  .lang-toggle button{border:none;background:transparent;padding:6px 12px;font-size:12.5px;font-weight:700;border-radius:6px;cursor:pointer;color:inherit;}
  .lang-toggle button.active{background:var(--brand,#4f46e5);color:#fff;}
</style>
</head>
<body>
<div class="app">

  <!-- ===== Header ===== -->
  <div class="topbar">
    <div class="topbar-title">
      <div class="bell-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="live-dot"></span>
      </div>
      <div>
        <h1 data-en="Notification Center" data-mr="सूचना केंद्र">Notification Center</h1>
        <p data-en="Monitor project activities, pending updates, delays, and important system alerts across all schools." data-mr="सर्व शाळांमधील प्रकल्प कार्यवाही, प्रलंबित अद्यतने, विलंब आणि महत्त्वाचे प्रणाली इशारे यांचे निरीक्षण करा.">Monitor project activities, pending updates, delays, and important system alerts across all schools.</p>
      </div>
    </div>
    <div class="topbar-actions">
      <div class="lang-toggle" id="langToggle" role="group" aria-label="Language switch">
        <button type="button" class="active" data-lang="en">EN</button>
        <button type="button" data-lang="mr">मराठी</button>
      </div>
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input id="searchInput" type="text" data-en-placeholder="Search notifications..." data-mr-placeholder="सूचना शोधा..." placeholder="Search notifications...">
      </div>
      <button class="btn btn-ghost" id="filterToggleBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
        <span data-en="Filter" data-mr="गाळणी">Filter</span>
      </button>
      <button class="btn btn-ghost" id="markAllBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>
        <span data-en="Mark All as Read" data-mr="सर्व वाचले म्हणून चिन्हांकित करा">Mark All as Read</span>
      </button>
      <button class="btn btn-ghost" id="exportBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4"/><path d="M4 17v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
        <span data-en="Export Alerts" data-mr="इशारे निर्यात करा">Export Alerts</span>
      </button>
      <button class="btn btn-icon btn-ghost" id="settingsBtn" aria-label="Notification settings">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 0 1-4 0v-.09A1.7 1.7 0 0 0 8.96 19a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 0 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 0 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 0 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"/></svg>
      </button>
    </div>
  </div>

  <!-- ===== Summary cards ===== -->
  <div class="stats-grid" id="statsGrid">
    <div class="stat-card total">
      <div class="stat-top">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
      </div>
      <p class="stat-count" data-count="0" id="statTotal">0</p>
      <p class="stat-label" data-en="Total Notifications" data-mr="एकूण सूचना">Total Notifications</p>
    </div>
    <div class="stat-card critical">
      <div class="stat-top">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg></div>
      </div>
      <p class="stat-count" data-count="0" id="statCritical">0</p>
      <p class="stat-label" data-en="Critical Alerts" data-mr="गंभीर इशारे">Critical Alerts</p>
    </div>
    <div class="stat-card pending">
      <div class="stat-top">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div>
      </div>
      <p class="stat-count" data-count="0" id="statPending">0</p>
      <p class="stat-label" data-en="Pending Updates" data-mr="प्रलंबित अद्यतने">Pending Updates</p>
    </div>
    <div class="stat-card resolved">
      <div class="stat-top">
        <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10.01-3-3"/></svg></div>
      </div>
      <p class="stat-count" data-count="0" id="statResolved">0</p>
      <p class="stat-label" data-en="Resolved Alerts" data-mr="निकाली इशारे">Resolved Alerts</p>
    </div>
  </div>

  <!-- ===== Priority chips ===== -->
  <div class="chip-row" id="chipRow">
    <div class="chip" data-priority="success" style="--dot:var(--success)">
      <span class="chip-dot"></span>
      <div class="chip-body"><div class="chip-count" id="chipCompletedCount">0</div><div class="chip-label" data-en="Completed" data-mr="पूर्ण">Completed</div></div>
      <span class="chip-pct" id="chipCompletedPct">0%</span>
    </div>
    <div class="chip" data-priority="pending" style="--dot:var(--warning)">
      <span class="chip-dot"></span>
      <div class="chip-body"><div class="chip-count" id="chipPendingCount">0</div><div class="chip-label" data-en="Pending" data-mr="प्रलंबित">Pending</div></div>
      <span class="chip-pct" id="chipPendingPct">0%</span>
    </div>
    <div class="chip" data-priority="critical" style="--dot:var(--critical)">
      <span class="chip-dot"></span>
      <div class="chip-body"><div class="chip-count" id="chipDelayedCount">0</div><div class="chip-label" data-en="Delayed" data-mr="विलंबित">Delayed</div></div>
      <span class="chip-pct" id="chipDelayedPct">0%</span>
    </div>
    <div class="chip" data-priority="info" style="--dot:var(--info)">
      <span class="chip-dot"></span>
      <div class="chip-body"><div class="chip-count" id="chipInfoCount">0</div><div class="chip-label" data-en="Information" data-mr="माहिती">Information</div></div>
      <span class="chip-pct" id="chipInfoPct">0%</span>
    </div>
  </div>

  <!-- ===== Main grid ===== -->
  <div class="main-grid">

    <!-- Left: feed -->
    <div class="panel">
      <div class="panel-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
          <span data-en="Live Notification Feed" data-mr="थेट सूचना फीड">Live Notification Feed</span>
        </h2>
        <div class="panel-sub-actions">
          <span style="font-size:11.5px;color:var(--ink-faint);font-weight:600;" id="feedCountLabel" data-en-suffix=" alerts" data-mr-suffix=" इशारे">— alerts</span>
        </div>
      </div>

      <div class="filter-bar" id="filterBar">
        <button class="filter-pill active" data-filter="all" data-en="All" data-mr="सर्व">All</button>
        <button class="filter-pill" data-filter="critical" data-en="Critical" data-mr="गंभीर">Critical</button>
        <button class="filter-pill" data-filter="pending" data-en="Pending" data-mr="प्रलंबित">Pending</button>
        <button class="filter-pill" data-filter="info" data-en="Info" data-mr="माहिती">Info</button>
        <button class="filter-pill" data-filter="success" data-en="Resolved" data-mr="निकाली">Resolved</button>
      </div>

      <div class="adv-filters" id="advFilters">
        <select><option data-en="District" data-mr="जिल्हा">District</option><option selected>Kolhapur</option></select>
        <select><option data-en="Taluka" data-mr="तालुका">Taluka</option><option>Karvir</option><option>Panhala</option><option>Shahuwadi</option><option>Hatkanangale</option><option>Shirol</option><option>Radhanagari</option><option>Gaganbawada</option><option>Bhudargad</option><option>Ajra</option><option>Gadhinglaj</option><option>Kagal</option><option>Chandgad</option></select>
        <select><option data-en="Project Type" data-mr="प्रकल्पाचा प्रकार">Project Type</option><option data-en="Construction" data-mr="बांधकाम">Construction</option><option data-en="Non-Construction" data-mr="बिगर-बांधकाम">Non-Construction</option></select>
        <select><option data-en="Funding Source" data-mr="निधी स्रोत">Funding Source</option><option data-en="Annual Plan" data-mr="वार्षिक योजना">Annual Plan</option><option data-en="Minor Mineral Fund" data-mr="गौण खनिज निधी">Minor Mineral Fund</option><option data-en="ZP Own Fund" data-mr="जिल्हा परिषद स्वनिधी">ZP Own Fund</option><option data-en="CSR Fund" data-mr="सीएसआर निधी">CSR Fund</option></select>
        <select><option data-en="Priority" data-mr="प्राधान्य">Priority</option><option data-en="Critical" data-mr="गंभीर">Critical</option><option data-en="Pending" data-mr="प्रलंबित">Pending</option><option data-en="Info" data-mr="माहिती">Info</option><option data-en="Resolved" data-mr="निकाली">Resolved</option></select>
        <select><option data-en="Status" data-mr="स्थिती">Status</option><option data-en="Open" data-mr="खुले">Open</option><option data-en="In Review" data-mr="पुनरावलोकनात">In Review</option><option data-en="Resolved" data-mr="निकाली">Resolved</option></select>
        <input type="date">
        <select><option data-en="Role" data-mr="भूमिका">Role</option><option data-en="CEO" data-mr="मुख्य कार्यकारी अधिकारी">CEO</option><option data-en="Sachiv" data-mr="सचिव">Sachiv</option><option data-en="HM" data-mr="मुख्याध्यापक">HM</option></select>
      </div>

      <div class="feed" id="feed"></div>

      <div class="empty-state" id="emptyState">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><circle cx="18" cy="6" r="4" fill="#fff"/><path d="m16.3 6 1.2 1.2L20 4.7" stroke="var(--success)"/></svg>
        <h3 data-en="Everything is up to date!" data-mr="सर्व काही अद्ययावत आहे!">Everything is up to date!</h3>
        <p data-en="No pending alerts or notifications." data-mr="कोणतेही प्रलंबित इशारे किंवा सूचना नाहीत.">No pending alerts or notifications.</p>
        <button class="btn btn-primary" id="refreshBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
          <span data-en="Refresh Notifications" data-mr="सूचना रिफ्रेश करा">Refresh Notifications</span>
        </button>
      </div>
    </div>

    <!-- Right: sidebar -->
    <div class="sidebar">

      <div class="side-panel">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18.7 8 13 13.7l-3-3-4.5 4.5"/></svg><span data-en="Smart Alert Analytics" data-mr="स्मार्ट इशारा विश्लेषण">Smart Alert Analytics</span></h3>
        <div class="ring-grid" id="ringGrid"></div>
        <p style="font-size:11px;color:var(--ink-faint);margin:14px 0 6px;font-weight:600;" data-en="WEEKLY COMPARISON" data-mr="साप्ताहिक तुलना">WEEKLY COMPARISON</p>
        <div class="bar-chart" id="barChart"></div>
      </div>

      <div class="side-panel">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span data-en="Upcoming Deadlines" data-mr="येणाऱ्या अंतिम मुदती">Upcoming Deadlines</span></h3>
        <div id="deadlineList"></div>
      </div>

      <div class="side-panel">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg><span data-en="Recent Activity" data-mr="अलीकडील कार्यवाही">Recent Activity</span></h3>
        <div id="activityList"></div>
      </div>

    </div>
  </div>

  <!-- ===== Footer ===== -->
  <div class="footer">
    <div class="footer-item"><span class="status-dot"></span> <span data-en="Server Status:" data-mr="सर्व्हर स्थिती:">Server Status:</span> <b style="color:var(--success)" data-en="Online" data-mr="ऑनलाइन">Online</b></div>
    <div class="footer-item" id="lastSync" data-en-prefix="Last Sync: " data-mr-prefix="शेवटचे सिंक: ">Last Sync: —</div>
    <div class="footer-item"><span data-en="Total Projects Monitored:" data-mr="एकूण देखरेख असलेले प्रकल्प:">Total Projects Monitored:</span> <b>246</b></div>
    <div class="footer-item"><span data-en="Refresh Rate:" data-mr="रिफ्रेश दर:">Refresh Rate:</span> <b>30s</b></div>
  </div>

</div>

<!-- ===== Drawer ===== -->
<div class="overlay" id="overlay"></div>
<div class="drawer" id="drawer">
  <div class="drawer-head">
    <div>
      <h3 id="drawerTitle" data-en="Alert Details" data-mr="इशाऱ्याचा तपशील">Alert Details</h3>
      <p id="drawerSub" data-en="Project overview" data-mr="प्रकल्प आढावा">Project overview</p>
    </div>
    <button class="drawer-close" id="drawerClose" aria-label="Close">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="drawer-body" id="drawerBody"></div>
  <div class="drawer-foot">
    <button class="btn btn-primary" id="drawerApprove" data-en="Approve" data-mr="मंजूर करा">Approve</button>
    <button class="btn btn-ghost" id="drawerUpdate" data-en="Request Update" data-mr="अद्यतनाची विनंती करा">Request Update</button>
    <button class="btn btn-ghost" id="drawerDownload" data-en="Download Report" data-mr="अहवाल डाउनलोड करा">Download Report</button>
    <button class="btn btn-ghost" id="drawerResolve" data-en="Mark Resolved" data-mr="निकाली म्हणून चिन्हांकित करा">Mark Resolved</button>
  </div>
</div>

<div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 7 17l-5-5"/></svg><span id="toastMsg" data-en="Done" data-mr="पूर्ण झाले">Done</span></div>

<script src="assets/js/app.js"></script>
<script>
/* ===== Language switch: EN <-> MR (structure & CSS untouched) ===== */
(function () {
  function applyLanguage(lang) {
    document.documentElement.setAttribute('lang', lang === 'mr' ? 'mr' : 'en');
    document.documentElement.setAttribute('data-lang', lang);

    // Elements whose full text/innerHTML swaps
    document.querySelectorAll('[data-en]').forEach(function (el) {
      var val = el.getAttribute(lang === 'mr' ? 'data-mr' : 'data-en');
      if (val !== null) el.textContent = val;
    });

    // Placeholder swap (inputs)
    document.querySelectorAll('[data-en-placeholder]').forEach(function (el) {
      var val = el.getAttribute(lang === 'mr' ? 'data-mr-placeholder' : 'data-en-placeholder');
      if (val !== null) el.setAttribute('placeholder', val);
    });

    // Prefix swap (e.g. "Last Sync: —")
    document.querySelectorAll('[data-en-prefix]').forEach(function (el) {
      var prefix = el.getAttribute(lang === 'mr' ? 'data-mr-prefix' : 'data-en-prefix');
      var current = el.textContent;
      var enPrefix = el.getAttribute('data-en-prefix');
      var mrPrefix = el.getAttribute('data-mr-prefix');
      var rest = current.replace(enPrefix, '').replace(mrPrefix, '');
      el.textContent = prefix + rest;
    });

    // Suffix swap (e.g. "— alerts")
    document.querySelectorAll('[data-en-suffix]').forEach(function (el) {
      var suffix = el.getAttribute(lang === 'mr' ? 'data-mr-suffix' : 'data-en-suffix');
      var current = el.textContent;
      var enSuffix = el.getAttribute('data-en-suffix');
      var mrSuffix = el.getAttribute('data-mr-suffix');
      var base = current.replace(enSuffix, '').replace(mrSuffix, '');
      el.textContent = base + suffix;
    });

    document.querySelectorAll('.lang-toggle button').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
    });

    try { localStorage.setItem('siteLang', lang); } catch (e) {}
  }

  document.getElementById('langToggle').addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-lang]');
    if (!btn) return;
    applyLanguage(btn.getAttribute('data-lang'));
  });

  var saved = 'en';
  try { saved = localStorage.getItem('siteLang') || 'en'; } catch (e) {}
  applyLanguage(saved);
})();
</script>
</body>
</html>