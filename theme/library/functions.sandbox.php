<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/*
This file is part of SANDBOX.

SANDBOX is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

SANDBOX is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with SANDBOX. If not, see http://www.gnu.org/licenses/.
*/
/**
 * @author      modified by peng@pengxwang.com
 */

define('SANDBOX_EXISTS', TRUE);

function sb_body_class ($print = true) {
    global $wp_query, $current_user;
    $c = array('wordpress');
    sb_date_classes(time(), $c);
    is_front_page()  ? $c[] = 'home'       : null; // For the front page, if set
    is_home()        ? $c[] = 'blog'       : null; // For the blog posts page, if set
    is_archive()     ? $c[] = 'archive'    : null;
    is_date()        ? $c[] = 'date'       : null;
    is_search()      ? $c[] = 'search'     : null;
    is_paged()       ? $c[] = 'paged'      : null;
    is_attachment()  ? $c[] = 'attachment' : null;
    is_404()         ? $c[] = 'four04'     : null; // CSS does not allow a digit as first character
    // Special classes for BODY element when a single post
    if (is_single()) {
        $postID = $wp_query->post->ID;
        the_post();
        // Adds 'single' class and class with the post ID
        $c[] = 'single postid-' . $postID;
        // Adds classes for the month, day, and hour when the post was published
        if (isset($wp_query->post->post_date))
            sb_date_classes(mysql2date('U', $wp_query->post->post_date), $c, 's-');
        // Adds category classes for each category on single posts
        if ($cats = get_the_category())
            foreach ($cats as $cat)
                $c[] = 's-category-' . $cat->slug;
        // Adds tag classes for each tags on single posts
        if ($tags = get_the_tags())
            foreach ($tags as $tag)
                $c[] = 's-tag-' . $tag->slug;
        // Adds MIME-specific classes for attachments
        if (is_attachment()) {
            $mime_type = get_post_mime_type();
            $mime_prefix = array('application/', 'image/', 'text/', 'audio/', 'video/', 'music/');
                $c[] = 'attachmentid-' . $postID . ' attachment-' . str_replace($mime_prefix, "", "$mime_type");
        }
        // Adds author class for the post author
        $c[] = 's-author-' . sanitize_title_with_dashes(strtolower(get_the_author_login()));
        rewind_posts();
    }
    // Author name classes for BODY on author archives
    elseif (is_author()) {
        $author = $wp_query->get_queried_object();
        $c[] = 'author';
        $c[] = 'author-' . $author->user_nicename;
    }
    // Category name classes for BODY on category archvies
    elseif (is_category()) {
        $cat = $wp_query->get_queried_object();
        $c[] = 'category';
        $c[] = 'category-' . $cat->slug;
    }
    // Tag name classes for BODY on tag archives
    elseif (is_tag()) {
        $tags = $wp_query->get_queried_object();
        $c[] = 'tag';
        $c[] = 'tag-' . $tags->slug;
    }
    // Page author for BODY on 'pages'
    elseif (is_page()) {
        $pageID = $wp_query->post->ID;
        $page_children = wp_list_pages("child_of=$pageID&echo=0");
        the_post();
        $c[] = 'page page-' . $pageID;
        $c[] = 'page-' . sanitize_title_with_dashes(strtolower(get_the_author('login')));
        // Checks to see if the page has children and/or is a child page; props to Adam
        if ($page_children)
            $c[] = 'page-parent';
        if ($wp_query->post->post_parent)
            $c[] = 'page-child page-parent-' . $wp_query->post->post_parent;
        if (is_page_template()) // Hat tip to Ian, themeshaper.com
            $c[] = 'page-template template-' . str_replace('.php', '', get_post_meta($pageID, '_wp_page_template', true));
        rewind_posts();
    }
    // Search classes for results or no results
    elseif (is_search()) {
        the_post();
        if (have_posts()) {
            $c[] = 'search-results';
        } else {
            $c[] = 'search-no-results';
        }
        rewind_posts();
    }
    // For when a visitor is logged in while browsing
    if ($current_user->ID)
        $c[] = 'loggedin';
    // Paged classes; for 'page X' classes of index, single, etc.
    if ((($page = $wp_query->get('paged')) || ($page = $wp_query->get('page'))) && $page > 1) {
        // Thanks to Prentiss Riddle, twitter.com/pzriddle, for the security fix below.
        $page = intval($page); // Ensures that an integer (not some dangerous script) is passed for the variable
        $c[] = 'paged-' . $page;
        if (is_single()) {
            $c[] = 'single-paged-' . $page;
        } elseif (is_page()) {
            $c[] = 'page-paged-' . $page;
        } elseif (is_category()) {
            $c[] = 'category-paged-' . $page;
        } elseif (is_tag()) {
            $c[] = 'tag-paged-' . $page;
        } elseif (is_date()) {
            $c[] = 'date-paged-' . $page;
        } elseif (is_author()) {
            $c[] = 'author-paged-' . $page;
        } elseif (is_search()) {
            $c[] = 'search-paged-' . $page;
        }
    }
    // Separates classes with a single space, collates classes for BODY
    $c = join(' ', apply_filters('body_class',  $c)); // Available filter: body_class
    return $print ? print($c) : $c;
}

function sb_post_class ($print = true) 
{
    global $post, $sb_post_alt;
    // hentry for hAtom compliace, gets 'alt' for every other post DIV, describes the post type and p[n]
    $c = array('hentry', "p$sb_post_alt", $post->post_type, $post->post_status);
    // Author for the post queried
    $c[] = 'author-' . sanitize_title_with_dashes(strtolower(get_the_author('login')));
    // Category for the post queried
    foreach ((array) get_the_category() as $cat)
        $c[] = 'category-' . $cat->slug;
    // Tags for the post queried; if not tagged, use .untagged
    if (get_the_tags() == null) {
        $c[] = 'untagged';
    } else {
        foreach ((array) get_the_tags() as $tag)
            $c[] = 'tag-' . $tag->slug;
    }
    // For password-protected posts
    if ($post->post_password)
        $c[] = 'protected';
    // Applies the time- and date-based classes (below) to post DIV
    sb_date_classes(mysql2date('U', $post->post_date), $c);
    // If it's the other to the every, then add 'alt' class
    if (++$sb_post_alt % 2)
        $c[] = 'alt';
    // Separates classes with a single space, collates classes for post DIV
    $c = join(' ', apply_filters('post_class', $c)); // Available filter: post_class
    return $print ? print($c) : $c;
}

// Define the num val for 'alt' classes (in post DIV and comment LI)
$sb_post_alt = 1;

function sb_comment_class ($print = true) 
{
    global $comment, $post, $sb_comment_alt;
    // Collects the comment type (comment, trackback),
    $c = array($comment->comment_type);
    // Counts trackbacks (t[n]) or comments (c[n])
    if ($comment->comment_type == 'comment') {
        $c[] = "c$sb_comment_alt";
    } else {
        $c[] = "t$sb_comment_alt";
    }
    // If the comment author has an id (registered), then print the log in name
    if ($comment->user_id > 0) {
        $user = get_userdata($comment->user_id);
        // For all registered users, 'byuser'; to specificy the registered user, 'commentauthor+[log in name]'
        $c[] = 'byuser comment-author-' . sanitize_title_with_dashes(strtolower($user->user_login));
        // For comment authors who are the author of the post
        if ($comment->user_id === $post->post_author)
            $c[] = 'bypostauthor';
    }
    // If it's the other to the every, then add 'alt' class; collects time- and date-based classes
    sb_date_classes(mysql2date('U', $comment->comment_date), $c, 'c-');
    if (++$sb_comment_alt % 2)
        $c[] = 'alt';
    // Separates classes with a single space, collates classes for comment LI
    $c = join(' ', apply_filters('comment_class', $c)); // Available filter: comment_class
    return $print ? print($c) : $c;
}
// private
function sb_date_classes ($t, &$c, $p = '') 
{
    $t = $t + (get_option('gmt_offset') * 3600);
    $c[] = $p . 'y' . gmdate('Y', $t); // Year
    $c[] = $p . 'm' . gmdate('m', $t); // Month
    $c[] = $p . 'd' . gmdate('d', $t); // Day
    $c[] = $p . 'h' . gmdate('H', $t); // Hour
}