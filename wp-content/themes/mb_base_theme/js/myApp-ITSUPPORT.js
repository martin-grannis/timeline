jQuery(document).ready(function () {

  jQuery(document).foundation();

  // turn all select into select2
  //jQuery('select').select2();

  // //More (Expand) or Less (Collapse)
  // $('.categories-menu.menu.nested').each(function(){
  //     var filterAmount = $(this).find('li').length;
  //     if( filterAmount > 5){    
  //       $('li', this).eq(4).nextAll().hide().addClass('toggleable');
  //       $(this).append('<li class="more">More</li>');    
  //     }  
  //   });

  //   $('.categories-menu.menu.nested').on('click','.more', function(){
  //     if( $(this).hasClass('less') ){    
  //       $(this).text('More').removeClass('less');    
  //     }else{
  //       $(this).text('Less').addClass('less'); 
  //     }
  //     $(this).siblings('li.toggleable').slideToggle(); 
  //   }); 


  // toggles
  jQuery('#search-filter').on('click', '.toggled', function (e) {
    e.preventDefault;
    jQuery(e.target).toggleClass('toggledClosed');
    jQuery(e.target).siblings('ul').toggleClass('isHidden');
  });

  jQuery('.searchList').on('click', 'li', function (e) {
    e.preventDefault;
    var t = jQuery(this);
    jQuery(e.target).toggleClass('toggledClosed');
    t.parent().prev().text(t.text());
    jQuery(e.target).parent().toggleClass('isHidden');
  });



}); // end doc ready