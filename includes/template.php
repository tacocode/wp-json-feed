<div id="wp_json_feed_posts">
    <p class="indicator"><i class="fas fa-sync-alt fa-spin"></i> Loading...</p>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $.ajax({
        dataType: "json",
        url: wp_json_feed.ajax_url,
        data: {
            action: 'wp_json_feed',
            url: '<?php echo $url ?>',
            replace: '<?php echo $attributes['replace']; ?>'
        },
        success: function (response) {
            $('.indicator').remove();
            if (response.data.json) {
                displayResult(response.data.json);
            } else {
                $('#wp_json_feed_posts').append('Could not load data');
            }
        },
        error: function () {
            $('.indicator').remove();
            $('#wp_json_feed_posts').append('Could not load data');
        }
    });

    function displayResult(data) {
        let limit = parseInt(<?php echo $limit_content; ?>);
        $.each(JSON.parse(data), function(index, item) {
            let content = item.<?php echo $display_content; ?>.rendered;
            let output = (limit > 0) ? content.substring(0, limit) + '...' : content;

            let post = $('<div class="post" id="' + item.id + '"><h3 class="post-title"><a href="' +
                item.link + '">' + item.title.rendered + '</a></h3><div class="post-content">' +
                output + '</div></div>');
            $('#wp_json_feed_posts').append(post);
        });
    }
});
</script>