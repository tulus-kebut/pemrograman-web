// ─── js/main.js ─────────────────────────────────────────

// Sidebar kategori (hamburger toggle)
(function () {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('categorySidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!toggle || !sidebar || !overlay) return;

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    toggle.classList.add('open');
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    toggle.classList.remove('open');
  }
  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
  overlay.addEventListener('click', closeSidebar);
})();

// Dropdown (avatar + notif)
function setupDropdown(btnId) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  const dropdown = btn.querySelector('.dropdown');
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    document.querySelectorAll('.dropdown.open').forEach(d => { if (d !== dropdown) d.classList.remove('open'); });
    dropdown?.classList.toggle('open');
  });
}
setupDropdown('avatarBtn');
setupDropdown('notifBtn');
document.addEventListener('click', () => {
  document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
});

// Like button (video/comment/post)
document.querySelectorAll('[data-like]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const type = btn.dataset.like;
    const id   = btn.dataset.id;
    const countEl = btn.querySelector('.like-count');
    const isLiked = btn.classList.contains('liked');
    btn.classList.toggle('liked');
    if (countEl) {
      const raw = countEl.textContent.trim();
      let n = parseFloat(raw) || 0;
      if (raw.includes('K')) n *= 1000;
      if (raw.includes('M')) n *= 1000000;
      n = isLiked ? n - 1 : n + 1;
      countEl.textContent = Math.round(n);
    }
    try {
      await fetch('ajax/like.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({type, id})
      });
    } catch (e) { console.error(e); }
  });
});

// Save video toggle
document.querySelectorAll('[data-save]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const id = btn.dataset.save;
    try {
      const res = await fetch('ajax/save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({video_id: id})
      });
      const data = await res.json();
      btn.classList.toggle('liked', data.saved);
    } catch (e) { console.error(e); }
  });
});

// Save post toggle
document.querySelectorAll('[data-save-post]').forEach(btn => {
  btn.addEventListener('click', async () => {
    btn.classList.toggle('liked');
    try {
      await fetch('ajax/save_post.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({post_id: btn.dataset.savePost})
      });
    } catch (e) { console.error(e); }
  });
});

// Settings tab switcher
document.querySelectorAll('.snav-item').forEach(item => {
  item.addEventListener('click', () => {
    const target = item.dataset.target;
    if (!target) return;
    document.querySelectorAll('.snav-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    item.classList.add('active');
    document.getElementById(target)?.classList.add('active');
  });
});

// Profile tab switcher
document.querySelectorAll('.ptab').forEach(tab => {
  tab.addEventListener('click', () => {
    const target = tab.dataset.target;
    if (!target) return;
    document.querySelectorAll('.ptab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-section').forEach(s => s.style.display = 'none');
    tab.classList.add('active');
    const el = document.getElementById(target);
    if (el) el.style.display = 'block';
  });
});

// Toggle reply list visibility
document.querySelectorAll('.toggle-replies').forEach(t => {
  t.addEventListener('click', () => {
    const target = document.getElementById(t.dataset.target);
    if (!target) return;
    const show = target.style.display === 'none' || !target.style.display;
    target.style.display = show ? 'block' : 'none';
  });
});

// File drop label preview
document.querySelectorAll('.file-drop input[type=file]').forEach(input => {
  input.addEventListener('change', () => {
    const wrap = input.closest('.file-drop');
    const label = wrap.querySelector('.fname');
    if (input.files.length && label) label.textContent = input.files[0].name;
  });
});

// Flash message auto-dismiss
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity .4s';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 400);
  });
}, 4000);