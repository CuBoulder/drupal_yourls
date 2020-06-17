(function($){
    $(function(){
        // Handle the search feature
        let search = null;
        let params = new URLSearchParams(window.location.search).get('page');
        let page = 1;
        
        function renderTable(url){
            fetch(url)
            .then(res => {
                if(res.status === 200){
                    return res.json();
                }
                else{
                    throw new Error('Server error');
                }
            })
            .then(res => {
                $('#url-results').empty(); //clear the table
                // re-draw it
                for (i in res.links){
                    $('#url-results').append(
                        `<tr>
                        <th scope="row"> <a href= ${res.links[i].shorturl} target="_blank"> ${res.links[i].shorturl} </a></th>
                        <td>${res.links[i].url}</td>
                        <td>${res.links[i].clicks}</td>
                        </tr>`
                    );
                }
            })
            .catch(err => {
                console.error(err);
                $('#url-results').empty(); //clear the table
                $('#url-results').append("<p> No Results </p>");
            });
        }
        
        document.getElementById('next-button').addEventListener('click', event => {
            page++;
            let maxPages = Math.ceil(+event.target.value / 10);
            if(page > maxPages) page = maxPages; //value on the button is the total number of short URLs
            renderTable(`/get-all-short-urls?page=${page}`);
        });
        document.getElementById('prev-button').addEventListener('click', event => {
            page--;
            if(page < 1) page = 1;
            renderTable(`/get-all-short-urls?page=${page}`);
        });
        
        // handle the search form
        document.getElementById('search-keyword').addEventListener('blur', e => {search = e.target.value});
        document.getElementById('search-button').addEventListener('click', event => {
            if(!search){
                alert("Please enter a keyword to search for");
                return;
            }
            renderTable(`/get-all-short-urls?keyword=${search}`);
        });
    });
})(jQuery);