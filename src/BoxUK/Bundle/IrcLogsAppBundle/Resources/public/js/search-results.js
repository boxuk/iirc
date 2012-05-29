$(document).ready(function() {
    
    var over = function() {
        $(this).popover('show');
        
    };
    
    var out = function () {
        $(this).popover('hide');
    };
    
    $('td.message').hover(over, out);
    
});