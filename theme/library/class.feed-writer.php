<?php
// RSS 0.90  Officially obsoleted by 1.0
// RSS 0.91, 0.92, 0.93 and 0.94  Officially obsoleted by 2.0
// So, define constants for RSS 1.0, RSS 2.0 and ATOM 	

    define('RSS1', 'RSS 1.0', true);
    define('RSS2', 'RSS 2.0', true);
    define('ATOM', 'ATOM', true);

/**
 * Universal Feed Writer class
 *
 * Generate RSS 1.0, RSS 2.0 and ATOM Feed
 * NOTE modified for use in Wordpress
 * 
 * @package     UniversalFeedWriter
 * @author      Anis uddin Ahmad <anisniit@gmail.com>
 * @link        http://www.ajaxray.com/projects/rss
 */
class FeedWriter
{
     private $channels      = array();  // Collection of channel elements
     private $items         = array();  // Collection of items as object of FeedItem class.
     private $data          = array();  // Store some other version wise data
     private $CDATAEncoding = array();  // The tag names which have to encoded as CDATA
     
     private $version   = null; 
    
    /**
    * Constructor
    * 
    * @param    constant    the version constant (RSS1/RSS2/ATOM).       
    */ 
    function __construct($version = RSS2)
    {	
        $this->version = $version;
            
        // Setting default value for assential channel elements
        $this->channels['title']        = $version . ' Feed';
        $this->channels['link']         = '';
                
        //Tag names to encode in CDATA
        $this->CDATAEncoding = array('description', 'content:encoded', 'summary', 'dc:subject');
        switch ($version) {
            case ATOM:
                $this->CDATAEncoding[] = 'content';
            break;
            case RSS2:
                $this->CDATAEncoding[] = 'category';
            break;	
        }
    }

    // Start # public functions ---------------------------------------------
    
    /**
    * Set a channel element
    * @access   public
    * @param    string  name of the channel tag
    * @param    string  content of the channel tag
    * @return   void
    */
    public function setChannelElement($elementName, $content)
    {
        $this->channels[$elementName] = $content;
    }
    
    /**
    * Get a channel element
    * @access   public
    * @param    string  name of the channel tag
    * @return   string  content of the channel tag
    */
    public function getChannelElement($elementName)
    {
        return $this->channels[$elementName];
    }
    
    /**
    * Set multiple channel elements from an array. Array elements 
    * should be 'channelName' => 'channelContent' format.
    * 
    * @access   public
    * @param    array   array of channels
    * @return   void
    */
    public function setChannelElementsFromArray($elementArray)
    {
        if(! is_array($elementArray)) return;
        foreach ($elementArray as $elementName => $content) 
        {
            $this->setChannelElement($elementName, $content);
        }
    }
    
    /**
    * Generate the actual RSS/ATOM file
    * 
    * @access   public
    * @return   void
    */ 
    public function GenerateFeed()
    {
        switch ($this->version) 
        {
            case RSS1:
                $content_type = 'application/rdf+xml';
            break; case ATOM:
                $content_type = 'application/atom+xml';
            break; case RSS2: default: 
                $content_type = 'text/xml';
            break;
        }
        if (function_exists('get_option'))
            header('Content-Type: ' . $content_type . '; charset=' . get_option('blog_charset'), true);
        else
            header("Content-type: text/xml");
        
        $this->printHead();
        $this->printChannels();
        $this->printItems();
        $this->printTail();
    }
    
    /**
    * Create a new FeedItem.
    * 
    * @access   public
    * @return   object  instance of FeedItem class
    */
    public function createNewItem()
    {
        $Item = new FeedItem($this->version);
        return $Item;
    }
    
    /**
    * Add a FeedItem to the main class
    * 
    * @access   public
    * @param    object  instance of FeedItem class
    * @return   void
    */
    public function addItem($feedItem)
    {
        $this->items[] = $feedItem;    
    }
        
    
    // Wrapper functions -------------------------------------------------------------------
    
    /**
    * Set the 'title' channel element
    * 
    * @access   public
    * @param    string  value of 'title' channel tag
    * @return   void
    */
    public function setTitle($title)
    {
        $this->setChannelElement('title', $title);
    }
    
    /**
    * Set the 'description' channel element
    * 
    * @access   public
    * @param    string  value of 'description' channel tag
    * @return   void
    */
    public function setDescription($desciption)
    {
        if($this->version == ATOM) 
        {
            $this->setChannelElement('subtitle', $desciption);
        } 
        else
        {
            $this->setChannelElement('description', $desciption);
        }
    }
    
    /**
    * Set the 'link' channel element
    * 
    * @access   public
    * @param    string  value of 'link' channel tag
    * @return   void
    */
    public function setLink($link)
    {
        $this->setChannelElement('link', $link);
    }
    
    /**
    * Set the 'image' channel element
    * 
    * @access   public
    * @param    string  title of image
    * @param    string  link url of the imahe
    * @param    string  path url of the image
    * @return   void
    */
    public function setImage($title, $link, $url)
    {
        $this->setChannelElement('image', array('title'=>$title, 'link'=>$link, 'url'=>$url));
    }
    
    /**
    * Set the 'about' channel element. Only for RSS 1.0
    * 
    * @access   public
    * @param    string  value of 'about' channel tag
    * @return   void
    */
    public function setChannelAbout($url)
    {
        $this->data['ChannelAbout'] = $url;    
    }
    
  /**
  * Generates an UUID
  * @author     Anis uddin Ahmad <admin@ajaxray.com>
  * @param      string  an optional prefix
  * @return     string  the formated uuid
  */
  public function uuid($key = null, $prefix = '') 
  {
    $key = ($key == null)? uniqid(rand()) : $key;
    $chars = md5($key);
    $uuid  = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);

    return $prefix . $uuid;
  }
    // End # public functions ----------------------------------------------
    
    // Start # private functions ----------------------------------------------
    
    /**
    * Prints the xml and rss namespace
    * 
    * @access   private
    * @return   void
    */
    private function printHead()
    {
        $encoding = function_exists('get_option')? get_option('blog_charset') : 'utf-8';
        $out  = '<?xml version="1.0" encoding="' . $encoding . '"?>' . "\n";
        
        if($this->version == RSS2)
        {
            $out .= '<rss version="2.0"
                     xmlns:content="http://purl.org/rss/1.0/modules/content/"
                     xmlns:wfw="http://wellformedweb.org/CommentAPI/"';
        }    
        elseif($this->version == RSS1)
        {
            $out .= '<rdf:RDF
                     xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                     xmlns="http://purl.org/rss/1.0/"';
        }
        elseif($this->version == ATOM)
        {
            $out .= '<feed xmlns="http://www.w3.org/2005/Atom"'; 
        }
        if (function_exists('apply_filters'))
            $out = apply_filters('t_feed_ns', $out);
        $out = str_replace(array("\t", "\r"), '', $out);
        $out .= '>' . PHP_EOL;;
        echo $out;
    }
    
    /**
    * Closes the open tags at the end of file
    * 
    * @access   private
    * @return   void
    */
    private function printTail()
    {
        if($this->version == RSS2)
        {
            echo '</channel>' . PHP_EOL . '</rss>'; 
        }    
        elseif($this->version == RSS1)
        {
            echo '</rdf:RDF>';
        }
        else if($this->version == ATOM)
        {
            echo '</feed>';
        }
      
    }

    /**
    * Creates a single node as xml format
    * 
    * @access   private
    * @param    string  name of the tag
    * @param    mixed   tag value as string or array of nested tags in 'tagName' => 'tagValue' format
    * @param    array   Attributes(if any) in 'attrName' => 'attrValue' format
    * @return   string  formatted xml tag
    */
    private function makeNode($tagName, $tagContent, $attributes = null)
    {        
        $nodeText = '';
        $attrText = '';
        
        if(is_array($attributes))
        {
            foreach ($attributes as $key => $value) 
            {
                $attrText .= " $key=\"$value\" ";
            }
        }
        
        if(is_array($tagContent) && $this->version == RSS1)
        {
            $attrText = ' rdf:parseType="Resource"';
        }
        
        $attrText .= (in_array($tagName, $this->CDATAEncoding) && $this->version == ATOM)? 
            'type="' . (function_exists('t_get_the_rss')? t_get_the_rss('html_type') : 'html') . '"' : '';
        $nodeText .= (in_array($tagName, $this->CDATAEncoding))? "<{$tagName}{$attrText}><![CDATA[" : "<{$tagName}{$attrText}>";
         
        if(is_array($tagContent))
        { 
            foreach ($tagContent as $key => $value) 
            {
                $nodeText .= $this->makeNode($key, $value); //recursion
            }
        }
        else
        {
            $nodeText .= (in_array($tagName, $this->CDATAEncoding))? 
                $tagContent : (function_exists('wp_specialchars')? wp_specialchars($tagContent) : htmlentities($tagContent));
        }           
        
        $nodeText = str_replace(array("\r", "\t"), '', $nodeText);
        
        $nodeText .= (in_array($tagName, $this->CDATAEncoding))? "]]></$tagName>" : "</$tagName>";
        
        return $nodeText . PHP_EOL;
    }
    
    /**
    * @desc     Print channels
    * @access   private
    * @return   void
    */
    private function printChannels()
    {
        //Start channel tag
        switch ($this->version) 
        {
           case RSS2: 
                echo '<channel>' . PHP_EOL;        
                break;
           case RSS1: 
                echo (isset($this->data['ChannelAbout']))? "<channel rdf:about=\"{$this->data['ChannelAbout']}\">" : "<channel rdf:about=\"{$this->channels['link']}\">";
                break;
        }
        
        //Print items of channel
        foreach ($this->channels as $key => $value) 
        {
            if(strpos($key, 'link') !== false && $this->version == ATOM) 
            {
                // ATOM prints link element as href attribute
                echo $this->makeNode('link', '', $value);
                if($key == 'link')
                {
                    echo $this->makeNode('id', $this->uuid($value['href'], 'urn:uuid:'));
                }
            } 
            elseif(is_array($value)) 
            {
                $content = '';
                if(isset($value['content'])) 
                {
                    $content = $value['content'];
                    unset($value['content']);
                }
                echo $this->makeNode($key, $content, $value);
            }
            else
            {
                echo $this->makeNode($key, $value);
            }    
            
        }
        
        //RSS 1.0 have special tag <rdf:Seq> with channel 
        if($this->version == RSS1)
        {
            echo "<items>" . PHP_EOL . "<rdf:Seq>" . PHP_EOL;
            foreach ($this->items as $item) 
            {
                $thisItems = $item->getElements();
                echo "<rdf:li resource=\"{$thisItems['link']['content']}\"/>" . PHP_EOL;
            }
            echo "</rdf:Seq>" . PHP_EOL . "</items>" . PHP_EOL . "</channel>" . PHP_EOL;
        }
    }
    
    /**
    * Prints formatted feed items
    * 
    * @access   private
    * @return   void
    */
    private function printItems()
    {    
        foreach ($this->items as $item) 
        {
            $thisItems = $item->getElements();
            
            //the argument is printed as rdf:about attribute of item in rss 1.0 
            echo $this->startItem($thisItems['link']['content']);
            
            foreach ($thisItems as $feedItem ) 
            {
                echo $this->makeNode($feedItem['name'], $feedItem['content'], $feedItem['attributes']); 
            }
            echo $this->endItem();
        }
    }
    
    /**
    * Make the starting tag of channels
    * 
    * @access   private
    * @param    string  The value of about tag which is used for only RSS 1.0
    * @return   void
    */
    private function startItem($about = false)
    {
        if($this->version == RSS2)
        {
            echo '<item>' . PHP_EOL; 
        }    
        elseif($this->version == RSS1)
        {
            if($about)
            {
                echo "<item rdf:about=\"$about\">" . PHP_EOL;
            }
            else
            {
                die('link element is not set .\n It\'s required for RSS 1.0 to be used as about attribute of item');
            }
        }
        else if($this->version == ATOM)
        {
            echo "<entry>" . PHP_EOL;
        }    
    }
    
    /**
    * Closes feed item tag
    * 
    * @access   private
    * @return   void
    */
    private function endItem()
    {
        if($this->version == RSS2 || $this->version == RSS1)
        {
            echo '</item>' . PHP_EOL; 
        }    
        else if($this->version == ATOM)
        {
            echo "</entry>" . PHP_EOL;
        }
    }
    

    
    // End # private functions ----------------------------------------------
    
 } // end of class FeedWriter
 
// autoload classes
function __autoload($class_name) 
{
    // require_once $class_name . '.php';
}