<?php
# version 20230729

class CheckCacheFolder {
  /* internal */
  private $cacheFolder = "";
  private $cacheBaseFolder = "";
  private $errormsg = "";

  public function __construct($cacheBaseFolder, $cacheFolder){
    $this->cacheFolder = $cacheFolder;
    $this->cacheBaseFolder = $cacheBaseFolder;
    $this->checkCacheFolder();
	}

  /* decodeFeedData: convert raw-json-data into array */
	private function checkCacheFolder() {
        # wp version 4.4.2 and later: "/cache" is not created at install, so the plugin has to check and create...
        if (!is_dir($this->cacheBaseFolder)) {
          $mkdirError = mkdir($this->cacheBaseFolder) ?? FALSE;
          if (!$mkdirError) {
            # mkdir failed, usually due to missing write-permissions
            $this->errormsg .= "<hr><b>".__('caching not working, plugin aborted', 'json-content-importer').":</b><br>";
            $this->errormsg .= __('plugin / wordpress / webserver can\'t create', 'json-content-importer')."<br><i>".$this->cacheBaseFolder."</i><br>";
            $this->errormsg .= __('therefore: set directory-permissions to 0777 (or other depending on the way you create directories with your webserver)', 'json-content-importer')."<hr>";
            # abort: no caching possible
            #exit;
            return $this->errormsg;
          }
        }

        if (!is_dir($this->cacheFolder)) {
          # $this->cacheFolder is no dir: not existing
          # try to create $this->cacheFolder
          $mkdirError = mkdir($this->cacheFolder) ?? FALSE;
          if (!$mkdirError) {
            # mkdir failed, usually due to missing write-permissions
            $this->errormsg .= "<hr><b>".__('caching not working, plugin aborted', 'json-content-importer').":</b><br>";
            $this->errormsg .= __('plugin / wordpress / webserver can\'t create', 'json-content-importer')."<br><i>".$this->cacheFolder."</i><br>";
            $this->errormsg .= __('therefore: set directory-permissions to 0777 (or other depending on the way you create directories with your webserver)', 'json-content-importer')."<hr>";
            # abort: no caching possible
            #exit;
            return $this->errormsg;
          }
        }
        # $this->cacheFolder writeable?
        if (!is_writeable($this->cacheFolder)) {
          $this->errormsg .= __('please check cacheFolder', 'json-content-importer').":<br>".$this->cacheFolder."<br>".__('is not writable. Please change permissions.', 'json-content-importer')."";
          #exit;
          return $this->errormsg;
        }
	}
}



class JSONdecode {
  /* internal */
  private $jsondata = "";
  private $feedData = "";
  private $isAllOk = TRUE;
  private $errormsg = "";

  public function __construct($feedData){
    $this->feedData = $feedData;
    $this->jsondata = "";
    #$this->decodeFeedData();
    $this->isAllOk = $this->decodeFeedData();
	}

  /* decodeFeedData: convert raw-json-data into array */
	public function decodeFeedData() {
	 if(empty($this->feedData)) {
       return FALSE;
   } else {
	    $this->jsondata =  json_decode($this->feedData);
      if (is_null($this->jsondata)) {
      # utf8_encode JSON-datastring, then try json_decode again
    	#$this->jsondata =  json_decode(utf8_encode($this->feedData)); #utf8_encode deprecated from PHP 8.2 on
    	$this->jsondata =  json_decode(iconv('iso-8859-1', 'utf-8', $this->feedData));
      if (is_null($this->jsondata)) {
        #echo "JSON-Decoding failed. Check structure and encoding if JSON-data.";
         return FALSE;
      }
      return TRUE;
     }
     return TRUE;
	 }
   return FALSE;
  }
  /* get */
	public function getJsondata() {
    return $this->jsondata;
  }
  /* get */
	public function getIsAllOk() {
    return $this->isAllOk;
  }
}

class FileLoadWithCache {
  /* internal */
  private $feedData = "";
  private $urlgettimeout = 5; # 5 seconds default timeout for get of JSON-URL
  private $cacheEnable = "";
  private $cacheFile = "";
  private $debugmode = 0;
  private $feedUrl = "";
  private $cacheExpireTime = 0;
  private $cacheWritesuccess = FALSE;
  private $oauth_bearer_access_key = "";
  private $http_header_default_useragent_flag = 0;
  private $allok = TRUE;
  private $errormsg = "";
  private $debugmessage = "";
  private $fallback2cache = 0;
  private $removewrappingsquarebrackets = FALSE;
  private $respHttpCode = "";
  private $respHttpServerError = "";

  public function __construct($feedUrl, $urlgettimeout, $cacheEnable, $cacheFile, $cacheExpireTime, $oauth_bearer_access_key, $http_header_default_useragent_flag, $debugmode, $fallback2cache=0, $removewrappingsquarebrackets=FALSE){
    $this->debugmode = $debugmode;
    $this->cacheEnable = $cacheEnable;
    if (is_numeric($urlgettimeout) && $urlgettimeout>=0) {
      $this->urlgettimeout = $urlgettimeout;
    }
    $this->cacheFile = $cacheFile;
    $this->feedUrl = $feedUrl;
    $this->cacheExpireTime = $cacheExpireTime;
    $this->oauth_bearer_access_key = $oauth_bearer_access_key;
    $this->http_header_default_useragent_flag = $http_header_default_useragent_flag;
    $this->fallback2cache = $fallback2cache;
    $this->removewrappingsquarebrackets = $removewrappingsquarebrackets;
	}

  /* get */
	public function getFeeddata() {
    return $this->feedData;
  }

  /* get errorlevel */
	public function getAllok() {
    return $this->allok;
  }

	public function getdebugmessage(){
      return $this->debugmessage;
    }
    
	private function showdebugmessage($message){
		if ($this->debugmode!=10) {
			return "";
		}
		$this->debugmessage .=  __('DEBUG', 'json-content-importer').": $message<br>";
		#echo "DEBUG: $message<br>";
    }

    /* retrieveJsonData: get json-data and build json-array */
	public function retrieveJsonData(){
		# check cache: is there a not expired file?
		if ($this->cacheEnable) {
			# use cache
			if ($this->isCacheFileExpired()) {
				# get json-data from cache
				#$this->retrieveFeedFromCache();
				$this->retrieveFeedFromCache();
			} else {
				$this->retrieveFeedFromWeb();
			}
		} else {
			# no use of cache OR cachefile expired: retrieve json-url
			$this->retrieveFeedFromWeb();
		}

  		if(empty($this->feedData)) {
			$this->errormsg .= __("error: get of json-data failed - plugin aborted: check url of json-feed", 'json-content-importer');
			$this->showdebugmessage(__("no data received: check url of json-feed", 'json-content-importer'));
			$this->allok = FALSE;
			return "";
		} else {
			if ($this->removewrappingsquarebrackets) {
				$this->feedData = preg_replace("/^\[/", "", trim($this->feedData));
				$this->feedData = preg_replace("/\]$/", "", trim($this->feedData));
			}
		}
	}

    /* isCacheFileExpired: check if cache enabled, if so: */
	public function isCacheFileExpired(){
		# get age of cachefile, if there is one...
		if (file_exists($this->cacheFile)) {
			$ageOfCachefile = filemtime($this->cacheFile);  # time of last change of cached file
		} else {
			# there is no cache file yet
			return FALSE;
		}

		# if $ageOfCachefile is < $cacheExpireTime use the cachefile:  isCacheFileExpired = FALSE
		if ($ageOfCachefile < $this->cacheExpireTime) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

    /* storeFeedInCache: store retrieved data in cache */
	private function storeFeedInCache(){
		if (!$this->cacheEnable) {
			# no use of cache if cache is not enabled or not working
			return NULL;
		}
	  
		$tmpJSON = json_decode($this->feedData, TRUE);
		if (is_null($tmpJSON)) {
			# utf8_encode JSON-datastring, then try json_decode again
			#$tmpJSON2 =  json_decode(utf8_encode($this->feedData)); #utf8_encode deprecated from PHP 8.2 on
			$tmpJSON2 =  json_decode(iconv('iso-8859-1', 'utf-8', $this->feedData));
			if (is_null($tmpJSON2)) {
				# invalid data: do not cache!
				#$this->debugmessage .= __("Feed is not valid JSON - not cached", 'json-content-importer')."<br>";
				#$this->showdebugmessage( __("Feed is not valid JSON - not cached", 'json-content-importer'))."<br>";
				return "";
			}
		}
	  
		$handle = fopen($this->cacheFile, 'w');
		if(isset($handle) && !empty($handle)){
			$this->cacheWritesuccess = fwrite($handle, $this->feedData); # false if failed
			fclose($handle);
			if (!$this->cacheWritesuccess) {
				$this->debugmessage .= "cache-error:<br>".$this->cacheFile."<br>".__("can't be stored - plugin aborted", 'json-content-importer');
				$this->showdebugmessage("cache-error:<br>".__("JSON-data can't be stored to", 'json-content-importer').$this->cacheFile);
				$this->allok = FALSE;
				return "";
			} else {
				$this->showdebugmessage(__("saving JSON-data to cache successful", 'json-content-importer'));
				return $this->cacheWritesuccess; # no of written bytes
			}
		} else {
			$this->debugmessage .= "cache-error:<br>".$this->cacheFile."<br>".__('is either empty or unwriteable - plugin aborted', 'json-content-importer');
			$this->showdebugmessage("cache-error:<br>".$this->cacheFile."<br>".__('is either empty or unwriteable - plugin aborted', 'json-content-importer'));
			$this->allok = FALSE;
			return "";
		}
	}

	public function retrieveFeedFromWeb() {
      # wordpress unicodes http://openstates.org/api/v1/bills/?state=dc&q=taxi&apikey=4680b1234b1b4c04a77cdff59c91cfe7;
      # to  http://openstates.org/api/v1/bills/?state=dc&#038;q=taxi&#038;apikey=4680b1234b1b4c04a77cdff59c91cfe7
      # and the param-values are corrupted
      # un_unicode ampersand:
      $this->feedUrl = preg_replace("/&#038;/", "&", $this->feedUrl);
      $this->feedUrl = str_replace('&amp;', '&', $this->feedUrl);
      #$useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0"; # in case of simulating a browser
      if (empty($this->oauth_bearer_access_key)) {
        $args = array(
          'timeout'     => $this->urlgettimeout,
          #'httpversion' => '1.0',
          # 'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
          #'blocking'    => true,
          #'headers'     => array(),
          #'cookies'     => array(),
          #'body'        => null,
          #'compress'    => false,
          #'decompress'  => true,
          #'sslverify'   => true,
          #'stream'      => false,
          #'filename'    => null
        );
      } else {
        $args = array(
            'timeout'     => $this->urlgettimeout,
   			'sslverify' => false
        );

        $acckey = $this->oauth_bearer_access_key;
        if (preg_match("/^nobearer /i", $acckey)) {
			$acckey = preg_replace("/^nobearer /i", "", $acckey);
			$header = $acckey;
			$this->showdebugmessage("Authentication header: ".$header);
			$args["headers"] = array('Authorization' => $header);
        } elseif (preg_match("/^header /i", $acckey)) {
			# header HEADER_KEY:HEADER_VALUE
			$acckey = preg_replace("/^header /i", "", $acckey);
			$acckeyArr = explode("#", $acckey);
			$headerArr = array();
			foreach ($acckeyArr as $acckeyItem) {
				$acckeyArrSub = explode(":", $acckeyItem, 2);
				if (!empty($acckeyArrSub[0]) && !empty($acckeyArrSub[1])) {
					$headerArr[$acckeyArrSub[0]] = $acckeyArrSub[1];
				}
			}
			$args["headers"] = $headerArr;
        } else {
			$header = __('Bearer', 'json-content-importer').' '.$acckey;
			$args["headers"] = array('Authorization' => $header);
        }
      }
      if ($this->http_header_default_useragent_flag==1) {
        $args['user-agent'] = __('JCI WordPress-Plugin - free Version', 'json-content-importer');
      }
		if (1==get_option("jci_sslverify_off")) {
			$args["sslverify"] = FALSE;
			$this->showdebugmessage("Switch OFF SSL verification (send sslverify=false)");
		};
		if (2==get_option("jci_sslverify_off")) {
			$args["sslverify"] = TRUE;
			$this->showdebugmessage("Switch ON SSL verification (send sslverify=true)");
		};
		#echo print_r($args, TRUE);

      $this->showdebugmessage("wp_remote_get to ".$this->feedUrl);
      $this->showdebugmessage(__('arguments', 'json-content-importer').": ".stripslashes(htmlentities(print_r($args, TRUE))));

      $response = wp_remote_get($this->feedUrl, $args);
	  $this->respHttpCode = wp_remote_retrieve_response_code($response); # gives http-errorcode BUT nothing if request fails completely, e .g. no serveranswer
      if ( is_wp_error( $response ) ) {
		# server does not answer at all
		$this->respHttpServerError = $response->get_error_message();
        $error_message = $response->get_error_message();
        $this->errormsg .= __('Something went wrong fetching URL with JSON-data', 'json-content-importer').": $error_message";
        $this->showdebugmessage(__('error getting URL:', 'json-content-importer'). $error_message);
        $this->allok = FALSE;
      } else if(isset($response['body']) && !empty($response['body'])){
		$this->feedData = $response['body'];
        $this->storeFeedInCache();
        $this->showdebugmessage(__('success getting URL', 'json-content-importer'));
	  }
		if ($this->fallback2cache>0) {
			$this->func_fallback2cache($this->respHttpCode);
    	}
	}


	public function get_respHttpCode(){
		return $this->respHttpCode;
	}
	public function get_respHttpServerError(){
		return $this->respHttpServerError;
	}
	
	public function func_fallback2cache(){
		$this->showdebugmessage(__('use fallback2cache', 'json-content-importer').": ".$this->fallback2cache);
		# use cached JSON, if needed and available:
		# 1: use cache if http-response is not 200
		# 2: use cache if response is not JSON
		# 3: use cache if http-response is not 200 AND not JSON
		$useCachedJson = FALSE;
		if ("200"!=$this->respHttpCode && ("1"==$this->fallback2cache || "3"==$this->fallback2cache)) {
			$useCachedJson = TRUE;
		}
		$jsondecodetmp = json_decode($this->feedData, TRUE);
		if (NULL==$jsondecodetmp && ("2"==$this->fallback2cache || "3"==$this->fallback2cache)) {
			$useCachedJson = TRUE;
		}
		if ($useCachedJson) {
			if(file_exists($this->cacheFile)) {
				# use previous generated and as cache stored JSON
				$this->feedData = file_get_contents($this->cacheFile);
				$this->showdebugmessage(__('fallback2cache ok: cached JSON found', 'json-content-importer'));
				$this->respHttpServerError = "";
				$this->respHttpCode = "200";
				$this->allok = TRUE;
			} else {
				$this->showdebugmessage(__('fallback2cache failed: no cached JSON found', 'json-content-importer'));
			}
		}
	}


    /* retrieveFeedFromCache: get cached filedata  */
	public function retrieveFeedFromCache(){
		if(file_exists($this->cacheFile)) {
			$this->feedData = file_get_contents($this->cacheFile);
			$this->respHttpServerError = "";
			$this->respHttpCode = "200";
			$this->showdebugmessage("Retrieved from the local cache: ".$this->feedUrl);
		} else {
			# get from cache failed, try from web
			$this->retrieveFeedFromWeb();
			$this->showdebugmessage("Failed to retrieved from the local cache: ".$this->feedUrl);
		}
	}
}
?>