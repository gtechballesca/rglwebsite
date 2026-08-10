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
   * Mobile nav is controlled by the inline script next to #rgl-mobile-drawer
   * (index.html). That avoids broken Close when a stale main.js is deployed.
   * Keep these window hooks as a no-op-safe bridge if the inline script ran first.
   */
  if (typeof window.rglSetMobileNavOpen !== 'function') {
    const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
    const mobileDrawer = document.getElementById('rgl-mobile-drawer');
    let ignoreToggleUntil = 0;

    function setMobileNavOpen(open) {
      if (open) document.body.classList.add('mobile-nav-active');
      else {
        document.body.classList.remove('mobile-nav-active');
        ignoreToggleUntil = Date.now() + 400;
      }
      if (mobileNavToggleBtn) {
        mobileNavToggleBtn.classList.toggle('bi-list', !open);
        mobileNavToggleBtn.classList.toggle('bi-x', open);
        mobileNavToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      if (mobileDrawer) {
        if (open) {
          mobileDrawer.hidden = false;
          mobileDrawer.style.setProperty('display', 'block', 'important');
        } else {
          mobileDrawer.hidden = true;
          mobileDrawer.style.setProperty('display', 'none', 'important');
        }
      }
    }

    window.rglSetMobileNavOpen = setMobileNavOpen;
    window.rglCloseMobileNav = function (e) {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      setMobileNavOpen(false);
      return false;
    };
    window.rglToggleMobileNav = function (e) {
      if (e) { e.preventDefault(); e.stopPropagation(); }
      if (Date.now() < ignoreToggleUntil) return false;
      setMobileNavOpen(!document.body.classList.contains('mobile-nav-active'));
      return false;
    };

    if (mobileNavToggleBtn) {
      mobileNavToggleBtn.addEventListener('click', window.rglToggleMobileNav);
    }
    if (mobileDrawer) {
      mobileDrawer.addEventListener('click', function (e) {
        if (e.target.closest('a')) return;
        if (e.target.closest('.rgl-mobile-drawer-close') || e.target.classList.contains('rgl-mobile-drawer-backdrop') || !e.target.closest('.rgl-mobile-drawer-panel')) {
          window.rglCloseMobileNav(e);
        }
      });
    }
  }

  // Prevent any leftover template hamburger handler from fighting the drawer
  document.querySelectorAll('#navmenu a').forEach(function (link) {
    link.addEventListener('click', function () {
      document.querySelectorAll('#navmenu a').forEach(function (el) { el.classList.remove('active'); });
      this.classList.add('active');
      if (document.body.classList.contains('mobile-nav-active') && window.rglCloseMobileNav) {
        window.rglCloseMobileNav();
      }
    });
  });
  document.querySelectorAll('#rgl-mobile-drawer a').forEach(function (link) {
    link.addEventListener('click', function () {
      document.querySelectorAll('#navmenu a, #rgl-mobile-drawer a').forEach(function (el) { el.classList.remove('active'); });
      this.classList.add('active');
      var mirror = document.querySelector('#navmenu a[href="' + this.getAttribute('href') + '"]');
      if (mirror) mirror.classList.add('active');
      if (window.rglCloseMobileNav) window.rglCloseMobileNav();
    });
  });

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