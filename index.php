<?php
/*
Plugin Name:Advance category field
Description:Custom plugin to add category product field
*/

// Add term page
// Adding Ajax Search for POSTS - wp_ajax_{action}
add_action( 'wp_ajax_getpostsearch', 'ex_get_posts_ajax_callback' );
function ex_get_posts_ajax_callback(){
    $return = array(); // we will pass post IDs and titles to this array
    // You can use WP_Query, query_posts() or get_posts() here - it doesn't matter
    
	$curent_cat = get_queried_object();
	$curent_cat_id = $curent_cat->term_id;
	$category_pro_id = $_GET['category_pro'];
	$search_results = new WP_Query( array(
        'post_type' => 'product',//post type
        'post_status' => 'publish', // if you don't want drafts to be returned
        'posts_per_page' => -1,
		's'=>$_GET['q'],
		'tax_query'=> array(
        array(
            'taxonomy'=> 'product_cat',
            'field' => 'term_id', //This is optional, as it defaults to 'term_id'
            'terms'=> $category_pro_id,
        ),
		)
    ) );

	
    if( $search_results->have_posts() ) :
        while( $search_results->have_posts() ) : $search_results->the_post();
            // shorten the title a little
            $title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
            $return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
        endwhile;
    endif;
    wp_send_json( $return );
}

// {$taxonomy}_add_form_fields

// HTML output for edit field
add_action('product_cat_add_form_fields', 'ex_product_cat_feature_posts', 10, 1);
add_action('product_cat_edit_form_fields', 'ex_product_cat_feature_posts', 10, 1);
function ex_product_cat_feature_posts( $taxonomy ) {
    // Nonce field to validate form request came from current site
    wp_nonce_field( basename( __FILE__ ), '_feature_post_nonce' );

    $html  = '<tr class="form-field">
    <th scope="row" valign="top"><label for="catshort_button_type">Search specific product:</label></th>
    <td>
        <select id="ex_product_cat_feature_post" name="ex_product_cat_feature_post[]" multiple="multiple" style="width:99%;max-width:25em;">';

    $term_id = isset($_GET['tag_ID']) ? $_GET['tag_ID'] : '';

    if( $feature_post_ids = get_term_meta( $term_id, 'ex_product_cat_feature_post', true ) ) {
        foreach( $feature_post_ids as $post_id ) {
            $title = get_the_title( $post_id );
            // if the post title is too long, truncate it and add "..." at the end
         $title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
            $html .=  '<option value="' . $post_id . '" selected="selected">' . $title . '</option>';
        }
    }
    $html .= '</select></td></tr>';

    // CSS Output
    ?><style type="text/css">iframe#description_ifr {min-height:220px !important;}</style><?php
    // HTML Output
    echo $html;

    // jQuery Output
    ?><script>
    // multiple select with AJAX search
    jQuery(function($) {
		var urlParams = new URLSearchParams(window.location.search);
		var tag_ID = urlParams.get('tag_ID');
        $('#ex_product_cat_feature_post').select2({
			
            ajax: {
                url: ajaxurl, // AJAX URL is predefined in WordPress admin
                dataType: 'json',
                delay: 250, // delay in ms while typing when to perform a AJAX search
                data: function (params) {
                    return {
                        q: params.term, // search query
                        action: 'getpostsearch', // AJAX action for admin-ajax.php
					    category_pro:tag_ID,  
					};
                },
                processResults: function( data ) {
                    var options = [];
                    if ( data ) {
                        // data is the array of arrays, and each of them contains ID and the Label of the option
                        $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                            options.push( { id: text[0], text: text[1]  } );
                        });
                    }
                    return {
                        results: options
                    };
                },
            },
            minimumInputLength: 3 // the minimum of symbols to input before perform a search
        });
		jQuery(document).on("click",'.select2-selection__choice__remove',function(){
			var selection_choice = jQuery('.select2-selection__choice').length;
		   if(selection_choice == 0){
			   jQuery('#ex_product_cat_feature_post option').remove();
		   }
		   
		   
		   
		});
		
    });
    </script>
    <?php
}

// edited_{$taxonomy}

// Save extra taxonomy fields callback function.
add_action( 'edited_product_cat', 'ex_product_cat_feature_posts_save', 10, 2 );
add_action( 'create_product_cat', 'ex_product_cat_feature_posts_save', 10, 2 );
function ex_product_cat_feature_posts_save( $term_id, $term_taxonomy_id ) {
if(empty($_POST['ex_product_cat_feature_post'])){
	update_term_meta($term_id, 'ex_product_cat_feature_post',"");
}
else{
if(isset($_POST['ex_product_cat_feature_post'])){
		update_term_meta($term_id, 'ex_product_cat_feature_post', $_POST['ex_product_cat_feature_post']);
		
}       
}
}

   
?>