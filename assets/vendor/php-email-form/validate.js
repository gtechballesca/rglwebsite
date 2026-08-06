/**
 * RGL contact form — sends via Web3Forms (required on this hosting).
 */
(function () {
  "use strict";

  var WEB3FORMS_PLACEHOLDER = "YOUR_WEB3FORMS_ACCESS_KEY";
  var WEB3FORMS_SETUP =
    'Get a free access key at <a href="https://web3forms.com" target="_blank" rel="noopener">web3forms.com</a> (use <strong>info@rgl.com.ph</strong>), then paste it in <code>index.html</code> as <code>window.RGL_WEB3FORMS_KEY = "your-key";</code>';

  let forms = document.querySelectorAll(".php-email-form");

  forms.forEach(function (e) {
    e.addEventListener("submit", function (event) {
      event.preventDefault();

      let thisForm = this;
      let formData = new FormData(thisForm);

      if (window.location.protocol === "file:") {
        displayError(
          thisForm,
          'This form only works on the live website. Please submit from <a href="https://www.rgl.com.ph">www.rgl.com.ph</a>.'
        );
        return;
      }

      thisForm.querySelector(".loading").classList.add("d-block");
      thisForm.querySelector(".error-message").classList.remove("d-block");
      thisForm.querySelector(".sent-message").classList.remove("d-block");

      resolveWeb3FormsKey(thisForm)
        .then(function (web3formsKey) {
          if (!web3formsKey) {
            displayError(thisForm, "Contact form is not configured yet. " + WEB3FORMS_SETUP);
            return;
          }

          web3forms_submit(thisForm, web3formsKey, formData);
        })
        .catch(function (error) {
          displayError(thisForm, error && error.message ? error.message : String(error));
        });
    });
  });

  function isValidWeb3FormsKey(key) {
    return (
      typeof key === "string" &&
      key.trim() !== "" &&
      key.trim() !== WEB3FORMS_PLACEHOLDER
    );
  }

  function resolveWeb3FormsKey(thisForm) {
    if (typeof window.RGL_WEB3FORMS_KEY === "string" && isValidWeb3FormsKey(window.RGL_WEB3FORMS_KEY)) {
      return Promise.resolve(window.RGL_WEB3FORMS_KEY.trim());
    }

    var formKey = thisForm.getAttribute("data-web3forms-key");
    if (isValidWeb3FormsKey(formKey)) {
      return Promise.resolve(formKey.trim());
    }

    return fetch("forms/form-config.php", {
      method: "GET",
      headers: { Accept: "application/json" },
    })
      .then(function (response) {
        if (!response.ok) {
          return "";
        }
        return response.json();
      })
      .then(function (data) {
        var key = data && data.web3formsKey ? data.web3formsKey : "";
        return isValidWeb3FormsKey(key) ? key.trim() : "";
      })
      .catch(function () {
        return "";
      });
  }

  function web3forms_submit(thisForm, accessKey, formData) {
    var visitorEmail = formData.get("email") || "";
    var serviceType = formData.get("type") || "General inquiry";

    fetch("https://api.web3forms.com/submit", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        access_key: accessKey,
        subject:
          "RGL Website Quote Request from " + visitorEmail + ": " + serviceType,
        from_name: formData.get("name") || "",
        name: formData.get("name") || "",
        email: visitorEmail,
        phone: formData.get("phone") || "",
        company: formData.get("company") || "",
        service: serviceType,
        message: formData.get("message") || "",
        replyto: visitorEmail,
      }),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (response.ok && data.success) {
            return data;
          }
          throw new Error(
            (data && data.message) || "Could not send your message."
          );
        });
      })
      .then(function () {
        thisForm.querySelector(".loading").classList.remove("d-block");
        thisForm.querySelector(".sent-message").classList.add("d-block");
        thisForm.reset();
      })
      .catch(function (error) {
        var message = error && error.message ? error.message : String(error);
        console.error("Web3Forms error:", message);
        displayError(thisForm, message);
      });
  }

  function displayError(thisForm, error) {
    thisForm.querySelector(".loading").classList.remove("d-block");
    thisForm.querySelector(".error-message").innerHTML = error;
    thisForm.querySelector(".error-message").classList.add("d-block");
  }
})();
