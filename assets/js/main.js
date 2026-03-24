// assets/js/main.js — НотаріусПРО

// Auto-hide flash alerts
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .5s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 500);
  }, 4000);
});

// Confirm delete links (additional safety)
document.querySelectorAll('a[href*="action=delete"]').forEach(a => {
  a.addEventListener('click', e => {
    if (!confirm('Ви впевнені, що хочете видалити цей запис?')) {
      e.preventDefault();
    }
  });
});

// Highlight active table row on hover (already handled via CSS)
// Mobile nav toggle (simple)
const navbar = document.querySelector('.navbar');
if (navbar) {
  const links = navbar.querySelector('.nav-links');
  if (window.innerWidth < 600 && links) {
    links.style.display = 'none';
    const toggle = document.createElement('button');
    toggle.textContent = '☰';
    toggle.style.cssText = 'background:none;border:none;color:var(--gold);font-size:1.5rem;cursor:pointer;margin-left:auto;';
    navbar.appendChild(toggle);
    toggle.addEventListener('click', () => {
      links.style.display = links.style.display === 'none' ? 'flex' : 'none';
      links.style.flexDirection = 'column';
      links.style.position = 'absolute';
      links.style.top = '60px';
      links.style.left = '0';
      links.style.right = '0';
      links.style.background = 'var(--ink)';
      links.style.padding = '1rem';
    });
  }
}
