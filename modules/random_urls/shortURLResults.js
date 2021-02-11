(function($, drupalSettings){
    console.log("%cSKO BUFFS!","color: #CFB87C; font-size: 2.75em;");
    $(function(){
        // Handle the search feature
        let search = null; let page = 1;
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
                $('#url-results').empty();
                for (i in res.links){
                    $('#url-results').append(
                        `<tr>
                        <th scope="row"> <a href= ${res.links[i].shorturl} target="_blank"> ${res.links[i].shorturl} </a></th>
                        <td>${res.links[i].url}</td>
                        <td>${res.links[i].clicks}</td>
                        </tr>`
                    );
                }
                if(Object.keys(res.links).length < 10){
                    $('#url-results').append(`<tr> <td></td><td> End of Results </td> <td></td> </tr>`);
                }
            })
            .catch(err => {
                console.error(err);
                $('#url-results').empty(); //clear the table
                $('#url-results').append("<p> No Results </p>");
            });
        }
        
        document.getElementById('next-button').addEventListener('click', function(){
            page++;
            let maxPages = Math.ceil(+this.value / 10);
            if(page >= maxPages){
                page = maxPages; //value on the button is the total number of short URLs
                this.disabled = true;
            }
            document.getElementById('prev-button').disabled = false;
            renderTable(`${drupalSettings.path.baseUrl}/get-all-short-urls?page=${page}`);
        });
        document.getElementById('prev-button').addEventListener('click', function(){
            page--;
            if(page <= 1){
                page = 1;
                this.disabled = true;
            }
            document.getElementById('next-button').disabled = false;
            renderTable(`${drupalSettings.path.baseUrl}/get-all-short-urls?page=${page}`);
        });

        // handle the search form
        document.getElementById('search-keyword').addEventListener('blur', e => {search = e.target.value});
        document.getElementById('search-button').addEventListener('click', event => {
            if(!search){
                alert("Please enter a keyword to search for");
                return;
            }
            renderTable(`${drupalSettings.path.baseUrl}/get-all-short-urls?keyword=${search}`);
        });
    });
})(jQuery, drupalSettings);