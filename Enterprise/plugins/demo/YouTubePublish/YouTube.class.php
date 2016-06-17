<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';
require_once BASEDIR.'/server/ZendFramework/library/Zend/Gdata/YouTube.php';

class YouTube
{	
	private $youtube = null;

	const UPLOAD_URL = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads';

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
    	require_once BASEDIR.'/server/ZendFramework/library/Zend/Gdata/ClientLogin.php';
		$authenticationURL= 'https://www.google.com/youtube/accounts/ClientLogin';
		$httpClient =   
		Zend_Gdata_ClientLogin::getHttpClient(
				$username = YOUTUBEPUBLISH_USERNAME,              
				$password = YOUTUBEPUBLISH_USERPWD,              
				$service = 'youtube',             
				$client = null,              
				$source = 'WoodWing Enterprise', // a short string identifying your application              
				$loginToken = null,              
				$loginCaptcha = null,              
				$authenticationURL);
		$applicationId = 'WoodWing Enterprise Publishing Service';
		$this->youtube = new Zend_Gdata_YouTube($httpClient, $applicationId, YOUTUBEPUBLISH_CLIENT_ID, YOUTUBEPUBLISH_DEV_KEY);
		$this->youtube->setMajorProtocolVersion(2);
    }

    /**
     * Upload a video to YouTube.
     *
     *
     * @param   string $path  Full path and file name of the video.
     * @param   string $title Video title.
     * @param   string $desc Video description.
     * @param   string|array $tags A space separated list of tags to add to the video.
     *          These will be added to those listed in getTags().
     * @return  string id of the new video
     */
    public function uploadVideo($path, $title = '', $desc = '', $tags = '', $format, $category)
    {   
        if($tags == '') {
        	$tags = 'none';
        }
		if( $desc == '' ){
			$desc = $title;
		}
		$newVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
		$newVideoEntry->setMajorProtocolVersion(2);

		$filesource = $this->youtube->newMediaFileSource($path);
		$filesource->setContentType($format);	
		$filesource->setSlug($title);
		$newVideoEntry->setMediaSource($filesource);

		// create a new Zend_Gdata_YouTube_MediaGroup object 
		$mediaGroup = $this->youtube->newMediaGroup(); 
		$mediaGroup->title = $this->youtube->newMediaTitle()->setText($title); 
		$mediaGroup->description = $this->youtube->newMediaDescription()->setText($desc);
		// the category must be a valid YouTube category 
		// optionally set some developer tags (see Searching by Developer Tags for more details) 
		$mediaGroup->category = array( $this->youtube->newMediaCategory()->setText($category)->setScheme('http://gdata.youtube.com/schemas/2007/categories.cat'));

		// set keywords
		if( count($tags) > 1 ) {
			$keywords = implode(",", $tags);
		}
		else {
			if($tags[0] == '') {
				$keywords = 'none';
			}
		}
		$mediaGroup->keywords = $this->youtube->newMediaKeywords()->setText($keywords); 
		$newVideoEntry->mediaGroup = $mediaGroup;

		try {
    		$newEntry = $this->youtube->insertEntry($newVideoEntry, self::UPLOAD_URL, 'Zend_Gdata_YouTube_VideoEntry');
    		$newEntry->setMajorProtocolVersion(2);
    		return $newEntry->getVideoId();
		} catch (Zend_Gdata_App_HttpException $httpException) {
		   	$msg = $httpException->getMessage();
            throw new BizException( '', 'Server', $msg, $msg );
		} catch (Zend_Gdata_App_Exception $e) {
    		$msg = 'Could not delete video: '. $e->getMessage();
            throw new BizException( '', 'Server', $msg, $msg );
		}
    }
    
    /**
     * Perform deletion based on YouTube video id
     *
     * @param 	string 	$id YouTube video id
     * @return  boolean	Throw exception if false else return true
     */
    public function deleteVideo( $id )
    {
	    $feed = $this->youtube->getUserUploads('default');
    	$videoEntryToDelete = null;

	    foreach($feed as $entry) {
    	    if ($entry->getVideoId() == $id) {
        	    $videoEntryToDelete = $entry;
            	break;
	        }
    	}
	    // check if videoEntryToUpdate was found
    	if (!$videoEntryToDelete instanceof Zend_Gdata_YouTube_VideoEntry) {
	        $msg = 'Could not find a video entry with id = ' . $id;
            throw new BizException( '', 'Server', $msg, $msg );
    	}

	    try {
    	    $httpResponse = $this->youtube->delete($videoEntryToDelete);
    	    return true;
    	} catch (Zend_Gdata_App_HttpException $httpException) {
    		$msg = $httpException->getMessage();
            throw new BizException( '', 'Server', $msg, $msg );
	    } catch (Zend_Gdata_App_Exception $e) {
	    	$msg = 'Could not delete video: '. $e->getMessage();
            throw new BizException( '', 'Server', $msg, $msg );
    	}
    }
    
    /**
     * Publish Video to YouTube
     *
     * @param string $path Video full path.
     * @param string $name Video name.
     * @param string $desc Video description
     * @param array	 $tags Video tags
     * @param string $format Video format
     * @param string $category  Video Category
     * @return string Video Id from YouTube
     */
    public function publishVideo( $path, $name, $desc, $tags, $format, $category)
    {
    	LogHandler::Log( 'YouTubePublish', 'DEBUG', 'Publish Video');
    	$videoid = '';
  		$videoid = $this->uploadVideo( $path, $name, $desc, $tags, $format, $category );
 		return $videoid;
    }

    /**
     * Update video to YouTube
     *
     * @param string $id YouTube Video Id
     * @param string $path Video path
     * @param string $title Video title
     * @param array	 $desc Video description
     * @param array	 $tags Video tags
     * @param string $format Video format
     * @param string $category  Video Category
     * @return string Video Id from YouTube
     */
    public function updateVideo( $id, $path, $title = '', $desc = '', $tags = '', $format, $category )
    {
    	LogHandler::Log( 'YouTubePublish', 'DEBUG', 'Update Video, ID:' . $id);
    	$videoId = '';
    	if( $this->deleteVideo($id) ) {
    		$videoId = $this->uploadVideo( $path, $title, $desc, $tags, $format, $category );
    	}
    	return $videoId;
    }

	/**
     * Unpublish video from YouTube
     *
     * @return string $id YouTube Video Id
     */
    public function unpublishVideo( $id )
    {	
    	LogHandler::Log( 'YouTubePublish', 'DEBUG', 'Unpublish Video, ID:' . $id);
  		return $this->deleteVideo( $id );
    }
}