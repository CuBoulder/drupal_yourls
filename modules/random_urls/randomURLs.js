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
                if(res.message.status === 'success' || res.message.code === 'error:url'){
                    // status will fail if user asks for existing url - so return the existing shorturl
                    m.className = "alert alert-success";
                    m.innerHTML = `Your short url is: ${res.message.shorturl}`;
                }
                else{
                    m.className = "alert alert-danger";
                    m.innerHTML = `Your short url is: ${res.message}`;
                }
            })
            .catch(err => {
                console.error(err);
                m.classname = "alert alert-danger";
                m.innerText = "Something went wrong. Please try again."; 
            });
        }
    });
}());