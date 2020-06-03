<?php

namespace Pforret\PfPageparser;


class PfPageparser
{
    // Build your next great package.
    private $config;
    private $content="";
    private $chunks=[];
    private $parsed=[];

    public function __construct($config=[]){
        $defaults=[
            'userAgent' =>  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
            'cacheTtl'  =>  3600,
            'timeOut'   =>  10,
        ];

        $this->config=array_merge($defaults,$config);
    }

    public function get_config(){
        return $this->config;
    }

    /* ------------------------------------------
     * LOADING THE CONTENT FROM A URL/FILE/STRING
     */

    public function load_from_url(string $url,array $options=[]): PfPageparser
    {
        // TODO: load with guzzle & caching
        $options=array_merge($this->config,$options);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_USERAGENT, $options['userAgent']);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_TIMEOUT,$options['timeOut']);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,$options['timeOut']);
        $this->content = curl_exec ($ch);
        curl_close ($ch);
        return $this;
    }

    public function load_from_file(string $filename): PfPageparser
    {
        // load directly from file

        if(file_exists($filename)){
            $this->content=file_get_contents($filename);
        }
        return $this;
    }

    public function load_fom_string(string $string): PfPageparser
    {
        // load HTML string
        $this->content=$string;
        return $this;
    }

    /* ------------------------------------------
    * GET RAW CONTENT BACK
    */

    /**
     * @return string
     */
    public function get_content():string
    {
        // for backward compatibility
        return $this->raw();
    }

    /**
     * @return string
     */
    public function raw(): string
    {
        return $this->content;
    }

    /* ------------------------------------------
    * MODIFY THE RAW CONTENT
    */

    public function trim_before(string $pattern,bool $is_regex=false): PfPageparser
    {
        $found = $is_regex ? preg_match($pattern, $this->content, $matches) : strpos($this->content, $pattern);
        if($found) $this->content = substr($this->content, $found);
        return $this;
    }

    public function trim_after(string $pattern,bool $is_regex=false): PfPageparser
    {
        $found = $is_regex ? preg_match($pattern, $this->content, $matches) : strpos($this->content, $pattern);
        if($found) $this->content=substr($this->content,0,$found);
        return $this;

    }

    public function trim(string $before="<body",string $after="</body",bool $is_regex=false): PfPageparser
    {
        $this->trim_before($before,$is_regex);
        $this->trim_after($after,$is_regex);
        return $this;
    }

    /* ------------------------------------------
    * RAW CONTENT => CHUNKS
    */

    /**
     * @param $pattern
     * @param $is_regex
     * @return $this
     * split the HTML content into chunks based on a text or regex separator
     */

    public function split_chunks(string $pattern,bool $is_regex=false): PfPageparser
    {
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
     * @param array $pattern_keep   - array of patterns that should be found (combined with OR)
     * @param array $pattern_remove - array of patterns that should not be found (combined with OR)
     * @param bool $is_regex       - whether patterns are regex or just strings
     * @return $this
     */
    public function filter_chunks($pattern_keep=[],$pattern_remove=[],bool $is_regex=false): PfPageparser
    {
        $id=false;
        $matches=false;
        $chunk=false;

        if(empty($this->chunks)){
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
            if(!empty($pattern_keep)){
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
            if(!empty($pattern_remove)){
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
    public function parse_fom_chunks(string $pattern,bool $restart=false): PfPageparser
    {
        if(empty($this->chunks)){
            return $this;
        }
        if($restart or empty($this->parsed)){
            $items=&$this->chunks;
        } else {
            $items=&$this->parsed;
        }
        foreach($items as $item){
            $matches=[];
            if(preg_match_all($pattern,$item,$matches,PREG_SET_ORDER)){
                $chunk_results=[];
                foreach($matches as $match){
                    $chunk_results[]=$match[1];
                }
                $this->parsed[]=$chunk_results;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function get_chunks(): array
    {
        return $this->chunks;
    }

    public function results(bool $before_parsing=false): array
    {
        if($before_parsing or empty($this->parsed)){
            return $this->chunks;
        } else {
            return $this->parsed;
        }
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

}
