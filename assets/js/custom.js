jQuery(document).ready(function($) {
    $('#import-posts-button').on('click', function(e) {
        e.preventDefault();

        $.ajax({
            url: localized_data.ajaxurl,
            type: 'POST',
            data: {
                action: 'custom_import_posts_from_xml'
            },
            success: function(response) {
                if (response.success) {
                    console.log('Posts imported successfully:', response.data.posts);
                    alert('Posts imported successfully!');
                } else {
                    console.error('Error importing posts:', response.data.error);
                    alert('Error importing posts: ' + response.data.error);
                }
            },
            error: function(error) {
                console.error('AJAX error:', error);
                alert('AJAX error occurred');
            }
        });
    });
});