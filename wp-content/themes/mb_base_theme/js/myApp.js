jQuery(document).ready(function () {


  jQuery(document).foundation();

  // toggles
  jQuery('#search-filter').on('click', '.toggled', function (e) {
    e.preventDefault;
    jQuery(e.target).toggleClass('toggledClosed');
    jQuery(e.target).siblings('ul').toggleClass('isHidden');
  });

  jQuery('.searchList').on('click', 'li', function (e) {
    e.preventDefault;
    var t = jQuery(this);
    //jQuery(e.target).parent().parent().next().toggleClass('toggledClosed');
    jQuery(e.target).parent().parent().find('a').toggleClass('toggledClosed');
    t.parent().prev().prev().text(t.text());
    t.parent().prev().val(t.text());
    jQuery(e.target).parent().toggleClass('isHidden');
  });

  jQuery('#enhSearch').on('click', '#AjaxClearSearch', function (e) {
    // set search vars to empty
    jQuery('#contentF').val("");
    jQuery('input[name=searchResourceCategory]').val("");
    jQuery('input[name=searchVideoContrib]').val("");
    jQuery('input[name=paged]').val("");
    jQuery('div.currentValue').text("<not selected>");
    doAjaxSearch(e);


  });

  jQuery('#enhSearch').on('click', '#AjaxSubmit', doAjaxSearch);

  function doAjaxSearch(e) {
    // jQuery('#enhSearch').on('click', '#AjaxSubmit', function (e) {
    e.preventDefault();

    // post the search data to the back end
    var search_str = jQuery('#contentF').val();
    var s_cat = jQuery('input[name=searchResourceCategory]').val();
    var s_contrib = jQuery('input[name=searchVideoContrib]').val();
    var s_paged = jQuery('input[name=paged]').val();

    jQuery.ajax({
      url: '/wp-admin/admin-ajax.php',
      type: 'post',
      dataType: 'text',
      data: {
        action: 'mb_search',
        s_string: search_str,
        s_cat: s_cat,
        s_contrib: s_contrib,
        s_paged: s_paged
      },
      success: function (response) {
        //alert(response)
        // just dump the response into the VideoItems div
        jQuery('#VideoItems').html(response);
      }
    });

  };


  jQuery('#VideoItems').on('click', '.ajaxPaginationLink', function (e) {
    e.preventDefault();
    var g = e.target.href.split('=');
 //   alert(g[1]);
    //if (g[0] == "//#Paged=" || g[0] == "https:/#Paged=") {
    var paged = g[1];
    jQuery('input[name=paged]').val(paged);
    doAjaxSearch(e); // research with new page number
   // }

  });
}); // end doc ready