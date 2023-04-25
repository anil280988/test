/**
 * This file contains handling of authenticated user checkbox.
 **/
(function ($, Drupal) {
  Drupal.behaviors.jsonapi_role_access = {
    attach: function attach(context) {
      $('#edit-roles-authenticated').on('click', this.toggle).each(this.toggle);
    },
    toggle: function toggle() {
      var isAuthChecked = $('#edit-roles-authenticated').is(':checked');
      // Removing first 2 options, as they will always be Anon and Auth roles.
      $('.js-form-item.js-form-type-checkbox').slice(2).each(function () {
        this.style.display = isAuthChecked ? 'none' : '';
      });
    }
  };
})(jQuery, Drupal);
