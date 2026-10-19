/* ==========================================================================
   Arrow Systems — Industry detail page behavior (vanilla JS, no dependencies)
   - "Contact With Our Team Now" form: inline validation (name, email, contact number), focus on the
     first problem, accessible status message. NOT connected to any service yet (see TODO below).
   Header, menus, search and newsletter are handled by the shared ../js/home.js.
   ========================================================================== */
(function () {
  'use strict';

  var form = document.querySelector('[data-contact-form]');
  if (!form) return;

  var status = form.querySelector('[data-form-status]');
  var fields = {
    name: form.elements.name,
    email: form.elements.email,
    phone: form.elements.phone
  };

  function setStatus(message, type) {
    status.textContent = message;
    status.className = 'form-status' + (type ? ' is-' + type : '');
  }

  function problem(key) {
    var value = fields[key].value.trim();
    if (key === 'name') return value ? '' : 'Enter your name.';
    if (key === 'email') {
      if (!value) return 'Enter your email address.';
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Enter a valid email address, like name@company.com.';
    }
    if (key === 'phone') {
      if (!value) return 'Enter a contact number.';
      return value.replace(/\D/g, '').length >= 7 ? '' : 'Enter a full contact number, including the country code.';
    }
    return '';
  }

  // Clear a field's error as soon as it is corrected.
  Object.keys(fields).forEach(function (key) {
    fields[key].addEventListener('input', function () {
      if (fields[key].getAttribute('aria-invalid') && !problem(key)) {
        fields[key].removeAttribute('aria-invalid');
        setStatus('');
      }
    });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var first = null, message = '';
    Object.keys(fields).forEach(function (key) {
      var msg = problem(key);
      if (msg) {
        fields[key].setAttribute('aria-invalid', 'true');
        if (!first) { first = fields[key]; message = msg; }
      } else {
        fields[key].removeAttribute('aria-invalid');
      }
    });
    if (first) {
      setStatus(message, 'error');
      first.focus();
      return;
    }

    // TODO(integration): submit to HubSpot (portal ID / form GUID pending) with the site's spam protection.
    // Show a success message only after HubSpot confirms the submission.
    setStatus('This form is not connected yet, so your message has not been sent. ' +
      'Please call 1-888-697-2749 or email sales@arrsys.com and our team will help.', 'pending');
  });
})();
