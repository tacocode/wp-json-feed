<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('wp_json_feed');
        do_settings_sections('wp_json_feed');
        submit_button('Save Settings');
        ?>
    </form>
</div>