const API = 'api/';

/* ---------- Icon library ---------- */
const ICONS = {
  critical: '<path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>',
  pending: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>',
  success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10.01-3-3"/>'
};
const EMOJI = { critical: '🔴', pending: '🟡', info: '🔵', success: '🟢' };
function iconSvg(type) {
  return `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[type]}</svg>`;
}

let currentFilter = 'all';
let currentSearch = '';
const groupOrder = ['आज', 'काल', 'अगोदर'];

/* ---------- Feed ---------- */
async function loadFeed() {
  const params = new URLSearchParams({ filter: currentFilter, search: currentSearch });
  const res = await fetch(`${API}notifications.php?${params}`);
  const json = await res.json();
  renderFeed(json.data || []);
}

function renderFeed(items) {
  const feedEl = document.getElementById('feed');
  const emptyEl = document.getElementById('emptyState');
  document.getElementById('feedCountLabel').textContent = `${items.length} सूचना`;

  feedEl.innerHTML = '';
  if (items.length === 0) {
    emptyEl.classList.add('show');
    feedEl.style.display = 'none';
    return;
  }
  emptyEl.classList.remove('show');
  feedEl.style.display = 'block';

  groupOrder.forEach(group => {
    const groupItems = items.filter(n => n.group === group);
    if (!groupItems.length) return;

    const heading = document.createElement('div');
    heading.className = 'group-heading';
    heading.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>${group}`;

    const timeline = document.createElement('div');
    timeline.className = 'timeline';

    heading.addEventListener('click', () => {
      heading.classList.toggle('collapsed');
      timeline.classList.toggle('collapsed');
    });

    groupItems.forEach((n, idx) => {
      const card = document.createElement('div');
      card.className = `notif-card ${n.type} ${n.unread ? 'unread' : ''}`;
      card.tabIndex = 0;
      card.setAttribute('role', 'button');
      const isLast = idx === groupItems.length - 1;
      card.innerHTML = `
        <div class="notif-spine">
          <div class="notif-icon ${n.type === 'critical' ? 'pulse' : ''}">${iconSvg(n.type)}</div>
          ${isLast ? '' : '<div class="spine-line"></div>'}
        </div>
        <div class="notif-body">
          <div class="notif-top">
            <p class="notif-title">${EMOJI[n.type]} ${n.title}</p>
            <span class="notif-time">${n.time}</span>
          </div>
          <p class="notif-desc">"${n.desc}"</p>
          <div class="notif-meta">
            <span><b>शाळा:</b> ${n.school}</span>
            <span><b>प्रकल्प:</b> ${n.project}</span>
            ${n.reason ? `<span><b>कारण:</b> ${n.reason}</span>` : ''}
          </div>
          <div class="notif-footer">
            <span class="badge">${n.priority}</span>
            <span class="notif-action">${n.action}</span>
          </div>
        </div>`;
      card.addEventListener('click', () => openDrawer(n.id));
      card.addEventListener('keypress', e => { if (e.key === 'Enter') openDrawer(n.id); });
      timeline.appendChild(card);
    });

    feedEl.appendChild(heading);
    feedEl.appendChild(timeline);
  });
}

/* ---------- Filters & search ---------- */
document.querySelectorAll('.filter-pill').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    loadFeed();
  });
});
document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', () => {
    const p = chip.dataset.priority;
    const wasActive = chip.classList.contains('active');
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
    if (wasActive) {
      currentFilter = 'all';
      document.querySelector('.filter-pill[data-filter="all"]').classList.add('active');
    } else {
      chip.classList.add('active');
      currentFilter = p;
    }
    loadFeed();
  });
});

let searchTimer;
document.getElementById('searchInput').addEventListener('input', e => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentSearch = e.target.value.trim();
    loadFeed();
  }, 250);
});
document.getElementById('filterToggleBtn').addEventListener('click', () => {
  document.getElementById('advFilters').classList.toggle('open');
});
document.getElementById('refreshBtn').addEventListener('click', () => {
  currentFilter = 'all';
  currentSearch = '';
  document.getElementById('searchInput').value = '';
  document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
  document.querySelector('.filter-pill[data-filter="all"]').classList.add('active');
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  loadFeed();
  showToast('फीड रिफ्रेश झाले');
});

/* ---------- Mark all read ---------- */
document.getElementById('markAllBtn').addEventListener('click', async () => {
  await fetch(`${API}mark_read.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ all: true })
  });
  loadFeed();
  loadStats();
  showToast('सर्व सूचना वाचल्या म्हणून चिन्हांकित करण्यात आल्या');
});

/* ---------- Export ---------- */
document.getElementById('exportBtn').addEventListener('click', () => {
  const params = new URLSearchParams({ filter: currentFilter, search: currentSearch });
  window.location.href = `${API}export.php?${params}`;
  showToast('सूचना CSV स्वरूपात निर्यात करण्यात आल्या');
});
document.getElementById('settingsBtn').addEventListener('click', () => {
  showToast('सूचना सेटिंग्ज लवकरच येत आहेत');
});

/* ---------- Drawer ---------- */
const overlay = document.getElementById('overlay');
const drawer = document.getElementById('drawer');
let activeDrawerId = null;

async function openDrawer(id) {
  activeDrawerId = id;
  const res = await fetch(`${API}notification_detail.php?id=${id}`);
  const json = await res.json();
  if (!json.ok) return;
  const n = json.data;
  const d = n.detail;

  document.getElementById('drawerTitle').textContent = n.title;
  document.getElementById('drawerSub').textContent = `${n.project} — ${n.school}`;
  document.getElementById('drawerBody').innerHTML = `
    <div class="drawer-row"><span>प्रकल्पाचे नाव</span><span>${n.project}</span></div>
    <div class="drawer-row"><span>शाळेचे नाव</span><span>${n.school}</span></div>
    <div class="drawer-row"><span>निधी स्रोत</span><span>${d.funding}</span></div>
    <div class="drawer-row"><span>सद्य टप्पा</span><span>${d.stage}</span></div>
    <div class="drawer-row"><span>विलंब दिवस</span><span>${d.delay}</span></div>
    <div class="drawer-row"><span>जिओ-टॅग स्थिती</span><span>${d.geotag}</span></div>
    <div class="drawer-row"><span>नियुक्त अधिकारी</span><span>${d.officer}</span></div>
    <div class="drawer-row"><span>आर्थिक वापर</span><span>${d.utilization}</span></div>
    <div style="margin-top:16px;">
      <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--ink-soft)">पूर्णता</span><b>${d.completion}%</b></div>
      <div class="progress-track"><div class="progress-fill" style="width:${d.completion}%"></div></div>
    </div>
    <p style="font-size:11px;color:var(--ink-faint);margin:16px 0 6px;font-weight:600;">अपलोड केलेले फोटो</p>
    <div class="photo-row">
      <div class="photo-ph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg></div>
      <div class="photo-ph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg></div>
      <div class="photo-ph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg></div>
    </div>
    <p style="font-size:11px;color:var(--ink-faint);margin:16px 0 6px;font-weight:600;">शेरा</p>
    <p style="font-size:13px;color:var(--ink-soft);line-height:1.6;margin:0;">${d.remarks || '—'}</p>
  `;

  overlay.classList.add('open');
  drawer.classList.add('open');

  // Opening a notification marks it read.
  await fetch(`${API}mark_read.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });
  loadFeed();
  loadStats();
}

function closeDrawer() {
  overlay.classList.remove('open');
  drawer.classList.remove('open');
  activeDrawerId = null;
}
overlay.addEventListener('click', closeDrawer);
document.getElementById('drawerClose').addEventListener('click', closeDrawer);

const drawerActionMap = {
  drawerApprove: 'approve',
  drawerUpdate: 'request_update',
  drawerDownload: 'download',
  drawerResolve: 'resolve'
};
Object.keys(drawerActionMap).forEach(id => {
  document.getElementById(id).addEventListener('click', async () => {
    if (!activeDrawerId) return;
    const res = await fetch(`${API}action.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: activeDrawerId, action: drawerActionMap[id] })
    });
    const json = await res.json();
    showToast(json.message || 'पूर्ण झाले');
    closeDrawer();
    loadFeed();
    loadStats();
    loadActivity();
  });
});

/* ---------- Toast ---------- */
let toastTimer;
function showToast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
}

/* ---------- Animated counters ---------- */
function animateCount(el, target) {
  let cur = 0;
  const step = Math.max(1, Math.round(target / 40));
  const tick = () => {
    cur += step;
    if (cur >= target) { el.textContent = target; return; }
    el.textContent = cur;
    requestAnimationFrame(tick);
  };
  tick();
}

/* ---------- Stats: cards, chips, rings, bar chart ---------- */
async function loadStats() {
  const res = await fetch(`${API}stats.php`);
  const json = await res.json();
  if (!json.ok) return;
  const s = json.data;

  animateCount(document.getElementById('statTotal'), s.total);
  animateCount(document.getElementById('statCritical'), s.critical);
  animateCount(document.getElementById('statPending'), s.pending);
  animateCount(document.getElementById('statResolved'), s.resolved);

  document.getElementById('chipCompletedCount').textContent = s.priority.completed.count;
  document.getElementById('chipCompletedPct').textContent = s.priority.completed.pct + '%';
  document.getElementById('chipPendingCount').textContent = s.priority.pending.count;
  document.getElementById('chipPendingPct').textContent = s.priority.pending.pct + '%';
  document.getElementById('chipDelayedCount').textContent = s.priority.delayed.count;
  document.getElementById('chipDelayedPct').textContent = s.priority.delayed.pct + '%';
  document.getElementById('chipInfoCount').textContent = s.priority.info.count;
  document.getElementById('chipInfoPct').textContent = s.priority.info.pct + '%';

  renderRings(s.rings);
  renderBarChart(s.weekly);
}

function renderRings(rings) {
  const ringGrid = document.getElementById('ringGrid');
  ringGrid.innerHTML = '';
  rings.forEach(r => {
    const pct = r.total > 0 ? Math.round((r.value / r.total) * 100) : 0;
    const R = 30, C = 2 * Math.PI * R;
    const offset = C - (pct / 100) * C;
    const item = document.createElement('div');
    item.className = 'ring-item';
    item.innerHTML = `
      <svg viewBox="0 0 74 74">
        <circle cx="37" cy="37" r="${R}" fill="none" stroke="var(--line)" stroke-width="7"/>
        <circle cx="37" cy="37" r="${R}" fill="none" stroke="var(--${r.color})" stroke-width="7" stroke-linecap="round"
          stroke-dasharray="${C}" stroke-dashoffset="${C}" transform="rotate(-90 37 37)">
          <animate attributeName="stroke-dashoffset" from="${C}" to="${offset}" dur="1.1s" fill="freeze" calcMode="spline" keySplines="0.2 0.9 0.3 1"/>
        </circle>
        <text x="37" y="41" text-anchor="middle" class="ring-val">${pct}%</text>
      </svg>
      <span class="ring-lbl">${r.label}</span>`;
    ringGrid.appendChild(item);
  });
}

function renderBarChart(weekly) {
  const barChart = document.getElementById('barChart');
  barChart.innerHTML = '';
  const maxV = Math.max(1, ...weekly.map(b => b.count));
  weekly.forEach(b => {
    const col = document.createElement('div');
    col.className = 'bar-col';
    col.innerHTML = `<div class="bar" style="height:0%" data-h="${(b.count / maxV) * 100}"></div><span class="bar-day">${b.day}</span>`;
    barChart.appendChild(col);
  });
  requestAnimationFrame(() => {
    setTimeout(() => {
      document.querySelectorAll('.bar').forEach(bar => { bar.style.height = bar.dataset.h + '%'; });
    }, 150);
  });
}

/* ---------- Deadlines ---------- */
async function loadDeadlines() {
  const res = await fetch(`${API}deadlines.php`);
  const json = await res.json();
  const list = document.getElementById('deadlineList');
  list.innerHTML = '';
  (json.data || []).forEach(d => {
    const colorVar = `var(--${d.urgency})`;
    const tintVar = `var(--${d.urgency}-tint)`;
    const row = document.createElement('div');
    row.className = 'deadline-item';
    row.style.setProperty('--d-color', colorVar);
    row.style.setProperty('--d-tint', tintVar);
    row.innerHTML = `<div class="deadline-bar"></div><div><p class="deadline-title">${d.title}</p><p class="deadline-sub">${d.sub}</p></div><span class="deadline-tag">${d.tag}</span>`;
    list.appendChild(row);
  });
}

/* ---------- Recent activity ---------- */
async function loadActivity() {
  const res = await fetch(`${API}activity.php`);
  const json = await res.json();
  const list = document.getElementById('activityList');
  list.innerHTML = '';
  (json.data || []).forEach(a => {
    const row = document.createElement('div');
    row.className = 'activity-item';
    row.innerHTML = `<span class="activity-dot"></span><div><div class="activity-text">${a.text}</div><div class="activity-time">${a.time}</div></div>`;
    list.appendChild(row);
  });
}

/* ---------- Footer sync time ---------- */
document.getElementById('lastSync').innerHTML = 'शेवटचे सिंक: <b>' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + '</b>';

/* ---------- Initial load ---------- */
loadFeed();
loadStats();
loadDeadlines();
loadActivity();