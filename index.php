<?php
/*
Plugin Name: CXSeries
Plugin URI: http://velolouisville.com
Description: A plugin for the CXSeries theme, which provides Races as posts, upcoming races, and a number of other features.
Version: 201512
Author: Ben Wilson
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );



function cxseries_fetch_races($number_of_races = 1) {
		if ($number_of_races == 1):
	            $qargs = "post_type=races&order=ASC&orderby=date&post_status=future&posts_per_page=".$number_of_races;
		else:
	            $qargs = "post_type=races&order=ASC&orderby=date&post_status=any&posts_per_page=".$number_of_races;
		endif;

    return new WP_Query($qargs);
}

function cxseries_display_next_race() {
  wp_reset_postdata();
  $the_query = cxseries_fetch_races(1);
  if ( $the_query->have_posts() ):
  	$post_count = 0;
  	$number_of_posts = 1;
		while ( $the_query->have_posts() && $post_count < $number_of_posts ) : $the_query->the_post();
            echo '<div class="cxseries_next_race">';
			cxseries_display_race_details();
            echo '</div>';
			$post_count++;
		endwhile;
	else:
    echo "<p>No upcoming races. See ya next season!</p>";
	endif;
  wp_reset_postdata();
}


function cxseries_display_race_details($title=true) {
	global $post;

    $meta_keys = array("dates","location",'website','facebook','twitter','registration');

    $meta = array();

    $location = get_post_meta( $post->ID, 'cxseries_race_location', true );

    $sponsoredby = get_post_meta( $post->ID, 'cxseries_race_sponsoredby', true );

    $registration_link = get_post_meta( $post->ID, 'cxseries_race_registration_link', true );

    $website_link = get_post_meta( $post->ID, 'cxseries_race_website_link', true );

    $facebook_link = get_post_meta( $post->ID, 'cxseries_race_facebook_link', true );

    $twitter_link = get_post_meta( $post->ID, 'cxseries_race_twitter_link', true );

    $has_registration_link = false;


?>
<?php if ($title) : ?>
		<?php $post_link = get_site_url()."/".$post->post_type."/".$post->post_name; ?>
        <span class="cxseries_name"><a href="<?php echo $post_link ?>"><?php the_title(); ?></a></span><br/>
<?php endif; ?>

<?php if ($sponsoredby) : ?>
        <div class="cxseries_race_sponsoredby">
            Sponsored by: <?php echo $sponsoredby ?>
        </div>
<?php endif; ?>

        <div class="cxseries_race_meta">
            <span class="cxseries_date"><?php echo ( !isset($custom_fields['dates']) ) ?  get_the_date() : $custom_fields['dates'][0]; ?></span>

<?php if ($location): ?>
            &bull;  <span class="cxseries_location"><?php echo $location ?></span>
<?php endif; ?>
        </div>

        <div class="cxseries_race_links">

<?php if ($registration_link): 
        $has_registration_link = true;
?>
            <a class="btn btn-mini btn-success cxseries_link" href="<?php echo $registration_link ?>">REGISTRATION</a>
<?php endif; ?>

<?php if ($website_link): ?>
            <a class="" href="<?php echo $website_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/link-20.png" alt="website" /></a>
<?php endif; ?>

<?php if ($facebook_link): ?>
            <a class="" href="<?php echo $facebook_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/facebook-20.png" alt="facebook" /></a>
<?php endif; ?>

<?php if ($twitter_link): ?>
            <a class="" href="<?php echo $twitter_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/twitter-20.png" alt="twitter" /></a> 
<?php endif; ?>

        </div>

<?php
}


/*
 * WIDGET: Upcoming Races
 *
 */
class cxseries_upcoming_races_widget extends WP_Widget {     

    public function __construct() {
        parent::__construct(
            'cxseries_upcoming_races', // Base ID
            'Upcoming Races', // Name
            array( 'description' => __( 'Upcoming Races', 'text_domain' ), 'number_of_posts' => 1 ) // Args
        );
    }

    function form($instance) {

        $cat =  (isset($instance['cat'])) ? $instance['cat'] : "";
?>

        <p><label for="<?php echo $this->get_field_name('widget_title'); ?>">Widget Title</label>

            <input type="text" name="<?php echo $this->get_field_name('widget_title') ?>" value="<?php echo esc_attr($instance['widget_title']) ?>" />

        </p>

        <p><label># of posts <em>(leave empty for ALL)</em></label>

        <input type="text" name="<?php echo $this->get_field_name('number_of_posts')?>" value="<?php echo esc_attr($instance['number_of_posts']) ?>" />

        <input type="hidden" id="cxseries_upcoming_posts_submit" name="cxseries_upcoming_posts_submit" value="1" />

<?php
    }

    function update($new,$old) {

        $instance = array();
        $instance['widget_title'] = strip_tags($new['widget_title']);
        $instance['number_of_posts'] = strip_tags($new['number_of_posts']);
        return $instance;
    }

    

    function widget($args, $instance) {

    	wp_reset_postdata();
        $number_of_posts = (!empty($instance['number_of_posts'])) ? $instance['number_of_posts'] : 999;

        echo '<div class="cxseries_upcoming_races">';

        if (isset($instance['widget_title']) && !empty($instance['widget_title'])):
            echo "<h2>".$instance['widget_title'].'</h2>';
        endif;

        $the_query = cxseries_fetch_races($number_of_posts);

        if ( $the_query->have_posts() ):

          echo "<ul>";

        	// The Loop

        	$post_count = 0;

        	while ( $the_query->have_posts() && $post_count < $number_of_posts ) : $the_query->the_post();

        ?>

	          <li>
							<?php 
							    cxseries_display_race_details();
							?>
	          </li>

				<?php

        $post_count++;

        	endwhile;

          echo "</ul>";

        else:

            echo "<p>No upcoming races. See ya next season!</p>";

        endif;

        // Reset Post Data

            wp_reset_postdata();

            echo '</div>';

    }

}

add_action( 'widgets_init', create_function( '', 'register_widget( "cxseries_upcoming_races_widget" );' ) );

/*
 * SHORTCODE: next_race
 */

add_shortcode( 'next_race', 'cxseries_next_race_shortcode' );
function cxseries_next_race_shortcode( $atts ) {

	// Configure defaults and extract the attributes into variables
	extract( shortcode_atts( 
		array( 
			'class'   => '',
		), 
		$atts 
	));

	ob_start();
    $the_query = cxseries_display_next_race();
	$output = ob_get_clean();
	return $output;

}


/*
 * SHORTCODE: upcoming_races
 */

add_shortcode( 'upcoming_races', 'cxseries_upcoming_races_shortcode' );
function cxseries_upcoming_races_shortcode( $atts ) {

	// Configure defaults and extract the attributes into variables
	extract( shortcode_atts( 
		array( 
			'class'   => '',
			'races' => 999
		), 
		$atts 
	));

	$args = array(
/*		'before_widget' => '<div class="box widget scheme-' . $scheme . ' ">',
		'after_widget'  => '</div>',
		'before_title'  => '<div class="widget-title">',
		'after_title'   => '</div>',
	*/	
	);

	$instance = array(
		'number_of_posts' => $races
		);

	ob_start();
	cxseries_upcoming_races_widget::widget( $args,$instance ); 
	$output = ob_get_clean();
	return $output;

}


/*
 * SHORTCODE: race_details 
 */


function cxseries_race_details_shortcode( $attributes ) {
  extract( shortcode_atts( array(
    'class' => ''
  ), $attributes ) );

  cxseries_display_race_details($title=false);

}

add_shortcode('race_details', 'cxseries_race_details_shortcode');


/*
 * Get Results by Category:Results and Tag:_YEAR_
 */


function cxseries_fetch_results($tag="2015") {
    $number_of_races = 999;
    $qargs = "post_type=attachment&order=DESC&orderby=date&tag=".$tag."&category_name=results&post_status=any&posts_per_page=".$number_of_races;
    return new WP_Query($qargs);
}


function cxseries_list_results() {
    global $post;

    $tags = get_tags(array("get"=>"all","orderby"=>"name","order"=>"desc"));
    if ($tags):
        foreach ($tags as $tag):

            if (preg_match('/[0-9]{4}/',$tag->name)):

                wp_reset_postdata();

                $the_query = cxseries_fetch_results($tag->name);

                if ( $the_query->have_posts() ):
                    echo "<h3>".$tag->name."</h3>";
                    echo "<ul>";
                    // The Loop
                    $post_count = 0;
                    while ( $the_query->have_posts() ) : $the_query->the_post();
                    ?>
                        <li><?php the_attachment_link(); ?></li>
                    <?php
                    $post_count++;

                    endwhile;
                    echo "</ul>";
                else:
                    echo "<p>No results posted!</p>";
                endif;

                wp_reset_postdata();

            endif;
        endforeach;
    endif;


// TODO
// get list of tags that match year format
// cycle through each tag and query individually


    
    wp_reset_postdata();


}

/*
 * CUSTOM POST TYPE: RACES 
 */

// Our custom post type function
function create_races_posttype() {

	register_post_type( 'races',
	// CPT Options
		array(
			'labels' => array(
				'name' => __( 'Races' ),
				'singular_name' => __( 'Race' )
			),
      'supports' => array('title','editor','custom-fields'),
			'public' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => 'races'),
		)
	);
}
// Hooking up our function to theme setup
add_action( 'init', 'create_races_posttype' );

$cxseries_race_labels = array(
    "Location" => "location",
    "Sponsored By" => "sponsoredby");

$cxseries_race_links = array(
            "Registration Link"=>"registration_link",
            "Website"=>"website_link",
            "Facebook"=>"facebook_link",
            "Twitter"=>"twitter_link",
        );


add_action('add_meta_boxes','cxseries_race_metabox_setup');
function cxseries_race_metabox_setup() {
    global $cxseries_race_labels, $cxseries_race_links;

    foreach ($cxseries_race_labels as $label_name=>$label_id):
        add_meta_box('cxseries_race_'.$label_id,$label_name,'cxseries_race_labels_metaboxes','races','normal','high');
    endforeach;


    foreach ($cxseries_race_links as $link_name=>$link_id):
        add_meta_box('cxseries_race_'.$link_id,$link_name,'cxseries_race_links_metaboxes','races','normal','high', array("link_name"=>$link_name, "link_id"=>$link_id));
    endforeach;

}

/*
 * METABOX: Location
 */


function cxseries_race_labels_metaboxes($post,$label) {
    wp_nonce_field( basename( __FILE__ ), $label['id'].'_nonce' );

    $value = get_post_meta( $post->ID, $label['id'], true );
?>
    <p>
    <label for="<?php echo $label['id'] ?>"><?php echo $label['title']?></label>
    <br />
    <input class="widefat" type="text" name="<?php echo $label['id'] ?>" id="<?php echo $label['id'] ?>" value="<?php echo esc_attr( $value ); ?>" size="30" />
  </p>
<?php
}


/* Save the meta box's post metadata. */
function cxseries_save_label_metaboxes( $post_id, $post ) {
    global $cxseries_race_labels;

    foreach ($cxseries_race_labels as $label_name=>$label_id):

        /* Verify the nonce before proceeding. */
        if ( !isset( $_POST['cxseries_race_'.$label_id.'_nonce'] ) || !wp_verify_nonce( $_POST['cxseries_race_'.$label_id.'_nonce'], basename( __FILE__ ) ) )
        return $post_id;

        /* Get the post type object. */
        $post_type = get_post_type_object( $post->post_type );

        /* Check if the current user has permission to edit the post. */
        if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return $post_id;

        /* Get the posted data and sanitize it for use as an HTML class. */
        $new_meta_value = ( isset( $_POST['cxseries_race_'.$label_id] ) ?  $_POST['cxseries_race_'.$label_id] : '' );

        /* Get the meta key. */
        $meta_key = 'cxseries_race_'.$label_id;

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value )
        add_post_meta( $post_id, $meta_key, $new_meta_value, true );

        /* If the new meta value does not match the old value, update it. */
        elseif ( $new_meta_value && $new_meta_value != $meta_value )
        update_post_meta( $post_id, $meta_key, $new_meta_value );

        /* If there is no new meta value but an old value exists, delete it. */
        elseif ( '' == $new_meta_value && $meta_value )
        delete_post_meta( $post_id, $meta_key, $meta_value );

    endforeach;
}
add_action('save_post', 'cxseries_save_label_metaboxes', 10, 2 );


/*
 * METABOXES: Race Links
 */

function cxseries_race_links_metaboxes($post,$link) {
    wp_nonce_field( basename( __FILE__ ), $link['id'].'_nonce' );

    $value = get_post_meta( $post->ID, $link['id'], true );
?>
    <p>
    <label for="<?php echo $link['id'] ?>"><?php echo $link['title']?></label>
    <br />
    <input class="widefat" type="text" name="<?php echo $link['id'] ?>" id="<?php echo $link['id'] ?>" value="<?php echo esc_attr( $value ); ?>" size="30" />
  </p>
<?php
}


/* Save the meta box's post metadata. */
function cxseries_save_link_metaboxes( $post_id, $post ) {
    global $cxseries_race_links;

    foreach ($cxseries_race_links as $link_name=>$link_id):

        /* Verify the nonce before proceeding. */
        if ( !isset( $_POST['cxseries_race_'.$link_id.'_nonce'] ) || !wp_verify_nonce( $_POST['cxseries_race_'.$link_id.'_nonce'], basename( __FILE__ ) ) )
        return $post_id;

        /* Get the post type object. */
        $post_type = get_post_type_object( $post->post_type );

        /* Check if the current user has permission to edit the post. */
        if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return $post_id;

        /* Get the posted data. */
        // TODO validate for valid link
        $new_meta_value = ( isset( $_POST['cxseries_race_'.$link_id] ) ?  $_POST['cxseries_race_'.$link_id] : '' );

        /* Get the meta key. */
        $meta_key = 'cxseries_race_'.$link_id;

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value )
        add_post_meta( $post_id, $meta_key, $new_meta_value, true );

        /* If the new meta value does not match the old value, update it. */
        elseif ( $new_meta_value && $new_meta_value != $meta_value )
        update_post_meta( $post_id, $meta_key, $new_meta_value );

        /* If there is no new meta value but an old value exists, delete it. */
        elseif ( '' == $new_meta_value && $meta_value )
        delete_post_meta( $post_id, $meta_key, $meta_value );

    endforeach;
}
add_action('save_post', 'cxseries_save_link_metaboxes', 10, 2 );




/*
 * FILTER: SHOW FUTURE POSTS  (for upcoming races)
 */ 

// Show Single Future Posts
add_filter('the_posts', 'show_future_posts');
function show_future_posts($posts){
   global $wp_query, $wpdb;
   if(is_single() && $wp_query->post_count ==0){
      $posts = $wpdb->get_results($wp_query->request);
   }
   return $posts;
};


// add categories for attachments 
function add_categories_for_attachments() {     
    register_taxonomy_for_object_type( 'category', 'attachment' ); 
} 
add_action( 'init' , 'add_categories_for_attachments' ); 

// add tags for attachments 
function add_tags_for_attachments() {     
    register_taxonomy_for_object_type( 'post_tag', 'attachment' ); 
} 
add_action( 'init' , 'add_tags_for_attachments' );



?>