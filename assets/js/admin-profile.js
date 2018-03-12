(function ($) {
  var CapsuleAdminProfile = {
    init: function() {
      $profile = $('.capsule-profile');
      $profile.prependTo($profile.closest('form'));
      // Reset API key.
      $('#cap-regenerate-key').on('click', function (e) {
        var id = $(this).data('user-id');
        var url = $(this).attr('href');
        e.preventDefault();
        $.post(
          url, { 
            action: 'cap_new_api_key',
            user_id: id 
          },
          function(data) {
            if (data) {
              $('#cap-api-key').html(data);
            }
          }
        );
      });
    }
  };

  $(document).ready(function () {
    CapsuleAdminProfile.init();
  });
})(jQuery);
