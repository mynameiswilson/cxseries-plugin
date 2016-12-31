<?php
/*
Plugin Name: CXSeries
Plugin URI: http://cxseries.com
Description: A plugin for the CXSeries theme, which provides Races as posts, upcoming races, and a number of other features.
Version: 201612
Author: Ben Wilson
*/
namespace CXSeries\CXSeriesPlugin;

defined('ABSPATH') or die('No script kiddies please!');

/* Custom Post type: Races */
add_action('init', 'CXSeries\CXSeriesPlugin\CreateRacesPostType');

/* Widget: Upcoming Races */
add_action('widgets_init', create_function('', 'register_widget( "CXSeries\CXSeriesPlugin\UpcomingRacesWidget" );'));

/* Shortcode: Next Race */
add_shortcode('next_race', 'CXSeries\CXSeriesPlugin\NextRaceShortcode');

/* Shortcode: Upcoming Races */
add_shortcode('upcoming_races', 'CXSeries\CXSeriesPlugin\UpcomingRacesShortcode');

/* Shortcode: Race Details */
add_shortcode('race_details', 'CXSeries\CXSeriesPlugin\RaceDetailsShortcode');

/* Shortcode: BikeReg Registration Form */
add_shortcode('bikereg', 'CXSeries\CXSeriesPlugin\BikeRegFormShortcode');


/* Add metaboxes to Races post type */
add_action('add_meta_boxes', 'CXSeries\CXSeriesPlugin\RaceMetaboxSetup');

/* Saving Race Labels and Links on Race posts */
add_action('save_post', 'CXSeries\CXSeriesPlugin\SaveLabelMetaboxes', 10, 2);
add_action('save_post', 'CXSeries\CXSeriesPlugin\SaveLinkMetaboxes', 10, 2);

/* Show Future Posts */
add_filter('the_posts', 'CXSeries\CXSeriesPlugin\ShowFuturePosts');

/* Results: Allow Attachments to be Categorized */
add_action('init', 'CXSeries\CXSeriesPlugin\AddCategoriesForAttachments');
add_action('init', 'CXSeries\CXSeriesPlugin\AddTagsForAttachments');

/*
 * Fetch Races
 */
function FetchRaces($number_of_races = 1)
{
    if ($number_of_races == 1) :
        // if only one race, choose next future race
        $qargs = "post_type=races&order=ASC&orderby=date&post_status=future&posts_per_page=".$number_of_races;
    else :
        // if more than one, choose all
        $qargs = "post_type=races&order=ASC&orderby=date&post_status=any&posts_per_page=".$number_of_races;
    endif;

    return new \WP_Query($qargs);
}

function DisplayNextRace()
{
    wp_reset_postdata();
    $the_query = FetchRaces(1);
    if ($the_query->have_posts()) :
        $post_count = 0;
        $number_of_posts = 1;
        while ($the_query->have_posts() && $post_count < $number_of_posts) :
            $the_query->the_post();
            echo '<div class="cxseries_next_race">';
            CXSeries\CXSeriesPlugin\DisplayRaceDetails();
            echo '</div>';
            $post_count++;
        endwhile;
    else :
        echo "<p>No upcoming races. See ya next season!</p>";
    endif;
    wp_reset_postdata();
}


function DisplayRaceDetails($title = true)
{
    global $post;

    $meta_keys = array("dates","location",'website','facebook','twitter','registration');

    $meta = array();

    $location = get_post_meta($post->ID, 'cxseries_race_location', true);

    $sponsoredby = get_post_meta($post->ID, 'cxseries_race_sponsoredby', true);

    $registration_link = get_post_meta($post->ID, 'cxseries_race_registration_link', true);

    $website_link = get_post_meta($post->ID, 'cxseries_race_website_link', true);

    $facebook_link = get_post_meta($post->ID, 'cxseries_race_facebook_link', true);

    $twitter_link = get_post_meta($post->ID, 'cxseries_race_twitter_link', true);

    $has_registration_link = false; ?>
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
            <span class="cxseries_date"><?php echo (!isset($custom_fields['dates'])) ? get_the_date() : $custom_fields['dates'][0]; ?></span>

<?php if ($location) : ?>
            &bull;  <span class="cxseries_location"><?php echo $location ?></span>
<?php endif; ?>
        </div>

        <div class="cxseries_race_links">

<?php if ($registration_link) :
        $has_registration_link = true; ?>
            <a class="btn btn-mini btn-success cxseries_link" href="<?php echo $registration_link ?>">REGISTRATION</a>
<?php endif; ?>

<?php if ($website_link) : ?>
            <a class="" href="<?php echo $website_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/link-20.png" alt="website" /></a>
<?php endif; ?>

<?php if ($facebook_link) : ?>
            <a class="" href="<?php echo $facebook_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/facebook-20.png" alt="facebook" /></a>
<?php endif; ?>

<?php if ($twitter_link) : ?>
            <a class="" href="<?php echo $twitter_link ?>"><img src="<?php bloginfo('template_url')?>/assets/images/twitter-20.png" alt="twitter" /></a>
<?php endif; ?>

        </div>

<?php

}


/*
 * WIDGET: Upcoming Races
 *
 */
class UpcomingRacesWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'UpcomingRacesWidget', // Base ID
            'Upcoming Races', // Name
            array( 'description' => __('Upcoming Races', 'text_domain'), 'widget_title' => 'Upcoming Races', 'number_of_posts' => 1 ) // Args
        );
    }

    public function form($instance)
    {
        $cat = (isset($instance['cat'])) ? $instance['cat'] : ""; ?>

        <p><label for="<?php echo $this->get_field_name('widget_title'); ?>">Widget Title</label>
        <br/>
        <input type="text" name="<?php echo $this->get_field_name('widget_title') ?>" value="<?php echo esc_attr($instance['widget_title']) ?>" />
        </p>

        <p><label># of posts <em>(leave empty for ALL)</em></label>

        <input type="text" name="<?php echo $this->get_field_name('number_of_posts')?>" value="<?php echo esc_attr($instance['number_of_posts']) ?>" />

        <input type="hidden" id="cxseries_upcoming_posts_submit" name="cxseries_upcoming_posts_submit" value="1" />

<?php

    }

    public function update($new, $old)
    {
        $instance = array();
        $instance['widget_title'] = strip_tags($new['widget_title']);
        $instance['number_of_posts'] = strip_tags($new['number_of_posts']);
        return $instance;
    }



    public function widget($args, $instance)
    {
        wp_reset_postdata();
        $number_of_posts = (!empty($instance['number_of_posts'])) ? $instance['number_of_posts'] : 999;

        echo '<div class="cxseries_upcoming_races">';

        if (isset($instance['widget_title']) && !empty($instance['widget_title'])) :
            echo "<h2>".$instance['widget_title'].'</h2>';
        endif;

        $the_query = FetchRaces($number_of_posts);

        if ($the_query->have_posts()) :

            echo "<ul>";

            // The Loop

            $post_count = 0;

            while ($the_query->have_posts() && $post_count < $number_of_posts) :
                $the_query->the_post(); ?>

                  <li>
                <?php DisplayRaceDetails(); ?>
                  </li>

            <?php

            $post_count++;

            endwhile;

            echo "</ul>";
        else :

                echo "<p>No upcoming races. See ya next season!</p>";

        endif;

        // Reset Post Data

            wp_reset_postdata();

            echo '</div>';
    }
}


/*
 * SHORTCODE: next_race
 */
function NextRaceShortcode($atts)
{
    // Configure defaults and extract the attributes into variables
    extract(
        shortcode_atts(
            array(
            'class'   => '',
            ),
            $atts
        )
    );

    ob_start();
    $the_query = DisplayNextRace();
    $output = ob_get_clean();
    return $output;
}


/*
 * SHORTCODE: upcoming_races
 */
function UpcomingRacesShortcode($atts)
{

    // Configure defaults and extract the attributes into variables
    extract(
        shortcode_atts(
            array(
            'class'   => '',
            'races' => 999
            ),
            $atts
        )
    );

    $args = array(
    /*        'before_widget' => '<div class="box widget scheme-' . $scheme . ' ">',
    'after_widget'  => '</div>',
    'before_title'  => '<div class="widget-title">',
    'after_title'   => '</div>',
    */
    );

    $instance = array(
      'number_of_posts' => $races
    );

    ob_start();
    the_widget("CXSeries\CXSeriesPlugin\UpcomingRacesWidget", $args, $instance);
    $output = ob_get_clean();
    return $output;
}


/*
 * SHORTCODE: race_details
 */
function RaceDetailsShortcode($attributes)
{
    extract(
        shortcode_atts(
            array(
            'class' => ''
            ),
            $attributes
        )
    );

    DisplayRaceDetails($title = false);
}

function BikeRegFormShortcode($attributes, $event_id = null)
{
    extract(shortcode_atts(array(
        'class' => ''
    ), $attributes));

    if (is_numeric($event_id)) :
        return  '<div class="'.$class.'" style="max-width:800px;margin:auto;" id="regWrapper"> '.
                '<script src="https://www.bikereg.com/Scripts/athleteRegWidget.js"></script> '.
                '<iframe src="https://www.bikereg.com/'.$event_id.'?if=1" style="height:100%;width:100%;border-radius:7px;" frameBorder="0" id="regFrame"></iframe> '.
                '</div>';
    else :
        return false;
    endif;

}

/*
 * Get Results by Category:Results and Tag:_YEAR_
 */
function FetchResults($tag = "2015")
{
    $number_of_races = 999;
    $qargs = "post_type=attachment&order=DESC&orderby=date&tag=".$tag."&category_name=results&post_status=any&posts_per_page=".$number_of_races;
    return new \WP_Query($qargs);
}

function ListResults()
{
    global $post;

    $tags = get_tags(
        array(
        "get" => "all",
        "orderby" => "name",
        "order" => "desc")
    );
    if ($tags) :
        foreach ($tags as $tag) :

            if (preg_match('/[0-9]{4}/', $tag->name)) :

                wp_reset_postdata();

                $the_query = FetchResults($tag->name);

                if ($the_query->have_posts()) :
                    echo "<h3>".$tag->name."</h3>";
                    echo "<ul>";
                    // The Loop
                    $post_count = 0;
                    while ($the_query->have_posts()) :
                        $the_query->the_post(); ?>
                                    <li><?php the_attachment_link(); ?></li>
                                <?php
                                $post_count++;

                    endwhile;
                    echo "</ul>";
                else :
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
function CreateRacesPostType()
{
    register_post_type(
        'races',
        // CPT Options
        array(
        'labels' => array(
                'name' => __('Races'),
                'singular_name' => __('Race')
        ),
        'supports' => array('title','editor','custom-fields'),
        'public' => true,
        'has_archive' => false,
        'rewrite' => array('slug' => 'races'),
        )
    );
}

$cxseries_race_labels = array(
    "Location" => "location",
    "Sponsored By" => "sponsoredby");

$cxseries_race_links = array(
            "Registration Link" => "registration_link",
            "Website" => "website_link",
            "Facebook" => "facebook_link",
            "Twitter" => "twitter_link",
        );


function RaceMetaboxSetup()
{
    global $cxseries_race_labels, $cxseries_race_links;

    foreach ($cxseries_race_labels as $label_name => $label_id) :
        add_meta_box(
            'cxseries_race_'.$label_id,
            $label_name,
            'CXSeries\CXSeriesPlugin\RaceLabelsMetaboxes',
            'races',
            'normal',
            'high'
        );
    endforeach;

    foreach ($cxseries_race_links as $link_name => $link_id) :
        add_meta_box(
            'cxseries_race_'.$link_id,
            $link_name,
            'CXSeries\CXSeriesPlugin\RaceLinksMetaboxes',
            'races',
            'normal',
            'high',
            array(
            "link_name" => $link_name,
            "link_id" => $link_id)
        );
    endforeach;
}


/*
 * METABOX: Location
 */


function RaceLabelsMetaboxes($post, $label)
{
    wp_nonce_field(basename(__FILE__), $label['id'].'_nonce');

    $value = get_post_meta($post->ID, $label['id'], true); ?>
    <p>
    <label for="<?php echo $label['id'] ?>"><?php echo $label['title']?></label>
    <br />
    <input class="widefat" type="text" name="<?php echo $label['id'] ?>" id="<?php echo $label['id'] ?>" value="<?php echo esc_attr($value); ?>" size="30" />
  </p>
<?php

}


/* Save the meta box's post metadata. */
function SaveLabelMetaboxes($post_id, $post)
{
    global $cxseries_race_labels;

    foreach ($cxseries_race_labels as $label_name => $label_id) :

        /* Verify the nonce before proceeding. */
        if (!isset($_POST['cxseries_race_'.$label_id.'_nonce']) || !wp_verify_nonce($_POST['cxseries_race_'.$label_id.'_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        /* Get the post type object. */
        $post_type = get_post_type_object($post->post_type);

        /* Check if the current user has permission to edit the post. */
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        /* Get the posted data and sanitize it for use as an HTML class. */
        $new_meta_value = (isset($_POST['cxseries_race_'.$label_id]) ? $_POST['cxseries_race_'.$label_id] : '');

        /* Get the meta key. */
        $meta_key = 'cxseries_race_'.$label_id;

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta($post_id, $meta_key, true);

        /* If a new meta value was added and there was no previous value, add it. */
        if ($new_meta_value && '' == $meta_value) {
            add_post_meta($post_id, $meta_key, $new_meta_value, true);
        } /* If the new meta value does not match the old value, update it. */
        elseif ($new_meta_value && $new_meta_value != $meta_value) {
            update_post_meta($post_id, $meta_key, $new_meta_value);
        } /* If there is no new meta value but an old value exists, delete it. */
        elseif ('' == $new_meta_value && $meta_value) {
            delete_post_meta($post_id, $meta_key, $meta_value);
        }

    endforeach;
}


/*
 * METABOXES: Race Links
 */

function RaceLinksMetaboxes($post, $link)
{
    wp_nonce_field(basename(__FILE__), $link['id'].'_nonce');

    $value = get_post_meta($post->ID, $link['id'], true); ?>
    <p>
    <label for="<?php echo $link['id'] ?>"><?php echo $link['title']?></label>
    <br />
    <input class="widefat" type="text" name="<?php echo $link['id'] ?>" id="<?php echo $link['id'] ?>" value="<?php echo esc_attr($value); ?>" size="30" />
  </p>
<?php

}


/* Save the meta box's post metadata. */
function SaveLinkMetaboxes($post_id, $post)
{
    global $cxseries_race_links;

    foreach ($cxseries_race_links as $link_name => $link_id) :

        /* Verify the nonce before proceeding. */
        if (!isset($_POST['cxseries_race_'.$link_id.'_nonce']) || !wp_verify_nonce($_POST['cxseries_race_'.$link_id.'_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        /* Get the post type object. */
        $post_type = get_post_type_object($post->post_type);

        /* Check if the current user has permission to edit the post. */
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        /* Get the posted data. */
        // TODO validate for valid link
        $new_meta_value = (isset($_POST['cxseries_race_'.$link_id]) ? $_POST['cxseries_race_'.$link_id] : '');

        /* Get the meta key. */
        $meta_key = 'cxseries_race_'.$link_id;

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta($post_id, $meta_key, true);

        /* If a new meta value was added and there was no previous value, add it. */
        if ($new_meta_value && '' == $meta_value) {
            add_post_meta($post_id, $meta_key, $new_meta_value, true);
        } /* If the new meta value does not match the old value, update it. */
        elseif ($new_meta_value && $new_meta_value != $meta_value) {
            update_post_meta($post_id, $meta_key, $new_meta_value);
        } /* If there is no new meta value but an old value exists, delete it. */
        elseif ('' == $new_meta_value && $meta_value) {
            delete_post_meta($post_id, $meta_key, $meta_value);
        }

    endforeach;
}

/*
 * FILTER: SHOW FUTURE POSTS  (for upcoming races)
 */
function ShowFuturePosts($posts)
{
    global $wp_query, $wpdb;
    if (is_single() && $wp_query->post_count == 0) {
        $posts = $wpdb->get_results($wp_query->request);
    }
    return $posts;
}

/*
 * Results: Add Categories to Attachments
 */
function AddCategoriesForAttachments()
{
    register_taxonomy_for_object_type('category', 'attachment');
}

/*
 * Results: Add Tags for Attachments
 */
function AddTagsForAttachments()
{
    register_taxonomy_for_object_type('post_tag', 'attachment');
}

?>
