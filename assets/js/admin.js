(function ($) {
  var CapsuleAdmin = {
    init: function() {
      // Menu.
      $('#adminmenu').find('a[href*="admin.php?page=capsule-projects"]')
        .attr('href', 'edit-tags.php?taxonomy=projects')
        .end()
        .find('a[href*="admin.php?page=capsule-users"]')
        .attr('href', 'users.php');
    }
  };

  $(document).ready(function () {
    CapsuleAdmin.init();
  });
})(jQuery);
