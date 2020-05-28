(function(){
    let url = null;
    let search = null;
    document.getElementById('new-url').addEventListener('blur', e => url = e.target.value);
    document.getElementById('shorten-button').addEventListener('click', () => {
        console.log(url);
        if(!(/.\.*colorado\.edu/g).test(url)){
            alert("Please make sure the URL is from a colorado.edu domain.");
            return;
        }
        else{
            let m = document.getElementById('message-container');
            fetch(`/get-random-url?url=${url}`)
            .then(res => {
                if(res.status === 200){
                    return res.json();
                }
                else{
                    throw Error(`Status code: ${res.status}`);
                }
            })
            .then(res => {
                m.innerHTML = `Your short url is: ${res.shorturl}`;
            })
            .catch(err => {
                console.error(err);
                m.innerText = "Something went wrong. Please try again."; 
            });
        }
    });
    // Handle the search feature
    document.getElementById('next-button').addEventListener('click', event=> {
        let next = +event.target.value + 1;
        window.location.href = window.location.origin + window.location.pathname + '?page=' + next ;
    });
    document.getElementById('prev-button').addEventListener('click', event=> {
        let next = +event.target.value - 1;
        if(next < 1) next = 1;
        window.location.href = window.location.origin + window.location.pathname + '?page=' + next ;
    });
    // handle the search form
    document.getElementById('search-keyword').addEventListener('blur', e => {search = e.target.value});
    document.getElementById('search-button').addEventListener('click', event => {
        if(!search){
            alert("Please enter a keyword to search for");
            return;
        }
        window.location.href = window.location.origin + window.location.pathname + '?keyword=' + search;
    });
}());