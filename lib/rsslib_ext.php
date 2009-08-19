<?php
  /*
   * Project:   MockingbirdRSS: a wrapper for the SimplePie library
   * File:      rsslib_ext.php, contains a base class for interfacting with
                the SimplePie library and the wrapper function fetch_rss()
                to maintain compatibility with MagpieRSS.
   * Author:    Charles Fulton <cfulton@kzoo.edu>
                Lennon Day-Reynolds <lennon@reed.edu>
   * License:   GPL
   */

  // Load the Simplepie library
  define('SIMPLEPIE_DIR', $CFG->libdir . '/simplepie_1.2');
  define('SIMPLEPIE_CACHE_DIR', $CFG->dataroot . '/cache/rsscache');
  define('SIMPLEPIE_CACHE_ON', true);
  require_once( SIMPLEPIE_DIR . '/simplepie.inc');

  // Takes the supplied feed $url and returns a MockingbirdRSS object ($RSS)
  function fetch_rss($url) {
      $RSS = new MockingbirdRSS($url);
      $RSS->cache_path = SIMPLEPIE_CACHE_DIR;
      $RSS->enable_caching = SIMPLEPIE_CACHE_ON;
      $RSS->fetch_url();
      return $RSS;
  }

  class MockingbirdRSS {

        var $items;     // Each item is represented as an array with title, description and link set
        var $channel;   // Similar to items but with one array only
        var $feed_url;
        var $cache_path;
        var $enable_caching;

        function MockingbirdRSS($url) {
            $this->items = array();
            $this->channel = array();
            $this->feed_url = $url;
        }

        function fetch_url() {
            $feed = new SimplePie();
            $feed->set_feed_url($this->feed_url);
            $feed->set_cache_location($this->cache_path);
            $feed->enable_cache($this->enable_caching);
            $feed->init();
            $feed->handle_content_type();

            // Channel information
            $this->channel = array(
                                  'title' => $feed->get_title(),
                                  'description' => $feed->get_description(),
                                  'link' => $feed->get_link()
                             );

            // Items
            foreach($feed->get_items() as $item) {
                $link = $item->get_link();

                // If link isn't set on an item look for a media enclosure and pull
                // the link from there instead. This doesn't work right with iTunesU
                // should be revisited.
                if (!$link) {
                    $enc = $item->get_enclosure();
                    $link = $enc->get_link();
                }
                $this->items[] = array(
                                      'title' =>  $item->get_title(),
                                      'description' => $item->get_description(),
                                      'link' => $link
                                      );
            }
        }
  }


