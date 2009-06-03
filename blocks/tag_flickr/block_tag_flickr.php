<?php // $id$

require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/magpie/rss_cache.inc');

define('FLICKR_DEV_KEY', '4fddbdd7ff2376beec54d7f6afad425e');
define('DEFAULT_NUMBER_OF_PHOTOS', 6);
define('FLICKR_CACHE_EXPIRATION', 1800);

class block_tag_flickr extends block_base {

    function init() {
        $this->title = get_string('defaulttile','block_tag_flickr');
        $this->version = 2007101509;
    }

    function applicable_formats() {
        return array('tag' => true);
    }

    function specialization() {
        $this->title = !empty($this->config->title) ? $this->config->title : get_string('blockname', 'block_tag_flickr');
    }

    function instance_allow_multiple() {
        return true;
    }

    function preferred_width() {
        return 170;
    } 

    function get_content() {

        global $CFG, $USER, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $tagid = optional_param('id', 0, PARAM_INT);   // tag id - for backware compatibility
        $tag = optional_param('tag', '', PARAM_TAG); // tag 

        if ($tag) {
            $tagobject = tag_get('name', $tag);
        } else if ($tagid) {
            $tagobject = tag_get('id', $tagid);
        }

        if (empty($tagobject)) {
            print_error('tagnotfound');
        }

        //include related tags in the photo query ?
        $tagscsv = $tagobject->name;
        if (!empty($this->config->includerelatedtags)) {
            $tagscsv .= ',' . tag_get_related_tags_csv(tag_get_related_tags($tagobject->id), TAG_RETURN_TEXT);
        }
        $tagscsv = urlencode($tagscsv);

        //number of photos to display
        $numberofphotos = DEFAULT_NUMBER_OF_PHOTOS;
        if( !empty($this->config->numberofphotos)) {
            $numberofphotos = $this->config->numberofphotos;
        }

        //sort search results by
        $sortby = 'relevance';
        if( !empty($this->config->sortby)) {
            $sortby = $this->config->sortby;
        }

        //pull photos from a specific photoset
        if(!empty($this->config->photoset)){

            $request = 'http://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos';
            $request .= '&api_key='.FLICKR_DEV_KEY;
            $request .= '&photoset_id='.$this->config->photoset;
            $request .= '&per_page='.$numberofphotos;
            $request .= '&format=php_serial';

            $response = $this->fetch_request($request);

            $search = unserialize($response);

            foreach ($search['photoset']['photo'] as $p){
                $p['owner'] = $search['photoset']['owner'];
            }

            $photos = array_values($search['photoset']['photo']);

        }
        //search for photos tagged with $tagscsv
        else{

            $request = 'http://api.flickr.com/services/rest/?method=flickr.photos.search';
            $request .= '&api_key='.FLICKR_DEV_KEY;
            $request .= '&tags='.$tagscsv;
            $request .= '&per_page='.$numberofphotos;
            $request .= '&sort='.$sortby;
            $request .= '&format=php_serial';

            $response = $this->fetch_request($request);

            $search = unserialize($response);
            $photos = array_values($search['photos']['photo']);  
        }


        if(strcmp($search['stat'], 'ok') != 0) return; //if no results were returned, exit...

        //Accessibility: render the list of photos
        $text = '<ul class="inline-list">';
         foreach ($photos as $photo) {
            $text .= '<li><a href="http://www.flickr.com/photos/' . $photo['owner'] . '/' . $photo['id'] . '/" title="'.s($photo['title']).'">';
            $text .= '<img alt="'.s($photo['title']).'" class="flickr-photos" src="'. $this->build_photo_url($photo, 'square') ."\" /></a></li>\n";
         }
        $text .= "</ul>\n";

        $this->content = new stdClass;
        $this->content->text = $text;
        $this->content->footer = '';

        return $this->content;
    }

    function fetch_request($request){
        global $CFG;

        make_upload_directory('/cache/flickr');

        $cache = new RSSCache($CFG->dataroot . '/cache/flickr', FLICKR_CACHE_EXPIRATION);
        $cache_status = $cache->check_cache( $request);

        if ( $cache_status == 'HIT' ) {
            $cached_response = $cache->get( $request );

            return $cached_response;
        }

        if ( $cache_status == 'STALE' ) {
            $cached_response = $cache->get( $request );
        }

        $response = download_file_content($request);

        if(empty($response)){
            $response = $cached_response;
        }
        else{
            $cache->set($request, $response);    
        }

        return $response;        
    }

    function build_photo_url ($photo, $size='medium') {
        //receives an array (can use the individual photo data returned
        //from an API call) and returns a URL (doesn't mean that the
        //file size exists)
        $sizes = array(
            'square' => '_s',
            'thumbnail' => '_t',
            'small' => '_m',
            'medium' => '',
            'large' => '_b',
            'original' => '_o'
        );

        $size = strtolower($size);
        if (!array_key_exists($size, $sizes)) {
            $size = 'medium';
        }

        if ($size == 'original') {
            $url = 'http://farm' . $photo['farm'] . '.static.flickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['originalsecret'] . '_o' . '.' . $photo['originalformat'];
        } else {
            $url = 'http://farm' . $photo['farm'] . '.static.flickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'] . $sizes[$size] . '.jpg';
        }
        return $url;
    }    
}

?>
