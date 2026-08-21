<?php

defined('ABSPATH') || exit;

class Npcink_Toolbox_Easy_Thumbnail_Switcher {

    public $add_new_str;
    public $change_str;
    public $remove_str;
    public $upload_title;
    public $upload_add;
    public $confirm_str;

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::__construct()
     *
     * The main constructor function
     * @since 1.0
     */
    public function __construct() {

$this->add_new_str = __( '添加', 'npcink-site-toolbox' );
            $this->change_str = __( '修改', 'npcink-site-toolbox' );
            $this->remove_str = __( '移除', 'npcink-site-toolbox' );
            $this->upload_title = __( '上传缩略图', 'npcink-site-toolbox' );
            $this->upload_add = __( '使用选定的', 'npcink-site-toolbox' );
            $this->confirm_str = __( '确定吗？', 'npcink-site-toolbox' );

        add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'thumb_column' ), 10, 2 );
        add_action( 'admin_footer', array( $this, 'add_nonce' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

        add_action( 'wp_ajax_npcink_site_toolbox_thumbnail_update', array( $this, 'update' ) );
        add_action( 'wp_ajax_npcink_site_toolbox_thumbnail_remove', array( $this, 'remove' ) );

        add_image_size( 'npcink-site-toolbox-thumbnail', 75, 75, array( 'center', 'center' ) );

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::add_nonce()
     *
     * Used to add a nonce for security checks
     * @since 1.0
     */
    public function add_nonce() {

        global $pagenow;

        if( $pagenow !== 'edit.php' ) {
            return;
        }

        wp_nonce_field( 'npcink_site_toolbox_thumbnail', 'npcink_site_toolbox_thumbnail_nonce' );

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::scripts()
     *
     * Enqueue scripts
     * @since 1.0
     */
    public function scripts( $pagenow ) {

        if( $pagenow !== 'edit.php' ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style( 'npcink-site-toolbox-thumbnail-switcher', plugins_url( 'css/styles.css', __FILE__ ), array(), NPCINK_SITE_TOOLBOX_VERSION );

        wp_enqueue_script( 'npcink-site-toolbox-thumbnail-switcher', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery', 'media-upload', 'thickbox' ), NPCINK_SITE_TOOLBOX_VERSION, true );

        wp_localize_script( 'npcink-site-toolbox-thumbnail-switcher', 'npcinkSiteToolboxThumbnail', array(
            'upload_title' => $this->upload_title,
            'upload_add' => $this->upload_add,
            'confirm' => $this->confirm_str,
        ) );

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::add_column()
     *
     * @param array $columns
     *
     * The action which is added to the post row actions
     * @since 1.0
     */
    public function add_column( $columns ) {

        $columns['ts-ets-option'] = __( '缩略图', 'npcink-site-toolbox' );
        return $columns;

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::thumb_column()
     *
     * @param string $column
     * @param int $id Post ID
     *
     * The column display
     * @since 1.0
     */
    public function thumb_column( $column, $id ) {

        switch( $column ) {
            case 'ts-ets-option':

                if( has_post_thumbnail() ) {
                    the_post_thumbnail( 'npcink-site-toolbox-thumbnail' );
                    echo '<br>';
                    echo sprintf( '<button type="button" class="button-primary ts-ets-add" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->change_str ) );
                    echo sprintf( ' <button type="button" class="button-secondary ts-ets-remove" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->remove_str ) );
                } else {
                    echo sprintf( '<button type="button" class="button-primary ts-ets-add" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->add_new_str ) );
                }

                break;
        }

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::update()
     *
     * AJAX Callback for updating post thumbnail
     * @since 1.0
     */
    public function update() {

        // Check if all required data are set or not
        if( !isset( $_POST['nonce'] ) || !isset( $_POST['post_id'] ) || !isset( $_POST['thumb_id'] ) ) {
            wp_die();
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is unslashed here, then type-checked and sanitized below.
        $nonce_value = wp_unslash( $_POST['nonce'] );

        if ( ! is_string( $nonce_value ) ) {
            wp_die();
        }

        $nonce = sanitize_text_field( $nonce_value );

        // Verify nonce
        if( !wp_verify_nonce( $nonce, 'npcink_site_toolbox_thumbnail' ) ) {
            wp_die();
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is unslashed here, then type-checked and converted below.
        $post_id = wp_unslash( $_POST['post_id'] );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is unslashed here, then type-checked and converted below.
        $thumbnail_id = wp_unslash( $_POST['thumb_id'] );

        if ( ! is_string( $post_id ) || ! is_string( $thumbnail_id ) ) {
            wp_die();
        }

        $id = absint( $post_id );
        $thumb_id = absint( $thumbnail_id );

        if ($id <= 0 || $thumb_id <= 0) {
            wp_die();
        }

        if ( ! current_user_can( 'edit_post', $id ) ) {
            wp_die();
        }

        set_post_thumbnail( $id, $thumb_id );

        echo wp_get_attachment_image( $thumb_id, 'npcink-site-toolbox-thumbnail' );
        echo '<br>';
        echo sprintf( '<button type="button" class="button-primary ts-ets-add" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->change_str ) );
        echo sprintf( ' <button type="button" class="button-secondary ts-ets-remove" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->remove_str ) );

        wp_die();

    }

    /**
     * Npcink_Toolbox_Easy_Thumbnail_Switcher::remove()
     *
     * AJAX Callback for removing post thumbnail
     * @since 1.0
     */
    public function remove() {

        // Check if all required data are set or not
        if( !isset( $_POST['nonce'] ) || !isset( $_POST['post_id'] ) ) {
            wp_die();
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is unslashed here, then type-checked and sanitized below.
        $nonce_value = wp_unslash( $_POST['nonce'] );

        if ( ! is_string( $nonce_value ) ) {
            wp_die();
        }

        $nonce = sanitize_text_field( $nonce_value );

        // Verify nonce
        if( !wp_verify_nonce( $nonce, 'npcink_site_toolbox_thumbnail' ) ) {
            wp_die();
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Value is unslashed here, then type-checked and converted below.
        $post_id = wp_unslash( $_POST['post_id'] );

        if ( ! is_string( $post_id ) ) {
            wp_die();
        }

        $id = absint( $post_id );

        if ($id <= 0) {
            wp_die();
        }

        if ( ! current_user_can( 'edit_post', $id ) ) {
            wp_die();
        }

        delete_post_thumbnail( $id );

        echo sprintf( '<button type="button" class="button-primary ts-ets-add" data-id="%s">%s</button>', esc_attr( $id ), esc_html( $this->add_new_str ) );

        wp_die();

    }

}

new Npcink_Toolbox_Easy_Thumbnail_Switcher();
