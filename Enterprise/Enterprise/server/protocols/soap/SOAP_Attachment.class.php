<?php
class SOAP_Attachment
{
    public $options = array();
    public $attributes = array();

    /**
     * Constructor.
     *
     * @param string $name      Name of the SOAP value <value_name>
     * @param string $type      The attachment's MIME type.
     * @param string $filename  The attachment's file name. Ignored if $file is provided.
     * @param string $file      The attachment data.
     */
    public function __construct( $name = '', $type = 'application/octet-stream', $filename, $file = null )
    {
        $this->name = $name;
        $filePath = $filename;
        if( !is_null($file) ) { 
          $filedata = $file; // do not read file data yet but support $file
        } else {
 	       $filedata = file_get_contents($filePath);
        }
        $filename = basename($filename);
        $cid = md5(uniqid(time()));
        $this->attributes['href'] = 'cid:' . $cid; 
        $this->options['attachment'] = array('body' => $filedata,
                                             'disposition' => $filename,
                                             'content_type' => $type,
                                             'encoding' => 'base64',
                                             'cid' => $cid,
                                             'filepath' => $filePath);
    }
}
