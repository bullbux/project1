/**
 * Character Counter for inputs and text areas showing characters left.
 */
$(document).ready(function(){
    var attrs = new Array;
    $('.word_count').each(function(){
        //maximum limit of characters allowed.
        var attributes = $(this)[0].className.match(/{(.*)}/gi);
        if(attributes && attributes.length > 0){
            attrs.push( eval('('+attributes+')') );
            if(typeof attrs[$('.word_count').index(this)].charcount != 'undefined'){
                var maxlimit = attrs[$('.word_count').index(this)].charcount;
                // get current number of characters
                var length = $(this).val().length;
                if(length >= maxlimit) {
                            $(this).val($(this).val().substring(0, maxlimit));
                            length = maxlimit;
                    }
                // update count on page load
                $('.' + attrs[$('.word_count').index(this)].counter_class).html( (maxlimit - length) + ' characters left');
                // bind on key up event
                $(this).keyup(function(){ 
                    // get new length of characters
                    var new_length = $(this).val().length;                    
                    if(new_length >= maxlimit) {
                                    $(this).val($(this).val().substring(0, maxlimit));
                                    //update the new length
                                    new_length = maxlimit;
                            }
                    // update count
                    $('.' + attrs[$('.word_count').index(this)].counter_class).html( (maxlimit - new_length) + ' characters left');
                });
            }
        }
    });
});