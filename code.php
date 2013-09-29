<?php

// filtre permalien
add_filter( 'page_link', 'archive_permalink', 10, 2 );
function archive_permalink( $lien, $id ) {
    if( '' != ( $archive = get_post_meta( $id, '_archive_page', true ) ) && ! is_admin() )
        return get_post_type_archive_link( $archive );
    else
        return $lien;
}

// redirect
add_action( 'template_redirect', 'redirect_to_archive' );
function redirect_to_archive() {
    if( is_page() && ! is_admin() ){
        global $post;
        if( '' != ( $archive = get_post_meta( $post->ID, '_archive_page', true ) ) ) {
            wp_redirect( get_post_type_archive_link( $archive ), 301 );
            exit();
        }
    }
}

add_action( 'admin_init', 'admin_init' );
function admin_init(){
    add_meta_box("desc_page", "Archive à présenter", "archive_page", "page", "side", "high");
}

function save_custom( $post_ID ){ 
    // on retourne rien du tout s'il s'agit d'une sauvegarde automatique
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post_id;
        
    if ( isset( $_POST[ 'archive_page' ] ) ) {
        check_admin_referer( 'archive_page-save_' . $_POST[ 'post_ID' ], 'archive_page-nonce' );

        if( isset( $_POST[ 'archive_page' ] ) ) {
            $target = $_POST[ 'archive_page' ];
            global $wpdb;
            $suppr = $wpdb->get_results( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archive_page' AND meta_value = '$target'" );
            foreach( $suppr as $s )
                delete_post_meta( $s->post_id, '_archive_page' );
            update_post_meta( $_POST[ 'post_ID' ], '_archive_page', $_POST[ 'archive_page' ] );
        }
    }
}

function archive_page( $post ) {
    $archive_page = get_post_meta( $post->ID,'_archive_page', true);
    ?>
    <select name="archive_page" id="">
        <option value="">Aucune</option>
        <?php
            $ar = get_post_types(array('show_ui'=>true));
            foreach( $ar as $a ) :
        ?>
        <option value="<?php echo $a; ?>" <?php selected($a,$archive_page); ?>><?php echo $a; ?></option> 
        <?php endforeach; ?>
    </select>
    <p>Choisissez la cible de cette page</p>
    
    <?php
    wp_nonce_field( 'archive_page-save_' . $post->ID, 'archive_page-nonce') ;

}

//présentation de l'archive
function presentation_archive() {
    $post_type_obj = get_queried_object();
    $target = $post_type_obj->name;
    global $wpdb;
    $presentation = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archive_page' AND meta_value = '$target'" );
    if( ! is_null( $presentation)  ) {
        $presentation = new WP_Query( array(
            'page_id' => $presentation
            ) );
        if( $presentation->have_posts() ) : $presentation->the_post();
            the_title( '<h1 class="h1">', '</h1>' );
            echo '<div class="article-elem">';
            echo the_content();
            echo '</div>';
        endif;
    }
}

//filtre classes nav menu
add_filter( 'nav_menu_css_class', 'add_my_archive_menu_classes', 10 , 3 );
function add_my_archive_menu_classes( $classes , $item, $args ) {
    if( '' != ( $archive = get_post_meta( $item->object_id, '_archive_page', true ) ) ) {
        if( is_post_type_archive( $archive ) )
            $classes[] = 'current-menu-item';
        if( is_singular( $archive ) )
            $classes[] = 'current-menu-ancestor';
    }
    return $classes;
}
