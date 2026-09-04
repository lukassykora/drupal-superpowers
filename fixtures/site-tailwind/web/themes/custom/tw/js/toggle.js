((Drupal, once) => {
  Drupal.behaviors.twToggle = {
    attach(context) {
      once('tw-toggle', '[data-tw-toggle]', context).forEach((el) => {
        el.addEventListener('click', () => el.classList.toggle('hidden'));
      });
    },
  };
})(Drupal, once);
