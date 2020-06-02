(function($){
    // Prevent the <a> tags from redirecting from the dropdown on the view
    function handleAction(e, action, row){
        e.preventDefault();
        // Get the application status column - this will be updated after the fetch 
        let status = document.querySelectorAll("td.views-field-field-ucb-url-status");
        // make a request to the link instead of going to it
        fetch(action)
        .then(res => res.json())
        .then(res =>{
            if(res.action === 'deleted'){
                window.location = window.location.href; //reload the page
            }
            // Change the content of the status column
            console.log(status[row]);
            status[row].innerText = res.action;
            alert(res.message);
        })
        .catch(err => console.err(err));
    }

    $(function(){
        let list = document.querySelectorAll('li.dropbutton-action'); //nodelist of <li>
        let links  = Array.from(list);
        links.forEach((i, index) => {
            let row = Math.floor(index / 3); // find the index of the status - 3 comes from the 3 actions
            // attach a click event to all of the <a> tags
            // console.log(`Row : ${row}`);
            i.children[0].addEventListener('click', e => handleAction(e, i.children[0].href, row ));
        });
    });
}(jQuery));