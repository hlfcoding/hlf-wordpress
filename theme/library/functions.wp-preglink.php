<?php

/**
 * External Link Parser Functions
 * hooked function
 * @see         functions.php
 * @author      Mark Jaquith (txfx.net)     for the external attribute regex and callback
 * @author      Ann Oyama (superann.com)    for the clickable url regex
 * @param       string 
 * @return      string 
 * @category    Controller
 * @package     WordPress
 * @subpackage  Peng's WordPress Frontend 
 */
 
function tPregLink ($content) 
{
    // FB::trace('tPregLink');
    $content = preg_replace(
            array('#([\s>])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is'
                , '#([\s>])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is'
                , '#([\s>])([a-z0-9\-_.]+)@([^,< \n\r]+)#i'
            )
            , 
            array('$1<a href="$2">$2</a>'
                , '$1<a href="http://$2">$2</a>'
                , '$1<a href="mailto:$2@$3">$2@$3</a>'
            )
            , $content
        );
        # this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $content = preg_replace(
              "#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i"
            , "$1$3</a>"
            , $content
        );
    $content = preg_replace_callback(
              '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i'
            , 'tPregLinkCallback'
            , $content
        );
    return trim($content);
}
function tGetDomain ($uri)
{
    preg_match("/^(http:\/\/)?([^\/]+)/i", $uri, $matches);
    $host = $matches[2];
    preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
    return $matches[0];	   
}
function tPregLinkCallback ($matches)
{
        # 1
        # 2
        # 3
        # 4
        # 5
    $html = '<a href="' . $matches[2] . '//' . $matches[3] . '"'
         . $matches[1] . $matches[4]
         . (tGetDomain($matches[3]) != tGetDomain($_SERVER["HTTP_HOST"]) ? ' class="external" rel="nofollow" title="'
         . (strpos($matches[5], $matches[3]) === false ? $matches[3] : '') . '">' : '>')
         . $matches[5] . '</a>';
    $html = apply_filters('t_external_link_parsed', $html);
    return $html;
}
