jQuery(document).ready(function($) {
  $('#start-assignment').click(function() {
    $('#dma-assignment-status').html('Initiating process...'); // Initial message

    $.ajax({
      url: dmaAjax.ajax_url,
      type: 'POST',
      data: {
        action: 'dma_start_assignment'
      },
      success: function(response) {
        if (response.success) {
          // Update the UI with the processing message received from the server
          $('#dma-assignment-status').html(response.data);

          // Optionally, if you have a way to check the progress or completion, update the message accordingly
          // This part requires additional implementation on how to handle long-running processes
        } else {
          // Handle failure
          $('#dma-assignment-status').html('Failed to start the process.');
        }
      },
      error: function(errorThrown) {
        console.log('Error:', errorThrown);
        $('#dma-assignment-status').html('Error during assignment processing: ' + errorThrown);
      }
    });
  });
});
