jQuery(document).ready(function($) {
  $('#start-assignment').click(function() {
    console.log('Assignment started...');
    $.ajax({
      url: dmaAjax.ajax_url,
      type: 'POST',
      data: {
        action: 'dma_start_assignment'
      },
      success: function(response) {
        console.log('Assignment processing complete.');
        // Optionally, update the UI to reflect completion.
        // dma-assignment-status
        $('#dma-assignment-status').html('Assignment processing complete.');
      },
      error: function(errorThrown) {
        console.log('Error during assignment processing:', errorThrown);
        // Optionally, update the UI to reflect the error.
        // dma-assignment-status
        $('#dma-assignment-status').html('Error during assignment processing: ' + errorThrown);

      }
    });
  });
});
