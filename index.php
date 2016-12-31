<?php
/**
 * CXSeries
 *
 * @package   CXSeries
 * @author    Ben Wilson <ben@thelocust.org>
 * @copyright 2016 Ben Wilson
 * @license   GPL-2.0+ http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @wordpress-plugin
 * Plugin Name: CXSeries
 * Plugin URI:  https://cxseries.com
 * Description: Companion plugin for the CXSeries theme, which provides Races as posts, upcoming races, and a number of other features.
 * Version:     201612
 * Author:      Ben Wilson <ben@thelocust.org>
 * Author URI:  https://benwilson.org
 * Text Domain: cxseries
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
namespace CXSeries\CXSeriesPlugin;

// If this file is accessed directly, then abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Custom Post type: Races */
add_action( 'init', __NAMESPACE__ . '\create_races_post_type' );

/* Widget: Upcoming Races */
add_action( 'widgets_init', function() { register_widget( __NAMESPACE__ . '\UpcomingRacesWidget' );
} );

/* Shortcode: Next Race */
add_shortcode( 'next_race', __NAMESPACE__ . '\next_race_shortcode' );

/* Shortcode: Upcoming Races */
add_shortcode( 'upcoming_races', __NAMESPACE__ . '\upcoming_races_shortcode' );

/* Shortcode: Race Details */
add_shortcode( 'race_details', __NAMESPACE__ . '\race_details_shortcode' );

/* Shortcode: BikeReg Registration Form */
add_shortcode( 'bikereg', __NAMESPACE__ . '\bikereg_form_shortcode' );

/* Add metaboxes to Races post type */
add_action( 'add_meta_boxes', __NAMESPACE__ . '\race_metabox_setup' );

/* Saving Race Labels and Links on Race posts */
add_action( 'save_post', __NAMESPACE__ . '\save_label_metaboxes', 10, 2 );
add_action( 'save_post', __NAMESPACE__ . '\save_link_metaboxes', 10, 2 );

/* Show Future Posts */
add_filter( 'the_posts', __NAMESPACE__ . '\show_future_posts' );

/* Results: Allow Attachments to be Categorized */
add_action( 'init', __NAMESPACE__ . '\add_categories_for_attachments' );
add_action( 'init', __NAMESPACE__ . '\add_tags_for_attachments' );

/**
 * Fetch Races
 *
 * Builds WP_Query call to fetch "races" custom post type.
 *
 * @param  int $number_of_races how many races to pull.
 * @return WP_Query results.
 */
function fetch_races( $number_of_races = 1 ) {
	if ( $number_of_races == 1 ) :
		// if only one race, choose next future race.
		$qargs = 'post_type=races&order=ASC&orderby=date&post_status=future&posts_per_page=' . $number_of_races;
	else :
		// if more than one, choose all.
		$qargs = 'post_type=races&order=ASC&orderby=date&post_status=any&posts_per_page=' . $number_of_races;
	endif;

	return new \WP_Query( $qargs );
}

/**
 * Display Next Race
 *
 * Echoes display_race_details of have a next race, if not, show
 *  "no upcoming races" message.
 */
function display_next_race() {
	wp_reset_postdata();
	$the_query = fetch_races( 1 );
	if ( $the_query->have_posts() ) :
		$post_count = 0;
		$number_of_posts = 1;
		while ( $the_query->have_posts() && $post_count < $number_of_posts ) :
			$the_query->the_post();
			echo '<div class="cxseries_next_race">';
			   display_race_details();
			echo '</div>';
			$post_count++;
			endwhile;
	else :
		echo '<p>No upcoming races. See ya next season!</p>';
	endif;
	wp_reset_postdata();
}

/**
 * Display Race Details
 *
 * Displays HTML for Race Details
 *
 * @param bool $title display title of the race.
 */
function display_race_details( $title = true ) {
	global $post;

	$meta_keys = array( 'dates','location','website','facebook','twitter','registration' );

	$meta = array();

	$location = get_post_meta( $post->ID, 'cxseries_race_location', true );

	$sponsoredby = get_post_meta( $post->ID, 'cxseries_race_sponsoredby', true );

	$registration_link = get_post_meta( $post->ID, 'cxseries_race_registration_link', true );

	$website_link = get_post_meta( $post->ID, 'cxseries_race_website_link', true );

	$facebook_link = get_post_meta( $post->ID, 'cxseries_race_facebook_link', true );

	$twitter_link = get_post_meta( $post->ID, 'cxseries_race_twitter_link', true );

	$has_registration_link = false; ?>
	<?php if ( $title ) : ?>
		<?php $post_link = get_site_url() . '/' . $post->post_type . '/' . $post->post_name; ?>
			<span class="cxseries_name"><a href="<?php echo esc_url( $post_link ); ?>"><?php the_title(); ?></a></span><br/>
	<?php endif; ?>

	<?php if ( $sponsoredby ) : ?>
			<div class="cxseries_race_sponsoredby">
				Sponsored by: <?php echo esc_html( $sponsoredby ); ?>
			</div>
	<?php endif; ?>

			<div class="cxseries_race_meta">
				<span class="cxseries_date"><?php echo esc_html( ( ! isset( $custom_fields['dates'] )) ? get_the_date() : $custom_fields['dates'][0] ); ?></span>

	<?php if ( $location ) : ?>
				&bull;  <span class="cxseries_location"><?php echo esc_html( $location ); ?></span>
	<?php endif; ?>
			</div>

			<div class="cxseries_race_links">

	<?php if ( $registration_link ) :
		$has_registration_link = true; ?>
				<a class="btn btn-mini btn-success cxseries_link" href="<?php echo esc_url( $registration_link ); ?>">REGISTRATION</a>
	<?php endif; ?>

	<?php if ( $website_link ) : ?>
				<a class="" href="<?php echo esc_url( $website_link );?>"><img src="<?php bloginfo( 'template_url' )?>/assets/images/link-20.png" alt="website" /></a>
	<?php endif; ?>

	<?php if ( $facebook_link ) : ?>
				<a class="" href="<?php echo esc_url( $facebook_link ); ?>"><img src="<?php bloginfo( 'template_url' )?>/assets/images/facebook-20.png" alt="facebook" /></a>
	<?php endif; ?>

	<?php if ( $twitter_link ) : ?>
				<a class="" href="<?php echo esc_url( $twitter_link ); ?>"><img src="<?php bloginfo( 'template_url' )?>/assets/images/twitter-20.png" alt="twitter" /></a>
	<?php endif; ?>

			</div>

	<?php

}

/**
 * Upcomign Races WP_Widget
 *
 * Displays upcoming races as a widget
 */
class UpcomingRacesWidget extends \WP_Widget {

	/**
	 * Constructor
	 *
	 * Constructs the WP_Widget
	 */
	public function __construct() {

		parent::__construct(
			'UpcomingRacesWidget', // Base ID.
			'Upcoming Races', // Name.
			array( 'description' => __( 'Upcoming Races', 'text_domain' ), 'widget_title' => 'Upcoming Races', 'number_of_posts' => 1 )
		);

	}

	/**
	 * Form for Add/Edit of Widget
	 *
	 * @param obj $instance instance of this particular widget.
	 */
	public function form( $instance ) {
		$cat = (isset( $instance['cat'] )) ? $instance['cat'] : ''; ?>

			<p><label for="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>">Widget Title</label>
			<br/>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" value="<?php echo esc_attr( $instance['widget_title'] ) ?>" />
			</p>

			<p><label># of posts <em>(leave empty for ALL)</em></label>

			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'number_of_posts' ) ); ?>" value="<?php echo esc_attr( $instance['number_of_posts'] ) ?>" />

			<input type="hidden" id="cxseries_upcoming_posts_submit" name="cxseries_upcoming_posts_submit" value="1" />

	<?php

	}

	/**
	 * Handle update of widget form
	 *
	 * @param obj $new new data.
	 * @param obj $old old data.
	 */
	public function update( $new, $old ) {
		$instance = array();
		$instance['widget_title'] = strip_tags( $new['widget_title'] );
		$instance['number_of_posts'] = strip_tags( $new['number_of_posts'] );
		return $instance;
	}


	/**
	 * Display the widget
	 *
	 * @param array $args arguments.
	 * @param array $instance the data for this instance of the widget.
	 */
	public function widget( $args, $instance ) {
		wp_reset_postdata();
		$number_of_posts = ( ! empty( $instance['number_of_posts'] )) ? $instance['number_of_posts'] : 999;

		echo ' <div class="cxseries_upcoming_races">';

		if ( isset( $instance['widget_title'] ) && ! empty( $instance['widget_title'] ) ) :
			echo '<h2>' . esc_html( $instance['widget_title'] ) . '</h2>';
			endif;

		$the_query = fetch_races( $number_of_posts );

		if ( $the_query->have_posts() ) :
		?>
      <ul>
		<?php
			$post_count = 0;

		while ( $the_query->have_posts() && $post_count < $number_of_posts ) :
			$the_query->the_post(); ?>

					  <li>
						<?php display_race_details(); ?>
					  </li>

				<?php

				$post_count++;

				endwhile;
?>
      </ul>
<?php
			else :
?>
				<p>No upcoming races. See ya next season!</p>
<?php
			endif;

			wp_reset_postdata();
?>
      </div>
<?php

	}
}


/**
 * Next Race SHORTCODE
 *
 * Displays the next race using the [next_race][/next_race] short code
 *
 * @param array $atts attributes for this shortcode.
 */
function next_race_shortcode( $atts = [] ) {

	ob_start();
	$the_query = display_next_race();
	$output = ob_get_clean();
	return $output;
}


/**
 * Upcoming Races SHORTCODE
 *
 * Displays the upcoming races using the [upcoming_races][/upcoming_races] short code
 *
 * @param array $atts attributes for this shortcode.
 */
function upcoming_races_shortcode( $atts = [] ) {

	// Configure defaults and extract the attributes into variables.
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );
	$upcoming_races_atts = shortcode_atts([
		'races' => 999,
	], $atts);

	$args = array(

	/*
	'before_widget' => ' < div class = "box widget scheme-' . $scheme . ' " > ',
	'after_widget'  => ' < / div > ',
	'before_title'  => ' < div class = 'widget-title' > ',
	'after_title'   => ' < / div > ',
	*/

	);

	$instance = array(
	  'number_of_posts' => $upcoming_races_atts['races'],
	);

	ob_start();
	the_widget( __NAMESPACE__ . '\UpcomingRacesWidget', $args, $instance );
	$output = ob_get_clean();
	return $output;
}


/**
 * Race Details SHORTCODE
 *
 * Displays the details for a specified race using the [race_details][/race_details] short code
 *
 * @param array $attributes attributes for this shortcode.
 */
function race_details_shortcode( $attributes ) {
	display_race_details( $title = false );
}

/**
 * BikeReg Registration Form SHORTCODE
 *
 * Displays the details for a specified race using the [bikereg]12345[/bikereg] short code
 *
 * @param array $attributes attributes for this shortcode.
 * @param int   $event_id the BikeReg event id.
 */
function bikereg_form_shortcode( $attributes, $event_id = null ) {
	if ( is_numeric( $event_id ) ) :
		$html = '<div class="" style="max -width:800px; margin:auto;" id="regWrapper">' .
			'<script src="https:// www.bikereg.com/Scripts/athleteRegWidget.js"></script> ' .
			'<iframe src="https://www.bikereg.com/' . esc_attr( $event_id ) . '?if=1" style="height:100%;width:100%;border-radius:7px;" frameBorder="0" id="regFrame"></iframe> ' .
			'</div>';
		return $html;
		else :
			return false;
		endif;

}

/**
 * Fetch Results
 *
 * Fetch results attachements for a specific attachment tag.
 *
 * @param string $tag tag name.
 */
function fetch_results( $tag = '2015' ) {
	$number_of_races = 999;
	$qargs = 'post_type=attachment&order=DESC&orderby=date&tag=' . $tag . '&category_name=results&post_status=any&posts_per_page=' . $number_of_races;
	return new \WP_Query( $qargs );
}

/**
 * List Results
 *
 * Lists all results
 */
function list_results() {
	global $post;

	$tags = get_tags(
		array(
		'get' => 'all',
		'orderby' => 'name',
		'order' => 'desc',
		)
	);
	if ( $tags ) :
		foreach ( $tags as $tag ) :

			if ( preg_match( '/[0-9]{4}/', $tag->name ) ) :

				wp_reset_postdata();

				$the_query = fetch_results( $tag->name );

				if ( $the_query->have_posts() ) :
					echo '<h3>' . esc_html( $tag->name ) . '</h3>';
					echo '<ul>';
					$post_count = 0;
					while ( $the_query->have_posts() ) :
						$the_query->the_post(); ?>
				<li><?php the_attachment_link(); ?></li>
	<?php
		$post_count++;

				endwhile;
					echo '</ul>';
	else :
		echo '<p>No results posted!</p>';
					endif;

					wp_reset_postdata();

				endif;
			endforeach;
		endif;

	// TODO
	// get list of tags that match year format
	// cycle through each tag and query individually.
	wp_reset_postdata();
}

	/**
	 * Create Races Post Type
	 *
	 * Create and register the races custom post time
	 */
function create_races_post_type() {
	register_post_type(
		'races',
		array(
		'labels' => array(
		'name' => __( 'Races' ),
		  'singular_name' => __( 'Race' ),
		),
		'supports' => array( 'title','editor' ),
		'public' => true,
		'has_archive' => false,
		'rewrite' => array( 'slug' => 'races' ),
		'menu_icon' => 'dashicons-awards',
		)
	);
}

	// TODO move these to an init function, define as constants?
	$cxseries_race_labels = array(
	'Location' => 'location',
	'Sponsored By' => 'sponsoredby',
	);

	$cxseries_race_links = array(
	'Registration Link' => 'registration_link',
	'Website' => 'website_link',
	'Facebook' => 'facebook_link',
	'Twitter' => 'twitter_link',
	);


	/**
	 * Race Metabox Setup
	 *
	 * Create all race post type metaboxes (labels and links)
	 */
	function race_metabox_setup() {
		global $cxseries_race_labels, $cxseries_race_links;

		foreach ( $cxseries_race_labels as $label_name => $label_id ) :
			add_meta_box(
				'cxseries_race_' . $label_id,
				$label_name,
				__NAMESPACE__ . '\race_labels_metaboxes',
				'races',
				'normal',
				'high'
			);
			endforeach;

		foreach ( $cxseries_race_links as $link_name => $link_id ) :
			add_meta_box(
				'cxseries_race_' . $link_id,
				$link_name,
				__NAMESPACE__ . '\race_links_metaboxes',
				'races',
				'normal',
				'high',
				array(
				'link_name' => $link_name,
				'link_id' => $link_id,
				)
			);
			endforeach;
	}


	/**
	 * Display Race Label Metaboxes
	 *
	 * Displays race label metaboxes
	 *
	 * @param obj    $post the post object.
	 * @param string $label label for this metabox.
	 */
	function race_labels_metaboxes( $post, $label ) {
		wp_nonce_field( basename( __FILE__ ), $label['id'] . '_nonce' );

		$value = get_post_meta( $post->ID, $label['id'], true ); ?>
			<p>
			<label for="<?php echo esc_attr( $label['id'] ); ?>"><?php echo esc_html( $label['title'] );?></label>
			<br />
			<input class="widefat" type="text" name="<?php echo esc_attr( $label['id'] ) ?>" id="<?php echo esc_attr( $label['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" size="30" />
		  </p>
		<?php

	}


	/**
	 * Save Race Label Metaboxes
	 *
	 * Saves metabox information on update
	 *
	 * @param id  $post_id the id of the post.
	 * @param obj $post post object info.
	 */
	function save_label_metaboxes( $post_id, $post ) {
		global $cxseries_race_labels;

		foreach ( $cxseries_race_labels as $label_name => $label_id ) :

			/* Verify the nonce before proceeding. */
			if ( ! isset( $_POST[ 'cxseries_race_' . $label_id . '_nonce' ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ 'cxseries_race_' . $label_id . '_nonce' ] ) , basename( __FILE__ ) ) ) {
				return $post_id;
			}

			/* Get the post type object. */
			$post_type = get_post_type_object( $post->post_type );

			/* Check if the current user has permission to edit the post. */
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return $post_id;
			}

			/* Get the posted data and sanitize it for use as an HTML class. */
			$new_meta_value = (isset( $_POST[ 'cxseries_race_' . $label_id ] ) ? $_POST[ 'cxseries_race_' . $label_id ] : '');

			/* Get the meta key. */
			$meta_key = 'cxseries_race_' . $label_id;

			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, $meta_key, true );

			/* If a new meta value was added and there was no previous value, add it. */
			if ( $new_meta_value && '' == $meta_value ) {
				add_post_meta( $post_id, $meta_key, $new_meta_value, true );
			} /* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
				update_post_meta( $post_id, $meta_key, $new_meta_value );
			} /* If there is no new meta value but an old value exists, delete it. */
			elseif ( '' == $new_meta_value && $meta_value ) {
				delete_post_meta( $post_id, $meta_key, $meta_value );
			}

			endforeach;
	}


	/**
	 * Display Race Link Metaboxes
	 *
	 * Displays race link metaboxes
	 *
	 * @param obj    $post the post object.
	 * @param string $link the link for this metabox.
	 */
	function race_links_metaboxes( $post, $link ) {
		wp_nonce_field( basename( __FILE__ ), $link['id'] . '_nonce' );

		$value = get_post_meta( $post->ID, $link['id'], true ); ?>
			<p>
			<label for="<?php echo esc_attr( $link['id'] ) ?>"><?php echo esc_html( $link['title'] ); ?></label>
			<br />
			<input class="widefat" type="text" name="<?php echo esc_attr( $link['id'] ) ?>" id="<?php echo esc_attr( $link['id'] ); ?>" value="<?php echo esc_attr( $value ); ?>" size="30" />
		  </p>
		<?php

	}


	/**
	 * Save Race Link Metaboxes
	 *
	 * Saves metabox information on update
	 *
	 * @param id  $post_id the id of the post.
	 * @param obj $post post object info.
	 */
	function save_link_metaboxes( $post_id, $post ) {
		global $cxseries_race_links;

		foreach ( $cxseries_race_links as $link_name => $link_id ) :

			/* Verify the nonce before proceeding. */
			if ( ! isset( $_POST[ 'cxseries_race_' . $link_id . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'cxseries_race_' . $link_id . '_nonce' ], basename( __FILE__ ) ) ) {
				return $post_id;
			}

			/* Get the post type object. */
			$post_type = get_post_type_object( $post->post_type );

			/* Check if the current user has permission to edit the post. */
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return $post_id;
			}

			/*
			Get the posted data. */
			// TODO validate for valid link.
			$new_meta_value = (isset( $_POST[ 'cxseries_race_' . $link_id ] ) ? $_POST[ 'cxseries_race_' . $link_id ] : '');

			/* Get the meta key. */
			$meta_key = 'cxseries_race_' . $link_id;

			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, $meta_key, true );

			/* If a new meta value was added and there was no previous value, add it. */
			if ( $new_meta_value && '' == $meta_value ) {
				add_post_meta( $post_id, $meta_key, $new_meta_value, true );
			} /* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
				update_post_meta( $post_id, $meta_key, $new_meta_value );
			} /* If there is no new meta value but an old value exists, delete it. */
			elseif ( '' == $new_meta_value && $meta_value ) {
				delete_post_meta( $post_id, $meta_key, $meta_value );
			}

			endforeach;
	}

	/**
	 * Show Future Posts
	 *
	 * Allows for future, published posts to be displayed
	 *
	 * @param array $posts array of posts.
	 */
	function show_future_posts( $posts ) {
		global $wp_query, $wpdb;
		if ( is_single() && $wp_query->post_count == 0 ) {
			$posts = $wpdb->get_results( $wp_query->request );
		}
		return $posts;
	}

	/**
	 * Add Categories for Attachements
	 *
	 * Registers new taxonomy (categories) for attachments, needed for organizing
	 *  our "results"
	 */
	function add_categories_for_attachments() {
		register_taxonomy_for_object_type( 'category', 'attachment' );
	}

	/**
	 * Add Tags for Attachements
	 *
	 * Registers new taxonomy (tags) for attachments, needed for organizing
	 *  our "results"
	 */
	function add_tags_for_attachments() {
		register_taxonomy_for_object_type( 'post_tag', 'attachment' );
	}

?>
