/**
 * PHP Email Form Validation - v3.11 (RGL)
 * URL: https://bootstrapmade.com/php-email-form/
 * Author: BootstrapMade.com
 */
(function () {
  "use strict";

  let forms = document.querySelectorAll('.php-email-form');

  forms.forEach(function (e) {
    e.addEventListener('submit', function (event) {
      event.preventDefault();

      let thisForm = this;

      // Block overlapping submits (common cause of "2nd attempt" failures)
      if (thisForm.dataset.submitting === '1') {
        return;
      }

      let action = thisForm.getAttribute('action');
      let recaptcha = (thisForm.getAttribute('data-recaptcha-site-key') || '').trim();

      if (!action) {
        displayError(thisForm, 'The form action property is not set!');
        return;
      }

      if (window.location.protocol === 'file:') {
        displayError(
          thisForm,
          'Open this site via your z.com URL (or a local PHP server), not as a local file. PHP forms cannot run from file://'
        );
        return;
      }

      if (!recaptcha || recaptcha === 'YOUR_RECAPTCHA_SITE_KEY') {
        displayError(
          thisForm,
          'reCAPTCHA is not configured. Add your Site Key in index.html (form data-recaptcha-site-key and api.js?render=).'
        );
        return;
      }

      thisForm.dataset.submitting = '1';
      thisForm.querySelector('.loading').classList.add('d-block');
      thisForm.querySelector('.error-message').classList.remove('d-block');
      thisForm.querySelector('.sent-message').classList.remove('d-block');

      let formData = new FormData(thisForm);

      if (typeof grecaptcha === 'undefined') {
        thisForm.dataset.submitting = '0';
        displayError(thisForm, 'The reCAPTCHA script failed to load. Check your Site Key and network.');
        return;
      }

      grecaptcha.ready(function () {
        try {
          grecaptcha
            .execute(recaptcha, { action: 'inquiry_submit' })
            .then((token) => {
              if (!token) {
                thisForm.dataset.submitting = '0';
                displayError(thisForm, 'reCAPTCHA did not return a token. Please refresh and try again.');
                return;
              }
              formData.set('recaptcha-response', token);
              formData.set('g-recaptcha-response', token);
              php_email_form_submit(thisForm, action, formData);
            })
            .catch(function (error) {
              thisForm.dataset.submitting = '0';
              displayError(thisForm, error);
            });
        } catch (error) {
          thisForm.dataset.submitting = '0';
          displayError(thisForm, error);
        }
      });
    });
  });

  function php_email_form_submit(thisForm, action, formData) {
    fetch(action, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      cache: 'no-store',
    })
      .then((response) => {
        if (response.ok) {
          return response.text();
        }
        throw new Error(
          `${response.status} ${response.statusText} ${response.url}`
        );
      })
      .then((data) => {
        thisForm.dataset.submitting = '0';
        thisForm.querySelector('.loading').classList.remove('d-block');

        let body = (data || '').trim();

        // z.com / Imunify360 bot shield often returns this HTML on repeat POSTs
        if (
          /one moment,? please/i.test(body) ||
          /<!DOCTYPE html>/i.test(body) ||
          /<html[\s>]/i.test(body)
        ) {
          throw new Error(
            'Your host blocked this submit (bot protection). In cPanel → Imunify360, whitelist forms/get-a-quote.php, then try again.'
          );
        }

        if (body === 'OK') {
          thisForm.querySelector('.sent-message').classList.add('d-block');
          thisForm.reset();
        } else {
          throw new Error(
            body
              ? body
              : 'Form submission failed and no error message returned from: ' +
                  action
          );
        }
      })
      .catch((error) => {
        thisForm.dataset.submitting = '0';
        displayError(thisForm, error);
      });
  }

  function displayError(thisForm, error) {
    thisForm.dataset.submitting = '0';
    thisForm.querySelector('.loading').classList.remove('d-block');
    let message =
      error && typeof error === 'object' && 'message' in error
        ? error.message
        : String(error);
    thisForm.querySelector('.error-message').textContent = message;
    thisForm.querySelector('.error-message').classList.add('d-block');
  }
})();
