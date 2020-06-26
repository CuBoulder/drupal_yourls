(function($){
    // Prevent the <a> tags from redirecting from the dropdown button on the view
    function handleAction(e, action, row){
        e.preventDefault();
        console.log(action, row);
        // Get the application status column - this will be updated after the fetch 
        let status = $("td.views-field-field-ucb-url-status");
        // make a request to the link instead of going to it
        fetch(action)
        .then(res => res.json())
        .then(res =>{
            if(res.action === 'deleted'){
                window.location = window.location.href; //reload the page
            }
            // Change the content of the status column
            status[row].innerText = res.action;
            alert(res.message);
        })
        .catch(err => console.err(err));
    }
    
    // Handle the details modal for the view
    function populateModal(node){
        fetch(`/application/details/${node}`)
        .then(res => {
            if(res.status === 200){
                return res.json();
            }
            else{
                throw Error('Non 200 status code ... rip.');
            }
        })
        .then(res => {
            return {...res, reason: res.reason['0']['#text']};
        })
        .then(details => {
            $('.modal-body').html(() => `
                <p><strong> Author: </strong>${details.name}</p>
                <p><strong> Site Title: </strong>${details.title}</p>
                <p><strong> Long URL: </strong> <a href=${details.url} target="_blank">${details.url}</a></p>
                <p><strong> Short URL: </strong>${details.keyword}</p>
                <p><strong> Reason: </strong>${details.reason}</p>
            `);
            $('#myModal').modal('show');
        })
        .catch(err => {
            console.error(err);
        });
    }

    $(function(){
        // Add empty modal
        $('.short-url-applications-view').append(`
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"> Application Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body"></div>
            </div>
          </div>
        </div>`);
        
        $('.url-app-details').each( function(){
            let node = $(this).attr('value');
            $(this).click(() => populateModal(node)); // add click handler to the 'details' column of the table
        });
        
        // add click event to the dropbutton actions
        $('li.dropbutton-action').each( function(index, value){
            let row = Math.floor(index / 3); // find the row of the table that the action was performed on.
            let link = $(this).children('a')[0];
            link.addEventListener("click", e => handleAction(e, $(this).children('a')[0].href, row ));
        });
    });
}(jQuery));