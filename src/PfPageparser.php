<?php

namespace Pforret\PfPageparser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class PfPageparser
{
    // Build your next great package.
    private $config;
    private $content="";
    private $chunks=[];
    private $parsed=[];
    private $logger=null;

    public function __construct(array $config=[], LoggerInterface $logger=null){
        $defaults=[
            'userAgent' =>  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
            'cacheTtl'  =>  3600,
            'timeout'   =>  5,      // Guzzle timeout
            'method'    =>  'GET',
            'headers'   => [
                'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
            ]
        ];

        if($logger){
            $this->logger=$logger;
        }
        $this->config=array_merge($defaults,$config);
    }

    public function get_config(){
        return $this->config;
    }

    /* ------------------------------------------
     * LOADING THE CONTENT FROM A URL/FILE/STRING
     */

    private function initialize(){
        $this->content="";
        $this->chunks=[];
        $this->parsed=[];
    }

    /**
     * @param string $url
     * @param array $options
     * @return $this
     */
    public function load_from_url(string $url, array $options=[]): PfPageparser
    {
        // TODO: load with caching
        $this->initialize();
        $options=array_merge($this->config,$options);
        $client = new Client([
            'headers'   =>  $options['headers'],
        ]);
        try {
            $res = $client->request($options['method'], $url);
            $this->content=$res->getBody()->getContents();
        } catch (GuzzleException $error) {
            $message = $error->getMessage();
            $this->log($message,'error');
        }
        return $this;
    }

    public function load_from_file(string $filename): PfPageparser
    {
        $this->initialize();
        if(file_exists($filename)){
            $this->content=file_get_contents($filename);
        }
        return $this;
    }

    public function load_fom_string(string $string): PfPageparser
    {
        $this->initialize();
        $this->content=$string;
        return $this;
    }

    public function cleanup_html($remove_linefeeds=true,$shrink_spaces=true ): PfPageparser
    {
        if($remove_linefeeds)   $this->content=preg_replace("|\n+|"," ",$this->content); // remove line feeds
        if($shrink_spaces)      $this->content=preg_replace("|\s\s+|"," ",$this->content); // remove multiple spaces
        return $this;
    }

    /* ------------------------------------------
    * GET THE RAW CONTENT
    */
    public function get_content():string
        {   return $this->content;      /* for backward compatibility */ }

    public function raw(): string
        {    return $this->content; }

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
     * @param array $pattern_keep
     * @param array $pattern_remove
     * @param bool $is_regex
     * @return $this
     */
    public function filter_chunks(array $pattern_keep=[], array $pattern_remove=[], bool $is_regex=false): PfPageparser
    {
        $matches=false;

        if(empty($this->chunks)){
            if($this->content){
                $this->chunks=[$this->content];
            } else {
                return $this;
            }
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
     * @param string $pattern
     * @param bool $only_one
     * @param bool $restart
     * @return PfPageparser
     */
    public function parse_fom_chunks(string $pattern,bool $only_one=false, bool $restart=false): PfPageparser
    {
        if(empty($this->chunks)){
            if($this->content){
                $this->chunks=[$this->content];
            } else {
                return $this;
            }
        }
        if($restart or empty($this->parsed)){
            $items=&$this->chunks;
	$this->parsed=[];
        } else {
            $items=&$this->parsed;
        }
        foreach($items as $item){
            $matches=[];
            if(preg_match_all($pattern,$item,$matches,PREG_SET_ORDER)){
                $chunk_results=[];
                foreach($matches as $match){
                    if($only_one){
                        $chunk_results=$match[1];
                    } else {
                        $chunk_results[]=$match[1];
                    }
                }
                $this->parsed[]=$chunk_results;
            }
        }
        return $this;
    }

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

    private function log(string $text,string $level = 'info')
    {
        if($this->logger){
            $this->logger->log($level,$text);
        }
    }

}
