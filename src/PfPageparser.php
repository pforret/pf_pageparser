<?php

namespace Pforret\PfPageparser;


class PfPageparser
{
    // Build your next great package.
    protected $config;
    protected $logger;
    protected $content;
    protected $chunks;
    protected $results;

    public function __construct($config=[],$logger=false){
        $defaults=[
            "UserAgent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36",
            "CacheTime" =>  3600,
        ];

        $this->config=array_merge($defaults,$config);
        $this->content="";
        $this->chunks=[];
        $this->results=[];
        if($logger){
            $this->logger=$logger;
        }
    }

    public function get_config(){
        return $this->config;
    }

    /**
     * @param $url
     * @return PfPageparser
     */
    public function load_from_url($url){
        // load with guzzle & caching
        $this->content=file_get_contents($url);
        return $this;
    }

    /**
     * @param $filename
     * @return PfPageparser
     */
    public function load_from_file($filename){
        // load directly from file
        $this->content=file_get_contents($filename);
        return $this;
    }

    /**
     * @param $string
     * @return PfPageparser
     */
    public function load_fom_string($string){
        // load HTML string
        $this->content=$string;
        return $this;
    }

    /**
     * @return string
     */
    public function get_content(){
        return $this->content;
    }

    /**
     * @param $pattern
     * @param bool $is_regex
     * @return $this
     */
    public function trim_before($pattern,$is_regex=false){
        if($is_regex){
            $matches=[];
            if($found=preg_match($pattern,$this->content,$matches)){
                $this->content=substr($this->content,$found); // trim before
            }
        } else {
            if($found=strpos($this->content,$pattern)){
                $this->content=substr($this->content,$found); // trim before
            }
        }
        return $this;
    }

    /**
     * @param $pattern
     * @param bool $is_regex
     * @return $this
     */
    public function trim_after($pattern,$is_regex=false){
        if($is_regex){
            $matches=[];
            if($found=preg_match($pattern,$this->content,$matches)){
                $this->content=substr($this->content,0,$found); // trim after
            }
        } else {
            if($found=strpos($this->content,$pattern)){
                $this->content=substr($this->content,0,$found + strlen($pattern)); // trim after
            }
        }
        return $this;

    }

    /**
     * @param string $before
     * @param string $after
     * @param bool $is_regex
     * @return $this
     */

    public function trim($before="<body",$after="</body",$is_regex=false){
        $this->trim_before($before,$is_regex);
        $this->trim_after($after,$is_regex);
        return $this;
    }

    /**
     * @param $pattern
     * @param $is_regex
     * @return $this
     * split the HTML content into chunks based on a text or regex separator
     */

    public function split_chunks($pattern,$is_regex=false){
        if(!$is_regex){
            $this->chunks=explode($pattern,$this->content);
        } else {
            $this->chunks=[];
            preg_match_all($pattern,$this->content,$matches, PREG_OFFSET_CAPTURE);
            if($matches) {
                $from_char=0;
                foreach($matches[0] as $match){
                    $separator=$match[0];
                    $at_char=$match[1];
                    $this->chunks[]=substr($this->content,$from_char,$at_char-$from_char-1);
                    $from_char=$at_char+strlen($separator);
                }
            } else {
                $this->chunks[]=$this->content;
            }
         }
        return $this;
    }

    /**
     * @return array
     */
    public function get_chunks(){
        return $this->chunks;
    }

    /**
     * @param bool $pattern_keep   - array of patterns that should be found (combined with OR)
     * @param bool $pattern_remove - array of patterns that should not be found (combined with OR)
     * @param bool $is_regex       - whether patterns are regex or just strings
     * @return $this
     */
    public function filter_chunks($pattern_keep=false,$pattern_remove=false,$is_regex=false){
        $id=false;
        $matches=false;
        $chunk=false;

        if(!$this->chunks){
            // not split in chunks yet
            // do nothing
            return $this;
        }
        if($pattern_keep AND !is_array($pattern_keep)){
            // make it always an array
            $pattern_keep=[$pattern_keep];
        }
        if($pattern_remove AND !is_array($pattern_remove)){
            // make it always an array
            $pattern_remove=[$pattern_remove];
        }
        foreach($this->chunks as $id => $chunk){
            //
            $keep_chunk=true;
            if($pattern_keep){
                $pattern_found=false;
                foreach($pattern_keep as $pattern){
                    if($is_regex){
                        $pattern_found=($pattern_found OR preg_match($pattern,$chunk,$matches));
                    } else {
                        $pattern_found=($pattern_found OR strstr($chunk,$pattern));
                    }
                }
                $keep_chunk=($keep_chunk AND $pattern_found);
            }
            if($pattern_remove){
                $pattern_found=false;
                foreach($pattern_remove as $pattern){
                    if($is_regex){
                        $pattern_found=($pattern_found OR preg_match($pattern,$chunk,$matches));
                    } else {
                        $pattern_found=($pattern_found OR strstr($chunk,$pattern));
                    }
                }
                $keep_chunk=($keep_chunk AND !$pattern_found);
            }
            if(!$keep_chunk){
                unset($this->chunks[$id]);
            }
        }
        return $this;
    }

    /**
     * @param $pattern
     * @return array
     */
    public function parse_fom_chunks($pattern){
        if(!$this->chunks){
            return $this;
        }
        $results=[];
        foreach($this->chunks as $chunk){
            $matches=[];
            if(preg_match_all($pattern,$chunk,$matches,PREG_SET_ORDER)){
                $chunk_results=[];
                foreach($matches as $match){
                    $chunk_results[]=$match[1];
                }
                $results[]=$chunk_results;
            }
        }
        return $results;
    }

    public function preg_get($pattern,$haystack){
        $matches=[];
        if(preg_match($pattern,$haystack,$matches)){
            return $matches[0];
        } else {
            return "";
        }
    }

    /**
     * PROTECTED FUNCTIONS
     */

    /**
     * @param $text
     * @param string $type
     * @return bool
     */
    protected function log($text,$type="LOG"){
        if(!$this->logger)  return false;
        return true;
    }
}
