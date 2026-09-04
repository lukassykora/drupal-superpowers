(function (Drupal) {
  Drupal.behaviors.acmeToggle = {
    attach: function (context) {
      document.querySelectorAll('.acme-toggle').forEach(function (el) {
        el.addEventListener('click', function () {
          el.nextElementSibling.classList.toggle('is-open');
        });
      });
    }
  };
})(Drupal);
