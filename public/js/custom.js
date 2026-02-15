    /* ---------- Collapsible submenus ---------- */
    // List of all submenu IDs
    const allSubmenuIds = [
      "posMenu",
      "specialordersMenu",
      "tailorMenu",
      "productionMenu",
      "inventoryMenu",
      "boutiquesMenu",
      "customersMenu",
      "expensesMenu",
      "settingsMenu",
      "reportsMenu"
    ];

    function toggleSubmenu(id) {
      const submenu = document.getElementById(id);
      const arrow = document.getElementById(`arrow-${id}`);
      
      if (!submenu || !arrow) return;
      
      const isCurrentlyOpen = submenu.classList.contains("active");
      
      // Close all other submenus
      allSubmenuIds.forEach(menuId => {
        if (menuId !== id) {
          const otherSubmenu = document.getElementById(menuId);
          const otherArrow = document.getElementById(`arrow-${menuId}`);
          if (otherSubmenu) {
            otherSubmenu.classList.remove("active");
          }
          if (otherArrow) {
            otherArrow.classList.remove("rotate-180");
          }
        }
      });
      
      // Toggle current submenu
      if (isCurrentlyOpen) {
        // If it's open, close it
        submenu.classList.remove("active");
        arrow.classList.remove("rotate-180");
      } else {
        // If it's closed, open it
        submenu.classList.add("active");
        arrow.classList.add("rotate-180");
      }
    }

    // Restore submenu states on load - only keep the one with active class (from PHP)
    document.addEventListener("DOMContentLoaded", () => {
      allSubmenuIds.forEach(id => {
        const menu = document.getElementById(id);
        const arrow = document.getElementById(`arrow-${id}`);
        
        if (menu && menu.classList.contains("active")) {
          // This menu should be open (contains active page)
          menu.classList.add("active");
          if (arrow) {
            arrow.classList.add("rotate-180");
          }
        } else {
          // Close all other menus
          if (menu) {
            menu.classList.remove("active");
          }
          if (arrow) {
            arrow.classList.remove("rotate-180");
          }
        }
      });
    });

    /* ---------- Dropdown helpers (click-to-toggle + click-outside) ---------- */
    const dropdowns = [
      { btn: "langBtn", menu: "langMenu" },
      { btn: "notifBtn", menu: "notifMenu" },
      { btn: "profileBtn", menu: "profileMenu" },
    ];

    dropdowns.forEach(({ btn, menu }) => {
      const $btn = document.getElementById(btn);
      const $menu = document.getElementById(menu);
      if (!$btn || !$menu) return;

      $btn.addEventListener("click", (e) => {
        e.stopPropagation();
        // Close others
        dropdowns.forEach(d => {
          if (d.menu !== menu) document.getElementById(d.menu)?.classList.remove("show");
        });
        // Toggle current
        $menu.classList.toggle("show");
      });

      // Close on outside click
      document.addEventListener("click", (e) => {
        if (!$menu.contains(e.target) && !$btn.contains(e.target)) {
          $menu.classList.remove("show");
        }
      });
    });

    /* ---------- Language toggle (AR/EN) with dir + flag + persistence) ---------- */
    const htmlEl = document.documentElement;
    const langFlag = document.getElementById("langFlag");
    const langMenu = document.getElementById("langMenu");

    // Function to update locale in Laravel session
    function updateLocale(locale) {
      const csrfToken = document.querySelector('meta[name="csrf-token"]');
      if (!csrfToken) {
        console.error("CSRF token not found");
        // Reload immediately if no CSRF token
        window.location.reload();
        return;
      }

      // Show loading indicator (optional)
      const body = document.body;
      body.style.cursor = 'wait';
      
      fetch("/change-locale", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken.getAttribute('content'),
          "Accept": "application/json"
        },
        body: JSON.stringify({ locale: locale })
      })
      .then(response => {
        // Always reload regardless of response
        body.style.cursor = 'default';
        window.location.reload();
      })
      .catch(error => {
        console.error("Error updating locale:", error);
        body.style.cursor = 'default';
        // Always reload even if AJAX fails
        window.location.reload();
      });
    }

    // Core: set language + direction + flag + persist + update session
    function setLang(code, reloadPage = true) {
      if (code === "en") {
        htmlEl.setAttribute("lang", "en");
        htmlEl.setAttribute("dir", "ltr");
        // Update flag only when user actually changes language
        if (langFlag) {
          langFlag.textContent = "🇬🇧";
          langFlag.setAttribute("data-locale", "en");
        }
        localStorage.setItem("ui:lang", "en");
        
        // Update Laravel session via AJAX
        if (reloadPage) {
          updateLocale("en");
        }
      } else {
        htmlEl.setAttribute("lang", "ar");
        htmlEl.setAttribute("dir", "rtl");
        // Update flag only when user actually changes language
        if (langFlag) {
          langFlag.textContent = "🇴🇲";
          langFlag.setAttribute("data-locale", "ar");
        }
        localStorage.setItem("ui:lang", "ar");
        
        // Update Laravel session via AJAX
        if (reloadPage) {
          updateLocale("ar");
        }
      }
    }

    // Get initial locale from HTML attribute (from session) or default to 'en'
    const sessionLocale = htmlEl.getAttribute("lang") || "en";
    const initialDir = htmlEl.getAttribute("dir") || "ltr";
    
    // Ensure HTML dir attribute matches session locale (fix RTL issue after login)
    if (sessionLocale === "en") {
      htmlEl.setAttribute("dir", "ltr");
    } else {
      htmlEl.setAttribute("dir", "rtl");
    }
    
    // Ensure flag matches session locale (prevent duplication)
    if (langFlag) {
      const flagLocale = langFlag.getAttribute("data-locale");
      if (flagLocale !== sessionLocale) {
        langFlag.textContent = sessionLocale === "en" ? "🇬🇧" : "🇴🇲";
        langFlag.setAttribute("data-locale", sessionLocale);
      }
    }
    
    // Check localStorage for saved preference
    const savedLocale = localStorage.getItem("ui:lang") || sessionLocale;

    // If localStorage differs from session, sync them by updating session and reloading
    if (savedLocale !== sessionLocale) {
      // Update session to match localStorage preference
      updateLocale(savedLocale);
    }

    // Handle menu clicks
    if (langMenu) {
      langMenu.querySelectorAll("[data-lang]").forEach(btn => {
        btn.addEventListener("click", () => {
          const v = btn.getAttribute("data-lang");
          setLang(v, true); // true = reload page to apply changes
          langMenu.classList.remove("show");
        });
      });
    }

document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menuBtn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");

  if (!menuBtn || !sidebar || !overlay) return;

  // Ensure overlay is hidden on page load
  overlay.style.display = "none";
  overlay.classList.remove("show");

  // عند الضغط على زر القائمة
  menuBtn.addEventListener("click", (e) => {
    e.stopPropagation(); // يمنع التداخل
    sidebar.classList.toggle("open");
    overlay.style.display = sidebar.classList.contains("open") ? "block" : "none";
  });

  // عند الضغط على الخلفية (يغلق القائمة)
  overlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.style.display = "none";
  });

  // لو ضغط المستخدم على أي مكان في الصفحة غير القائمة، تغلق تلقائيًا
  document.addEventListener("click", (e) => {
    if (
      sidebar.classList.contains("open") &&
      !sidebar.contains(e.target) &&
      e.target !== menuBtn
    ) {
      sidebar.classList.remove("open");
      overlay.style.display = "none";
    }
  });

  // Scroll sidebar to active link after a delay to ensure submenus are expanded
  setTimeout(() => {
    scrollSidebarToActiveLink();
  }, 200);
});

// Function to scroll sidebar to show the active link
function scrollSidebarToActiveLink() {
  const sidebarNav = document.querySelector('#sidebar nav');
  if (!sidebarNav) return;

  // Find active link (has bg-cyan-100 class which indicates active state)
  const activeLink = sidebarNav.querySelector('a.bg-cyan-100, button.bg-cyan-100');
  if (!activeLink) return;

  // Get bounding rectangles for accurate positioning
  const navRect = sidebarNav.getBoundingClientRect();
  const linkRect = activeLink.getBoundingClientRect();
  
  // Calculate current scroll position
  const currentScrollTop = sidebarNav.scrollTop;
  
  // Calculate the link's position relative to the nav container's content (not viewport)
  // linkRect.top is relative to viewport, navRect.top is relative to viewport
  // So linkRect.top - navRect.top gives us position relative to nav's visible top
  // Adding currentScrollTop gives us the absolute position in the scrollable content
  const linkTopInContent = currentScrollTop + (linkRect.top - navRect.top);
  
  // Get the nav container's visible height
  const navHeight = sidebarNav.clientHeight;
  const linkHeight = activeLink.offsetHeight;
  
  // Calculate scroll position to center the link in the viewport
  const scrollPosition = linkTopInContent - (navHeight / 2) + (linkHeight / 2);
  
  // Scroll smoothly to show the active link
  sidebarNav.scrollTo({
    top: Math.max(0, scrollPosition),
    behavior: 'smooth'
  });
}
