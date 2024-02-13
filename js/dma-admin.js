jQuery(document).ready(function($) {
  $('#start-assignment').click(function() {
    var $button = $(this); // Cache the button
    var $status = $('#dma-assignment-status'); // Cache the status div

    // Disable the button and update the status message
    $button.prop('disabled', true);
    $status.html('Initiating process...');

    $status.html('Processing... Do not refresh or close your browser...');

    $.ajax({
      url: dmaAjax.ajax_url,
      type: 'POST',
      data: {
        action: 'dma_start_assignment'
      },
      success: function(response) {
        if (response.success) {
          // Update the UI with the message received from the server
          $status.html(response.data);
        } else {
          // Handle failure
          $status.html('Failed to start the process.');
        }

        // Re-enable the button after processing
        $button.prop('disabled', false);
      },
      error: function(errorThrown) {
        console.log('Error:', errorThrown);
        $status.html('Error during assignment processing: ' + errorThrown);

        // Re-enable the button after error
        $button.prop('disabled', false);
      }
    });
  });
});
