<?php
/**
 * data load initialize
 *
 * @param mixed $filename please look at the load() method
 *
 * @access public
 * @see load()
 * @return void
 */
class JSON_data{
	protected
	
	/**
	 * imported posts data from json
	*
	* @var array
	* @access protected
	*/
	$posts = array(),
	
	/**
	 * json file to parse
	*
	* @var string
	* @access protected
	*/
	$_filename = '';
	
	
	
	public function __construct($filename = null)
	{
		$this->load($filename);
	}
	
	/**
	* @param string $filename the json filename to load
	*
	* @access public
	* @return boolean true if file was loaded successfully
	* @see isSymmetric(), getAsymmetricRows(), symmetrize()
	*/
	public function load($filename)
	{
		$this->_filename = $filename;
		$this->flush();
		return $this->parse();
	}
	
	/**
	 * json file validator
	 *
	 * checks wheather if the given csv file is valid or not
	 *
	 * @access protected
	 * @return boolean
	 */
	protected function validates()
	{
		// file existance
		if (!file_exists($this->_filename)) {
			return false;
		}
	
		// file readability
		if (!is_readable($this->_filename)) {
			return false;
		}
	
		return true;
	}
	/**
	 * object data flusher
	 *
	 * tells this object to forget all data loaded and start from
	 * scratch
	 *
	 * @access protected
	 * @return void
	 */
	protected function flush()
	{
		$this->posts    = array();
	}
	
	/**
	 * json parser
	 *
	 * reads json data and transforms it into php-data
	 *
	 * @access protected
	 * @return boolean
	 */
	protected function parse()
	{
		if (!$this->validates()) {
			return false;
		}
	
		$requests = file_get_contents($this->_filename);
		
		$fb_response = json_decode($requests);
		
		// move pointer to end
		end($fb_response);
		
		// get the key
		$last_key = key($fb_response);
		
		
		
		foreach ($fb_response as $key => $response) {
			// make sure last key which is paging not reached
			if($last_key!= $key ){
				foreach ($fb_response->$key as $data) {
					$row_data=array();
					foreach ($data as $item) {
						$row_data['post_title'] = isset($data->name)?$data->name:$data->status_type;
						$row_data['post_content'] =$data->message;
						$row_data['post_date']    = $data->created_time;
						$row_data['post_excerpt'] = isset($data->description)?$data->description:'';
						$row_data['post_name']    = isset($data->name)?$data->name:$data->status_type;
						$row_data['post_author']  = $data->from->name;
						$row_data['tax_input']    = array('type'=>$data->type,'status_type'=>$data->status_type);
						$row_data['type'] = $data->type;
						$row_data['status_type'] = $data->status_type;
						$row_data['caption'] = isset($data->caption)?$data->caption:'';
						$row_data['link'] = $data->link;
						$row_data['post_categories']  = $data->from->category;					
						$row_data['likes'] = count($data->likes->data);
						$row_data['comments'] = $data->comments->data;
						$row_data['picture'] = $data->picture;
						$row_data['type'] = $data->type;
						$row_data['status_type'] = $data->status_type;
						$row_data['shares'] = $data->shares->count;
					}
					array_push($this->posts, $row_data);
				}
			}
		}
		
		return true;
	}
	/** This function will Count posts and return numbers
	* @access public
	* @return integer
	*/
	public function countPosts()
	{
		return count($this->posts);
	}
	
	
	/**
	* @param array $range a list of posts to retrive
	*
	* @access public
	* @return array
	*/
	public function getPosts($range = array())
	{
		if (is_array($range) && ($range === array())) {
			return $this->posts;
		}
	
		if (!is_array($range)) {
			return $this->posts;
		}
	
		$ret_arr = array();
		foreach ($this->posts as $key => $row) {
			if (in_array($key, $range)) {
				$ret_arr[] = $row;
			}
		}
		return $ret_arr;
	}
	
	/**
	* @param integer $number the post number to fetch
	*
	* @access public
	* @return array the row identified by number, if $number does
	* not exist an empty array is returned instead
	*/
	public function getPost($number)
	{
		$raw = $this->posts;
		if (array_key_exists($number, $raw)) {
			return $raw[$number];
		}
		return array();
	}
	
}