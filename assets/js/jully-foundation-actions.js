(function () {
  function setMessage(form, text) {
    var existing = form.querySelector('.jf-form-message');
    if (!existing) {
      existing = document.createElement('p');
      existing.className = 'jf-form-message mt-3 text-sm font-bold text-secondary';
      form.appendChild(existing);
    }
    existing.textContent = text;
  }

  function normalize(text) {
    return (text || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function formValue(form, selector) {
    var field = form.querySelector(selector);
    return field ? field.value.trim() : '';
  }

  function buildFormEmail(form) {
    var lines = [];
    form.querySelectorAll('input, select, textarea').forEach(function (field) {
      var label = field.id || field.name || field.placeholder || field.type || 'Field';
      var value = field.value || '';
      if (value.trim()) {
        lines.push(label + ': ' + value.trim());
      }
    });
    return lines.join('\n');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var donationAmount = '$50';
    var donationMode = 'One-Time';
    var donateButton = document.querySelector('[data-donate-action]');

    document.querySelectorAll('[data-donation-amount]').forEach(function (button) {
      button.addEventListener('click', function () {
        donationAmount = button.getAttribute('data-donation-amount');
        document.querySelectorAll('[data-donation-amount]').forEach(function (item) {
          item.classList.remove('border-2', 'border-primary', 'bg-primary/5', 'text-primary');
        });
        button.classList.add('border-2', 'border-primary', 'bg-primary/5', 'text-primary');
        if (donateButton) {
          donateButton.lastChild.textContent = ' Donate ' + donationAmount + ' Now';
        }
      });
    });

    document.querySelectorAll('[data-donation-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        donationMode = button.getAttribute('data-donation-mode');
        document.querySelectorAll('[data-donation-mode]').forEach(function (item) {
          item.classList.remove('bg-surface-container-lowest', 'shadow-sm', 'text-primary');
          item.classList.add('text-on-surface-variant');
        });
        button.classList.add('bg-surface-container-lowest', 'shadow-sm', 'text-primary');
        button.classList.remove('text-on-surface-variant');
      });
    });

    if (donateButton) {
      donateButton.addEventListener('click', function () {
        window.alert('Flutterwave checkout will be connected soon. For now, please contact Jully Foundation to complete a ' + donationMode.toLowerCase() + ' donation of ' + donationAmount + '.');
        window.location.href = '../contact/';
      });
    }

    document.querySelectorAll('[data-submit-feedback]').forEach(function (button) {
      button.addEventListener('click', function () {
        var form = button.closest('form');
        if (form) {
          if (form.getAttribute('action')) {
            return;
          }
          setMessage(form, button.getAttribute('data-submit-feedback'));
        }
      });
    });

    document.querySelectorAll('form[action$=".php"]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var action = form.getAttribute('action');
        var button = form.querySelector('[type="submit"]');
        var originalText = button ? button.textContent : '';
        if (button) {
          button.disabled = true;
          button.textContent = 'Sending...';
        }
        fetch(action, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
          body: new FormData(form)
        })
          .then(function (response) { return response.json().then(function (data) { return { ok: response.ok && data.ok, data: data }; }); })
          .then(function (result) {
            setMessage(form, result.data.message || (result.ok ? 'Thank you. Your message has been sent.' : 'Your message could not be sent.'));
            if (result.ok) form.reset();
          })
          .catch(function () {
            setMessage(form, 'The message could not be sent automatically. Please email info@jullyfoundation.org or programs@jullyfoundation.org directly.');
          })
          .finally(function () {
            if (button) {
              button.disabled = false;
              button.textContent = originalText;
            }
          });
      });
    });

    document.querySelectorAll('[data-gallery-filter]').forEach(function (button) {
      button.addEventListener('click', function () {
        var filter = normalize(button.getAttribute('data-gallery-filter'));
        document.querySelectorAll('.masonry-item').forEach(function (item) {
          var text = normalize(item.textContent);
          item.style.display = filter === 'all' || text.indexOf(filter) !== -1 ? '' : 'none';
        });
      });
    });

    document.querySelectorAll('[data-project-filter]').forEach(function (button) {
      button.addEventListener('click', function () {
        var filter = normalize(button.getAttribute('data-project-filter'));
        document.querySelectorAll('article').forEach(function (item) {
          var text = normalize(item.textContent);
          item.style.display = filter === 'all' || text.indexOf(filter) !== -1 ? '' : 'none';
        });
      });
    });

    document.querySelectorAll('[data-mobile-menu]').forEach(function (button) {
      button.addEventListener('click', function () {
        var nav = button.closest('nav');
        if (!nav) return;
        var panel = nav.querySelector('.jf-mobile-menu');
        if (!panel) {
          panel = document.createElement('div');
          panel.className = 'jf-mobile-menu md:hidden border-t border-slate-100 bg-white px-6 py-4 flex flex-col gap-3 text-sm font-bold text-slate-700';
          var links = nav.querySelectorAll('a[href]');
          links.forEach(function (link) {
            if (!link.textContent.trim()) return;
            var clone = link.cloneNode(true);
            clone.className = 'py-2 text-slate-700 hover:text-primary';
            panel.appendChild(clone);
          });
          nav.appendChild(panel);
        } else {
          panel.classList.toggle('hidden');
        }
      });
    });
  });
})();
