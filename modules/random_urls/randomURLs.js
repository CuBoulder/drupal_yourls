(function(){
    let url = null;
    document.getElementById('new-url').addEventListener('blur', e => url = e.target.value);
    document.getElementById('shorten-button').addEventListener('click', () => {
        console.log(url);
        if(!(/.\.*colorado\.edu/g).test(url)){
            alert("Please make sure the URL is from a colorado.edu domain.");
            return;
        }
        else{
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
                console.table(res);
            })
            .catch(err => console.error(err));
        }
    })
}());