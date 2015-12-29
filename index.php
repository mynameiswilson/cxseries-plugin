<?php
/*
Plugin Name: CXSeries
Plugin URI: http://velolouisville.com
Description: A plugin for the CXSeries theme, which provides Races as posts, upcoming races, and a number of other features.
Version: 201512
Author: Ben Wilson
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


function display_cxseries_race_details($title=true) {
		global $post;

            $meta_keys = array("dates","location",'website','facebook','twitter','registration');

            $meta = array();

            $custom_fields = get_post_custom(); 

            $has_registration_link = false;

            foreach ($meta_keys as $key):

                if (isset($custom_fields[$key]) && is_array($custom_fields[$key])):

                    $meta[$key] = $custom_fields[$key][0];

                endif;

            endforeach; 

?>
<?php if ($title) : ?>
		<?php $post_link = "/".$post->post_type."/".$post->post_name; ?>
        <span class="cxseries_name"><a href="<?php echo $post_link ?>"><?php the_title(); ?></a></span><br/>
<?php endif; ?>
                <span class="cxseries_date"><?php echo ( !isset($custom_fields['dates']) ) ?  get_the_date() : $custom_fields['dates'][0]; ?></span> &bull;

                <?php foreach($meta as $key=>$value): 

                    switch ($key):

 	                   case "location":

                        ?>                  <span class="cxseries_location"><?php echo $value ?></span><br/><?php

                            break;

                        case "website":

        ?>

            <a class="cxseries_link" href="<?php echo $value; ?>"><img src="<?php bloginfo('template_url')?>/img/link-20.png" alt="website" /></a>

        <?  

                            break;

                        case "facebook":

        ?>

            <a class="cxseries_link" href="<?php echo $value; ?>"><img src="<?php bloginfo('template_url')?>/img/facebook-20.png" alt="facebook" /></a>

        <?php

                            break;

                        case "twitter": ?>

                            <a class="cxseries_link" href="http://twitter.com/<?php echo $value ?>"><img src="<?php bloginfo('template_url')?>/img/twitter-20.png" alt="twitter" /></a> 

        <?php

                            break;

                        case "registration": 
                          if (!empty($value)):
                        	 $has_registration_link = true;
        ?>

                            <a class="btn btn-mini btn-success cxseries_link" href="<?php echo $value ?>">REGISTRATION</a>



        <?php
                          endif;
                            break;

                    endswitch;
        endforeach; ?>
        <?php
        		if (!$has_registration_link): ?>
                            <a class="btn btn-mini btn-success cxseries_link" href="<?php echo $post_link ?>">REGISTRATION</a>
        <?php 		endif; 
          $has_registration_link = false;
        ?>
                <br/><br/>
        <?php
}


/*
 * WIDGET: Upcoming Races
 *
 */
class cxseries_upcoming_races extends WP_Widget {     

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


				if ($number_of_posts == 1):

			            $qargs = "post_type=races&order=ASC&orderby=date&post_status=future&posts_per_page=".$number_of_posts;

				else:

			            $qargs = "post_type=races&order=ASC&orderby=date&post_status=any&posts_per_page=".$number_of_posts;

				endif;


        $the_query = new WP_Query($qargs);

        if ( $the_query->have_posts() ):

            echo "<ul>";

        // The Loop

        $post_count = 0;

	        while ( $the_query->have_posts() && $post_count < $number_of_posts ) : $the_query->the_post();

	        ?>

	            <li>
								<?php 
								    display_cxseries_race_details();
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

add_action( 'widgets_init', create_function( '', 'register_widget( "cxseries_upcoming_races" );' ) );

/*
 * SHORTCODE: upcoming_races
 */

add_shortcode( 'upcoming_races', 'cxseries_upcoming_races_shortcode' );
function cxseries_upcoming_races_shortcode( $atts ) {

	// Configure defaults and extract the attributes into variables
	extract( shortcode_atts( 
		array( 
			'class'   => '',
			'races' => 1
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
	cxseries_upcoming_races::widget( $args,$instance ); 
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

  display_cxseries_race_details($title=false);

}

add_shortcode('race_details', 'cxseries_race_details_shortcode');


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



?>