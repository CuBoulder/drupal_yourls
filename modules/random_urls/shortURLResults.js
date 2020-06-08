(function($){
    $(function(){
        // Handle the search feature
        let search = null;
        let params = new URLSearchParams(window.location.search).get('page');
        document.getElementById('next-button').addEventListener('click', event=> {
            let next = params || event.target.value;
            next = +next + 1;
            window.location = window.location.origin + window.location.pathname + '?page=' + next ;
        });
        document.getElementById('prev-button').addEventListener('click', event=> {
            let next = params || event.target.value;
            next = +next - 1;
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