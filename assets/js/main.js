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
   * Mobile nav toggle
   * Move #navmenu to <body> while open so position:fixed is not trapped by
   * sticky header / filters (which made the hamburger panel look empty).
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  const navmenuEl = document.querySelector('#navmenu');
  const navmenuHome = navmenuEl ? navmenuEl.parentElement : null;

  function mobileNavToogle() {
    const body = document.body;
    const opening = !body.classList.contains('mobile-nav-active');

    body.classList.toggle('mobile-nav-active');
    if (mobileNavToggleBtn) {
      mobileNavToggleBtn.classList.toggle('bi-list');
      mobileNavToggleBtn.classList.toggle('bi-x');
    }

    if (navmenuEl && navmenuHome) {
      if (opening) {
        body.appendChild(navmenuEl);
      } else if (navmenuEl.parentElement !== navmenuHome) {
        navmenuHome.appendChild(navmenuEl);
      }
    }
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Hide mobile nav on same-page/hash links and set active state on click
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', function() {
      // Remove active class from all nav links
      document.querySelectorAll('#navmenu a').forEach(link => link.classList.remove('active'));
      // Add active class to clicked link
      this.classList.add('active');
      
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
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