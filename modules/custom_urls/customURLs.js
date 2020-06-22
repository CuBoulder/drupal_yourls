(function(){
    let data = {
        url: null,
        custom: null,
        identikey: null,
        title: null || 'Unknown Title',
        desc: null
    };
    let inputs = document.querySelectorAll('input');
    inputs.forEach(i => {
        i.addEventListener('blur', function(e){
            let name = e.target.name;
            data[name] = e.target.value;
        });
        if(i.name === 'identikey'){
            data.identikey = i.value;
        }
    });
    document.getElementById('url-desc').addEventListener('blur', e => data[e.target.name] = e.target.value );
    function format(custom){
        let hasSpace = /\s/g; //return false if no match
        let hasSlash = /\//g;
        if(!hasSpace.test(custom) && !hasSlash.test(custom)){
            return true;
        }
        else{
            return false;
        }
    }
    document.getElementById('custom-url-form').addEventListener('submit', function(e){
        e.preventDefault();
        if(!data.custom || !data.url || !data.desc){
            alert("Please fill out all of the fields");
            return;
        }
        if(!format(data.custom)){
            alert("Make sure the Short URL is one word");
            return;
        }
        // if(! /[a-z]{4}[0-9]{4}/g.test(data.identikey)){
        //     alert("Please enter your CU Identikey.");
        //     return;
        // }
        fetch('/add-url-request', {method: 'POST', body: JSON.stringify(data)})
        .then(res => {
            if(res.status === 200){
                return res.json();
            }
            else{
                throw Error('Response returned non 200 status code');
            }
        })
        .then(res => {
            let message = document.getElementById('message');
            if(res.app_status === true){
                message.className = "alert alert-success";
                message.innerHTML = "Added a new Application. An email will be sent to your colorado.edu account with the status of your application once its been reviewed.";
            }
            else{
                message.className = "alert alert-danger";
                message.innerHTML = res.message;
            }
        })
        .catch(err => console.error(err));
    });
})();