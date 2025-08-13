<?php
/**
 * Plugin Name:       Core Quiz Builder
 * Description:       A modern personality quiz builder for WordPress, built with React.
 * Version:           1.0.0
 * Author:            (Your Name Here)
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       core-quiz-builder
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the Custom Post Type for "Quiz".
 */
function core_quiz_register_post_type() {
    $labels = array(
        'name'                  => _x( 'Quizzes', 'Post type general name', 'core-quiz-builder' ),
        'singular_name'         => _x( 'Quiz', 'Post type singular name', 'core-quiz-builder' ),
        'menu_name'             => _x( 'Core Quizzes', 'Admin Menu text', 'core-quiz-builder' ),
        'name_admin_bar'        => _x( 'Quiz', 'Add New on Toolbar', 'core-quiz-builder' ),
        'add_new_item'          => __( 'Add New Quiz', 'core-quiz-builder' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'quiz' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-forms',
        'supports'           => array( 'title', 'thumbnail' ), // 'editor' is removed to disable the Block Editor.
        'show_in_rest'       => true,
    );

    register_post_type( 'core_quiz', $args );
}
add_action( 'init', 'core_quiz_register_post_type' );


/**
 * Creates the custom database tables required by the plugin upon activation.
 */
function core_quiz_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $results_table = $wpdb->prefix . 'core_quiz_results';
    $sql_results = "CREATE TABLE $results_table ( id mediumint(9) NOT NULL AUTO_INCREMENT, quiz_id bigint(20) UNSIGNED NOT NULL, title varchar(255) NOT NULL, description text NOT NULL, image_url varchar(255) DEFAULT '' NOT NULL, PRIMARY KEY  (id), KEY quiz_id (quiz_id) ) $charset_collate;";
    dbDelta( $sql_results );

    $questions_table = $wpdb->prefix . 'core_quiz_questions';
    $sql_questions = "CREATE TABLE $questions_table ( id mediumint(9) NOT NULL AUTO_INCREMENT, quiz_id bigint(20) UNSIGNED NOT NULL, question_text text NOT NULL, display_order mediumint(9) DEFAULT 0 NOT NULL, PRIMARY KEY  (id), KEY quiz_id (quiz_id) ) $charset_collate;";
    dbDelta( $sql_questions );

    $answers_table = $wpdb->prefix . 'core_quiz_answers';
    $sql_answers = "CREATE TABLE $answers_table ( id mediumint(9) NOT NULL AUTO_INCREMENT, question_id mediumint(9) NOT NULL, answer_text varchar(255) NOT NULL, maps_to_result_id mediumint(9) NOT NULL, PRIMARY KEY  (id), KEY question_id (question_id), KEY maps_to_result_id (maps_to_result_id) ) $charset_collate;";
    dbDelta( $sql_answers );
}
register_activation_hook( __FILE__, 'core_quiz_create_database_tables' );


/**
 * Renders the root div for our React app.
 */
function core_quiz_render_quiz_builder_app( $post ) {
    if ( 'core_quiz' === $post->post_type ) {
        echo '<div id="root" class="wp-core-quiz-builder"></div>';
    }
}
add_action( 'edit_form_after_title', 'core_quiz_render_quiz_builder_app' );


/**
 * Enqueues the compiled React app and its assets.
 */
function core_quiz_enqueue_module_assets( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) { return; }
    if ( 'core_quiz' !== get_post_type() ) { return; }

    $manifest_path = plugin_dir_path( __FILE__ ) . 'dist/.vite/manifest.json';
    if ( ! file_exists( $manifest_path ) ) { return; }

    $manifest = json_decode( file_get_contents( $manifest_path ), true );
    if ( ! $manifest ) { return; }
    
    $entry_script_key = 'src/main.jsx';
    if ( ! isset( $manifest[ $entry_script_key ] ) ) { return; }

    // Use regular wp_enqueue_script instead of wp_enqueue_script_module
    $script_handle = 'core-quiz-builder';
    
    // Enqueue WordPress dependencies first
    wp_enqueue_script( 'react' );
    wp_enqueue_script( 'react-dom' );
    wp_enqueue_script( 'wp-element' );
    wp_enqueue_script( 'wp-api-fetch' );
    
    // Then enqueue our script
    wp_enqueue_script(
        $script_handle,
        plugin_dir_url( __FILE__ ) . 'dist/' . $manifest[ $entry_script_key ]['file'],
        array( 'react', 'react-dom', 'wp-element', 'wp-api-fetch' ), // Dependencies
        '1.0.0',
        true // Load in footer
    );

    // Enqueue the corresponding CSS file
    if ( ! empty( $manifest[ $entry_script_key ]['css'] ) ) {
        foreach ( $manifest[ $entry_script_key ]['css'] as $css_file ) {
            wp_enqueue_style(
                $script_handle . '-style',
                plugin_dir_url( __FILE__ ) . 'dist/' . $css_file
            );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'core_quiz_enqueue_module_assets' );


/**
 * Register custom REST API routes for the quiz builder.
 */
function core_quiz_register_rest_routes() {
    register_rest_route( 'core-quiz/v1', '/quiz/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'core_quiz_get_quiz_data',
        'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
    ) );
}
add_action( 'rest_api_init', 'core_quiz_register_rest_routes' );

/**
 * Callback function to fetch all data for a quiz.
 */
function core_quiz_get_quiz_data( $request ) {
    global $wpdb;
    $quiz_id = (int) $request['id'];

    $quiz_post = get_post( $quiz_id );
    if ( ! $quiz_post || 'core_quiz' !== $quiz_post->post_type ) {
        return new WP_Error( 'not_found', 'Quiz not found', array( 'status' => 404 ) );
    }

    $quiz_data = array( 'id' => $quiz_post->ID, 'title' => $quiz_post->post_title );

    $results_table = $wpdb->prefix . 'core_quiz_results';
    $quiz_data['results'] = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $results_table WHERE quiz_id = %d", $quiz_id ) );
    
    $questions_table = $wpdb->prefix . 'core_quiz_questions';
    $quiz_data['questions'] = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $questions_table WHERE quiz_id = %d ORDER BY display_order ASC", $quiz_id ) );

    $answers_table = $wpdb->prefix . 'core_quiz_answers';
    foreach ( $quiz_data['questions'] as $index => $question ) {
        $quiz_data['questions'][$index]->answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $answers_table WHERE question_id = %d", $question->id ) );
    }

    return new WP_REST_Response( $quiz_data, 200 );
}