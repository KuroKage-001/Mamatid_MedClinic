function showMenuSelected(topMenuId, subMenuId) {
    $('.nav-item').removeClass('menu-open');
    $('.nav-link').removeClass('active');
    if (topMenuId) {
      $(topMenuId).addClass('menu-open');
      $(topMenuId + ' > .nav-link').addClass('active');
    }
    if (subMenuId) {
      $(subMenuId).addClass('active');
    }
  }