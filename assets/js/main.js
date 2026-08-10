/**
* Template Name: Constructo
* Template URL: https://bootstrapmade.com/constructo-bootstrap-construction-template/
* Updated: Aug 30 2025 with Bootstrap v5.3.8
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle — uses #rgl-mobile-drawer on <body> (not header nav).
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  const mobileDrawer = document.getElementById('rgl-mobile-drawer');

  function setMobileNavOpen(open) {
    if (open) {
      document.body.classList.add('mobile-nav-active');
    } else {
      document.body.classList.remove('mobile-nav-active');
    }

    if (mobileNavToggleBtn) {
      if (open) {
        mobileNavToggleBtn.classList.remove('bi-list');
        mobileNavToggleBtn.classList.add('bi-x');
      } else {
        mobileNavToggleBtn.classList.add('bi-list');
        mobileNavToggleBtn.classList.remove('bi-x');
      }
      mobileNavToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      mobileNavToggleBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    if (mobileDrawer) {
      if (open) {
        mobileDrawer.hidden = false;
        mobileDrawer.removeAttribute('hidden');
        mobileDrawer.style.setProperty('display', 'block', 'important');
      } else {
        mobileDrawer.hidden = true;
        mobileDrawer.setAttribute('hidden', '');
        mobileDrawer.style.setProperty('display', 'none', 'important');
      }
    }
  }

  // Expose for inline onclick fallback on Close button
  window.rglCloseMobileNav = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    setMobileNavOpen(false);
    return false;
  };

  window.rglToggleMobileNav = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    setMobileNavOpen(!document.body.classList.contains('mobile-nav-active'));
    return false;
  };

  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.setAttribute('role', 'button');
    mobileNavToggleBtn.setAttribute('tabindex', '0');
    mobileNavToggleBtn.setAttribute('aria-label', 'Open menu');
    mobileNavToggleBtn.setAttribute('aria-controls', 'rgl-mobile-drawer');
    mobileNavToggleBtn.setAttribute('aria-expanded', 'false');
    mobileNavToggleBtn.addEventListener('click', window.rglToggleMobileNav);
    mobileNavToggleBtn.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        window.rglToggleMobileNav(e);
      }
    });
  }

  if (mobileDrawer) {
    // Single delegated handler — Close button, backdrop, or outside panel
    function onDrawerPointer(e) {
      const t = e.target;
      if (
        t.closest('.rgl-mobile-drawer-close') ||
        t.classList.contains('rgl-mobile-drawer-backdrop') ||
        t === mobileDrawer ||
        !t.closest('.rgl-mobile-drawer-panel')
      ) {
        // Don't steal clicks from nav links
        if (t.closest('a')) return;
        window.rglCloseMobileNav(e);
      }
    }

    mobileDrawer.addEventListener('click', onDrawerPointer);
    mobileDrawer.addEventListener('pointerup', onDrawerPointer);
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.body.classList.contains('mobile-nav-active')) {
      setMobileNavOpen(false);
    }
  });

  /**
   * Hide mobile nav on same-page/hash links and set active state on click
   */
  function bindNavLink(link) {
    link.addEventListener('click', function() {
      document.querySelectorAll('#navmenu a, #rgl-mobile-drawer a').forEach(el => el.classList.remove('active'));
      this.classList.add('active');
      const mirror = document.querySelector('#navmenu a[href="' + this.getAttribute('href') + '"]');
      if (mirror) mirror.classList.add('active');
      if (document.body.classList.contains('mobile-nav-active')) {
        setMobileNavOpen(false);
      }
    });
  }
  document.querySelectorAll('#navmenu a').forEach(bindNavLink);
  document.querySelectorAll('#rgl-mobile-drawer a').forEach(bindNavLink);

  /**
   * Navmenu Scrollspy - Update active nav link based on scroll position
   */
  const navmenulinks = document.querySelectorAll('#navmenu a');

  function navmenuScrollspy() {
    const scrollPosition = window.scrollY + 200; // Offset for header height

    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;

      const section = document.querySelector(navmenulink.hash);
      if (!section) return;

      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
        // Remove active from all links
        navmenulinks.forEach(link => link.classList.remove('active'));
        // Add active to current link
        navmenulink.classList.add('active');
      }
    });

    // Special case: if at top of page, activate Home
    if (window.scrollY < 100) {
      navmenulinks.forEach(link => link.classList.remove('active'));
      const homeLink = document.querySelector('#navmenu a[href="#hero"]');
      if (homeLink) homeLink.classList.add('active');
    }
  }

  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  scrollTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      let config = JSON.parse(
        swiperElement.querySelector(".swiper-config").innerHTML.trim()
      );

      if (swiperElement.classList.contains("swiper-tab")) {
        initSwiperWithCustomPagination(swiperElement, config);
      } else {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener("load", initSwiper);

  /**
   * Initiate glightbox
   */
  const glightbox = GLightbox({
    selector: '.glightbox'
  });

})();