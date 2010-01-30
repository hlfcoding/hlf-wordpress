<?php

/**
 * Custom Feeds Functions
 * @see         feed include modules
 * @see         functions.php
 * @category    Controller
 * @package     WordPress
 * @subpackage  Peng's WordPress Frontend
 */

/**
 * Universal Feed Registrar 
 * replaces old feed if existent
 * modifies rewrite rules accordingly
 * NOTE uses just-in-time initialization
 * @param   string
 * @param   array           custom options
 */
function tAddFeed ($feedName, $args = array())
{
    global $wp_rewrite;
    global $tFeeds; 
    if (!$tFeeds) 
        $tFeeds = array();
    if (empty($feedName)) 
        $feedName = get_default_feed(); # rss2
    $tFeeds[$feedName] = $args;
    add_feed($feedName, 'tLoadFeed'); # hook onto do_feed action
    add_action('generate_rewrite_rules', 'tFeedRewriteRules');
    $wp_rewrite->flush_rules();
}
function tFeedRewriteRules ($wp_rewrite)
{
    $newRules = 
    array('feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index(1)
        );
    $wp_rewrite->rules = $newRules + $wp_rewrite->rules;
}

/**
 * Universal Feed Link Generator
 * @see     wp-includes/rewrite.php, wp-includes/taxonomy.php, wp-includes/feed.php
 * @see     wp-includes/link-template.php: get_category_feed_link
 * @uses    get_term_by, is_taxonomy, get_default_feed, get_option, add_query_arg, 
 *          get_category_permastruct, get_tag_permastruct, get_category_parents
 *          user_trailingslashit, trailingslashit
 * @param   string          custom feed name or taxonomy
 * @param   int             only needed for some feeds
 * @global  object          url rewriter
 * @return  string          full url
 */
function tFeedLink ($feed = '', $id = false)
{
    $taxonomy   = '';
    $term       = '';
    if ($id && is_taxonomy($feed) ) {
        $taxonomy   = $feed;
        $term       = get_term_by('id', $id, $taxonomy);
    }
    global $wp_rewrite;
    if (empty($feed) ) {
        $feed = get_default_feed();
    }
    if (get_option('permalink_structure') == '') { # no wp-rewrite
        $link = add_query_arg(
                  array('feed' => $feed, $taxonomy => $term)
                , get_bloginfo('url')
            );
    } elseif ($feed == 'category' || $feed == 'tag') {
        if (!empty($term) ) {
            $link = ($taxonomy == 'category')
                 ? $wp_rewrite->get_category_permastruct()
                 : $wp_rewrite->get_tag_permastruct();
            $slug = $term->slug;
            if ($taxonomy == 'category') {
                if ($term->parent != 0) {
                    $slug = get_category_parents($term->parent, false, '/', true) . $slug;
                }
                $link = str_replace('%category%', $slug, $link);
            } elseif ($taxonomy == 'post_tag') {
                $link = str_replace('%tag%', $slug, $link);
            }
            $feed = user_trailingslashit(
                      'feed' . (($feed != get_default_feed() && $feed != $taxonomy) ? '/' . $feed : '' )
                    , 'feed'
                );
            $link = trailingslashit(get_bloginfo('url') . $link) . $feed;
        }
    } elseif ($feed == 'work' || $feed == 'writing') {
        
    } elseif ($feed == 'comments') {
        
    }
    $link = apply_filters('t_term__link', $link, $feed);
    return $link;
}

/**
 * Universal Feed Loader
 * hooked function
 * supports custom settings
 * NOTE uses just-in-time initialization
 * @see     functions.php, feed-links include module
 * @uses    Universal Feed Writer class
 */
function tLoadFeed ()
{
    global $tFeeds, $tCurrentFeed, $wp_query, $post;
    $tCurrentFeed = get_query_var('feed');
    // FB::trace($tCurrentFeed);
    if (empty($tCurrentFeed) || $tCurrentFeed == 'feed') {
        $tCurrentFeed = get_default_feed(); # rss2
    }
    $args =& $tFeeds[$tCurrentFeed];	
    $defaults = 
        array('num_entries' => get_option('posts_per_rss')
            , 'do_images' => true
            , 'size_image' => 'large'
            , 'feed_type' => (defined('TFEED') ? TFEED : 'atom')
            , 'feed_title' => 'Recent Posts'
            , 'feed_description' => 'An unfiltered list limited to ' . $num_entries . ' posts'
        );
    $args = apply_filters('t_load_feed_args', $args);
    $args = wp_parse_args($args, $defaults);
        # customizing default info
    if (is_category()) {
        $category = t_get_term_name(get_query_var('cat'), 'category');
        $args['feed_title']         = 'Feed for ' . $category;
        $args['feed_description']   = 'A list limited to ' . $args['num_entries'] . ' posts categorized under ' . $category;
    }
    if ($wp_query->is_comment_feed) { # comment feed
        if (is_singular()) {
            $args['feed_title']     = 'Recent comments on ' . get_the_title_rss();
        } elseif (is_search()) {
            $args['feed_title']     = 'Comments for search on ' . attribute_escape($wp_query->query_vars['s']);
        } else {
            $args['feed_title']     = 'Recent comments for ' . get_wp_title_rss();
        }
        $args['feed_title'] = ent2ncr($args['feed_title']);
        $args['feed_description']   = 'A list limited to ' . $args['num_entries'] . ' comments';
    }
    $args['query'] = tGetFeedQuery();
    if (is_array($args['query'])) {
        $args['query']['showposts'] =& $args['num_entries'];
    }
    if ($tCurrentFeed == 'rss' || $tCurrentFeed == 'rss2' || $tCurrentFeed == 'atom') {
        $args['feed_type']          = $tCurrentFeed;
    } else {
        $args['feed_title']         = ucwords(str_replace('_', ' ', $tCurrentFeed));	
    }
    extract($args);
        # namespacing
    switch ($feed_type) {
        case 'rss2': 
            $namespace = '
                 xmlns:dc="http://purl.org/dc/elements/1.1/"
                 xmlns:atom="http://www.w3.org/2005/Atom"
                 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
                ';
            $feedType = RSS2;
        break; case 'rss':
            $namespace = '
                 xmlns:dc="http://purl.org/dc/elements/1.1/"
                 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
                 xmlns:admin="http://webns.net/mvcb/"
                 xmlns:content="http://purl.org/rss/1.0/modules/content/"
                ';
            $feedType = RSS1;
        break; case 'atom': default:
            $namespace = '
                 xmlns:thr="http://purl.org/syndication/thread/1.0"
                 xml:lang="' . get_option('rss_language') . '"
                 xml:base="' . get_bloginfo_rss('url') . '"
                 ';
            $feedType = ATOM;
        break;
    }
    $GLOBALS['t_feed_ns'] = $namespace; # for use in FeedWriter
    add_filter('t_feed_ns', create_function('$default', 'return $default . $GLOBALS["t_feed_ns"];'));
        # start
    $feedWriter = new FeedWriter($feedType);
    require TDIR . TMODINC . 'feed-head.php';
    require TDIR . TMODINC . 'feed-body.php';
        # output
    $out = ob_get_contents();
    $out = str_replace(array("\n", "\r", "\t", ' '), '', $input);
    ob_end_clean();
    $feedWriter->generateFeed();
    // lifestream_rss_feed();
    // FB::info($args);
}