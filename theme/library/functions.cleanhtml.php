<?php  if (! defined('MY_BASEPATH')) exit('No direct script access allowed');

/**
 * @author      Jonhoo
 * @author      modified by peng@pengxwang.com
 * @link        http://snippets.dzone.com/posts/show/1964
 */
function ch_clean_newlines($output)
{
    $pieces = explode("\n", $output);
    $skip = FALSE;
    foreach ($pieces as $key => $str)
    {
        // #modified
        if ($skip) { continue; }
        if (preg_match('/<script[^>]*><\/script>/', $str)) { continue; }
        if (preg_match('/<(textarea|pre)>/', $str)) 
            { /*_log('ch_clean_newlines');*/ $skip = true; continue; } // start of tag
        if (preg_match('/<\/(textarea|pre)>/', $str)) 
            { /*_log('ch_clean_newlines');*/ $skip = false; continue; } // end of tag
        
        // Makes sure empty lines are ignores
        if ( ! preg_match("/^(\s)*$/", $str))
        {
            $pieces[$key] = preg_replace("/>(\s|\t)*</U", ">\n<", $str);
        }
    }
    return implode("\n", $pieces);
}

function ch_clean_html($output)
{
    // Set wanted indentation
    $indent = str_repeat(" ", 4);
    // Uses previous function to seperate tags
    $output = ch_clean_newlines($output);
    $output = explode("\n", $output);
    // Sets no indentation
    $indent_level = 0;
    $skip = FALSE;
    foreach ($output as $key => $value)
    {
        // #modified
        if ($skip) { continue; }
        if (preg_match('/<(textarea|pre)>/', $value)) 
            { /*_log('ch_clean_newlines');*/ $skip = true; continue; } // start of tag
        if (preg_match('/<\/(textarea|pre)>/', $value)) 
            { /*_log('ch_clean_newlines');*/ $skip = false; continue; } // end of tag
        
        // Removes all indentation
        $value = preg_replace("/\t+/", "", $value);
        $value = preg_replace("/^\s+/", "", $value);
        $indent_replace = "";
        // Sets the indentation from current indent level
        for ($o = 0; $o < $indent_level; $o++)
        {
            $indent_replace .= $indent;
        }
        // If self-closing tag, simply apply indent
        if (preg_match("/<(.+)\/>/", $value))
        { 
            $output[$key] = $indent_replace . $value;
        }
        // If doctype declaration, simply apply indent
        elseif (preg_match("/<!(.*)>/", $value))
        { 
            $output[$key] = $indent_replace . $value;
        }
        // If opening AND closing tag on same line, simply apply indent
        elseif (preg_match("/<[^\/](.*)>/", $value) AND preg_match("/<\/(.*)>/", $value))
        { 
            $output[$key] = $indent_replace . $value;
        }
        // If closing HTML tag or closing JavaScript clams, decrease indentation and then apply the new level
        elseif (preg_match("/<\/(.*)>/", $value) OR preg_match("/^(\s|\t)*\}{1}(\s|\t)*$/", $value))
        {
            $indent_level--;
            $indent_replace = "";
            for ($o = 0; $o < $indent_level; $o++)
            {
                $indent_replace .= $indent;
            }
            $output[$key] = $indent_replace . $value;
        }
        // If opening HTML tag AND not a stand-alone tag, or opening JavaScript clams, increase indentation and then apply new level
        elseif (  (preg_match("/<[^\/](.*)>/", $value) AND ! preg_match("/<(link|meta|base|br|img|hr)(.*)>/", $value)) 
                OR preg_match("/^(\s|\t)*\{{1}(\s|\t)*$/", $value))
        {
            $output[$key] = $indent_replace . $value;
            $indent_level++;
            $indent_replace = "";
            for ($o = 0; $o < $indent_level; $o++)
            {
                $indent_replace .= $indent;
            }
        }
        // Else only apply indentation
        else
        {
            $output[$key] = $indent_replace . $value;
        }
    }
    // Return single string seperated by newline
    $output[] = '<!-- Thanks for reviewing the source. This code has been automatically re-indented. -->';
    $output = implode("\n", $output);
    return $output;
}