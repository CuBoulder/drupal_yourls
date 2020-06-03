(function($){
    $(function(){
        // Handle the search feature
        let search = null;
        document.getElementById('next-button').addEventListener('click', event=> {
            let next = +event.target.value + 1;
            window.location = window.location.origin + window.location.pathname + '?page=' + next ;
        });
        document.getElementById('prev-button').addEventListener('click', event=> {
            let next = +event.target.value - 1;
            if(next < 1) next = 1;
            window.location = window.location.origin + window.location.pathname + '?page=' + next ;
        });
        // handle the search form
        document.getElementById('search-keyword').addEventListener('blur', e => {search = e.target.value});
        document.getElementById('search-button').addEventListener('click', event => {
            if(!search){
                alert("Please enter a keyword to search for");
                return;
            }
            window.location = window.location.origin + window.location.pathname + '?keyword=' + search;
        });
    });
})(jQuery);