
if ( !Function.prototype.partial ) {
    Function.prototype.partial = function(){
        var fn = this, args = Array.prototype.slice.call(arguments);
        return function(){
            return fn.apply(
                this,
                args.concat( Array.prototype.slice.call(arguments) )
            );
        };
    };
}

$(function() {

    var filterTimeout = null;
    var lastLineClicked = null;

    /*================ highlighting =====================*/

    function unhighlightAll() {
        $( '.line' ).removeClass( 'highlight' );
    }

    function highlight( num ) {
        var element = line( num );
        if ( element ) {
            $( element ).addClass( 'highlight' );
        }
    }

    /*================ accessing lines =====================*/

    function line( num ) {
        var elements = $( '#line-' +num );
        return elements.length ? elements : false;
    }

    function lineNum( line ) {
        var str = line.attr( 'id' )
                      .substring (5 );
        return parseInt( str, 10 );
    }

    /*=============== line selections =====================*/

    function lineClicked( e ) {
        var link = $( this );
        var line = link.parent();
        var num = lineNum( line );
        ( e.shiftKey ? selectConversation : selectLine )( num );
        return false;
    }

    function selectLine( num ) {
        unhighlightAll();
        highlight( num );
        setHash( num );
        lastLineClicked = num;
    }

    function selectConversation( num ) {
        unhighlightAll();
        var start = lastLineClicked || num;
        var end = num;
        var hash = '#' +Math.min(start,end)+ '-' +Math.max(start,end);
        filterOn( hash );
        setFilter( hash );
    }

    /*================ utilities =====================*/

    function scrollTo( num ) {
        var element = line( num );
        if ( element ) {
            setTimeout(function() {
                var top = element.position().top - 150; 
                document.body.scrollTop = top;
            }, 100 );
        }
    }

    function setHash( num ) {
        var element = $( '#line-' +num );
        element.removeAttr( 'id' );
        window.location.hash = num;
        element.attr( 'id', 'line-' +num );
    }

    /*================ filtering =====================*/

    function messageFilter( text ) {
        var filterText = text.toLowerCase();
        var line = $( this );
        var text = $( '.message', line ).html();
        if ( text.toLowerCase().indexOf(filterText) == -1 ) {
            line.addClass( 'hidden' );
        }
    }

    function conversationFilter( from, to, text ) {
        var line = $( this );
        var num = lineNum( line );
        if ( num < from || num > to ) {
            line.addClass( 'hidden' )
        }
    }

    function filterOn( text ) {
        var filterer = messageFilter;
        var matches = null;
        if ( matches = text.match(/^#(\d+)-(\d+)$/) ) {
            setHash( text );
            filterer = conversationFilter.partial(
                parseInt( matches[1], 10 ),
                parseInt( matches[2], 10 )
            );
        }
        $( '.line' ).removeClass( 'hidden' )
                    .each( filterer.partial(text) );
    }

    function setFilter( text ) {
        $( '#filter' ).val( text );
    }

    function filter() {
        filterOn( $('#filter').val() );
    }

    function filterCheck() {
        if ( filterTimeout != null ) {
            clearTimeout( filterTimeout );
        }
        filterTimeout = setTimeout( filter, 500 );
    }

    /*================ "main" =====================*/

    $( '.line a' ).click( lineClicked );

    if ( window.location.hash ) {
        var hash = window.location.hash.substring( 1 );
        if ( hash.match(/^\d+$/) ) {
            unhighlightAll();
            highlight( hash );
            scrollTo( hash );
        }
        else {
            setFilter( '#' +hash );
            filterOn( '#' +hash );
        }
    }

    $( '#filter' ).keyup( filterCheck );

});

