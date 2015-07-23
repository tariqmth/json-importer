<?php
/*
 * Plugin Name: JSON Importer 
 * Description: Import data as posts from a JSON data file. <em>feel free to contact me at <a href="mailto:mth3@hotmail.com">mth3@hotmail.com</a></em>. 
 * Version: 1 
 * Author: Tariq Hafeez
 * Author URI:    https://au.linkedin.com/pub/tariq-hafeez/8/49/b2a
 */
/**
 * LICENSE: newscorp
 *
 * @author Tariq Hafeez <mth3@hotmail.com>
 * @copyright 2015 Tariq Hafeez
 * @license I will put license info later, right now its for newscorp
 *         
 */
class JSONImporterPlugin
{
    var $defaults = array(
		'post_title' => null, 
		'post_post' => null, 
		'post_type' => null, 
		'post_excerpt' => null, 
		'post_date' => null, 
		'post_tags' => null, 
		'post_categories' => null, 
		'post_author' => null, 
		'post_slug' => null, 
		'post_parent' => 0);

    var $log = array();
    
    /**
     * Determine value of option $name from database, $default value or $params,
     * save it to the db if needed and return it.
     *
     * @param string $name        	
     * @param mixed $default        	
     * @param array $params        	
     * @return string
     */
    function process_option($name, $default, $params)
    {
        if (array_key_exists($name, $params)) {
            $value = stripslashes($params[$name]);
        } elseif (array_key_exists('_' . $name, $params)) {
            // unchecked checkbox value
            $value = stripslashes($params['_' . $name]);
        } else {
            $value = null;
        }
        $stored_value = get_option($name);
        if ($value == null) {
            if ($stored_value === false) {
                if (is_callable($default) && method_exists($default[0], $default[1])) {
                    $value = call_user_func($default);
                } else {
                    $value = $default;
                }
                add_option($name, $value);
            } else {
                $value = $stored_value;
            }
        } else {
            if ($stored_value === false) {
                add_option($name, $value);
            } elseif ($stored_value != $value) {
                update_option($name, $value);
            }
        }
        return $value;
    }
    
    /**
     * Plugin's interface
     * Wordpress admin screen, Accessed through Tools menu
     * 
     * @return void
     */
    function form()
    {
        $opt_draft = $this->process_option('json_importer_import_as_draft', 'publish', $_POST);
        $opt_cat   = $this->process_option('json_importer_cat', 0, $_POST);
        
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->post(compact('opt_draft', 'opt_cat'));
        }
        
        // form HTML {{{
?>

<div class="wrap">
	<h2>Import JSON data</h2>
	<form class="add:the-list: validate" method="post"
		enctype="multipart/form-data">
		<!-- Import as draft -->
		<p>
			<input name="_json_importer_import_as_draft" type="hidden"
				value="publish" /> <label><input
				name="json_importer_import_as_draft" type="checkbox"
				<?php
        if ('draft' == $opt_draft) {
            echo 'checked="checked"';
        }
?>
				value="draft" /> Import posts as drafts</label>
		</p>

		<!-- Parent category -->
		<p>Organize into category <?php
        wp_dropdown_categories(array(
            'show_option_all' => 'Select one ...',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'show_count' => 0,
            'name' => 'json_importer_cat',
            'orderby' => 'name',
            'selected' => $opt_cat
        ));
?><br />
			<small>This will create new categories inside the category parent you
				choose.</small>
		</p>

		<!-- File input -->
		<p>
			<label for="json_import">Upload file:</label><br /> <input
				name="json_import" id="json_import" type="file" value=""
				aria-required="true" />
		</p>
		<p class="submit">
			<input type="submit" class="button" name="submit" value="Import" />
		</p>
	</form>
</div>
<!-- end wrap -->

<?php
        // end form HTML }}}
    }
    function print_messages()
    {
        if (!empty($this->log)) {
            
            // messages HTML {{{
?>

<div class="wrap">
    <?php
            if (!empty($this->log['error'])):
?>

    <div class="error">

        <?php
                foreach ($this->log['error'] as $error):
?>
            <p><?php
                    echo $error;
?></p>
        <?php
                endforeach;
?>

    </div>

    <?php
            endif;
?>

    <?php
            if (!empty($this->log['notice'])):
?>

    <div class="updated fade">

        <?php
                foreach ($this->log['notice'] as $notice):
?>
            <p><?php
                    echo $notice;
?></p>
        <?php
                endforeach;
?>

    </div>

    <?php
            endif;
?>
</div>
<!-- end wrap -->

<?php
            // end messages HTML }}}
            
            $this->log = array();
        }
    }
    
    /**
     * Handle POST submission
     *
     * @param array $options        	
     * @return void
     */
    function post($options)
    {
        if (empty($_FILES['json_import']['tmp_name'])) {
            $this->log['error'][] = 'No file uploaded, aborting.';
            $this->print_messages();
            return;
        }
        
        if (!current_user_can('publish_pages') || !current_user_can('publish_posts')) {
            $this->log['error'][] = 'You don\'t have the permissions to publish posts and pages. Please contact the blog\'s administrator.';
            $this->print_messages();
            return;
        }
        
        require_once 'DataSource/JSON_data.php';
        
        $time_start = microtime(true);
        $json       = new JSON_data();
        $file       = $_FILES['json_import']['tmp_name'];
        
        if (!$json->load($file)) {
            $this->log['error'][] = 'Failed to load file, aborting.';
            $this->print_messages();
            return;
        }
        
        // WordPress sets the correct timezone for date functions somewhere
        // in the bowels of wp_insert_post(). We need strtotime() to return
        // correct time before the call to wp_insert_post().
        $tz = get_option('timezone_string');
        if ($tz && function_exists('date_default_timezone_set')) {
            date_default_timezone_set($tz);
        }
        
        $skipped  = 0;
        $imported = 0;
        $comments = 0;
        foreach ($json->getPosts() as $json_data) {
            if ($post_id = $this->create_post($json_data, $options)) {
                $imported++;
                $comments += $this->add_comments($post_id, $json_data['comments']);
                $custom_fields = array(
                    'caption' => $json_data['caption'],
                    'link' => $json_data['link'],
                    'type' => $json_data['type'],
                    'status_type' => $json_data['status_type'],
                    'shares' => $json_data['shares'],
                    'likes' => $json_data['likes']
                );
                $this->create_custom_fields($post_id, $custom_fields);
            } else {
                $skipped++;
            }
        }
        
        if (file_exists($file)) {
            @unlink($file);
        }
        
        $exec_time = microtime(true) - $time_start;
        
        if ($skipped) {
            $this->log['notice'][] = "<b>Skipped {$skipped} posts (most likely due to empty title, body and excerpt).</b>";
        }
        $this->log['notice'][] = sprintf("<b>Imported {$imported} posts and {$comments} comments in %.2f seconds.</b>", $exec_time);
        $this->print_messages();
    }
    
    /**
     * Create post data and insert into Wordpress Database
     * @param array $data
     *        	The array of extracted data from JSON to create new wp post
     * @param array $options
     *        	The options been requested on plugin interface
     *        	i.e. import post as draft or publish, default parent category to import post in
     * @return integer $id The post ID
     */
    function create_post($data, $options)
    {
        $opt_draft  = isset($options['opt_draft']) ? $options['opt_draft'] : null;
        $opt_cat    = isset($options['opt_cat']) ? $options['opt_cat'] : null;
        $data       = array_merge($this->defaults, $data);
        // for now its always be POST,as we are not accepting post type form JSON
        $type       = $data['post_type'] ? $data['post_type'] : 'post';
        $valid_type = (function_exists('post_type_exists') && post_type_exists($type)) || in_array($type, array(
            'post',
            'page'
        ));
        
        if (!$valid_type) {
            $this->log['error']["type-{$type}"] = sprintf('Unknown post type "%s".', $type);
        }
        
        // validate minimum required data to create new post
        $required_fields = array(
            'post_title',
            'post_content',
            'post_date'
        );
        $valid_data      = true;
        foreach ($required_fields as $field) {
            if (!in_array($field, $data)) {
                $this->log['error']["type-{$type}"] = sprintf('Missing required field"%s".', $field);
                $valid_data                         = false;
            }
        }
        
        /**
         * IMPORTANT: senetaizing  cleaning and assigning data for wp post
         */
        $new_post = array(
            'post_title' => convert_chars($data['post_title']),
            'post_content' => wpautop(convert_chars($data['post_content'])),
            'post_status' => $opt_draft,
            'post_type' => $type,
            'post_date' => $this->parse_date($data['post_date']),
            'post_excerpt' => convert_chars($data['post_excerpt']),
            'post_name' => $data['post_slug'],
            'post_author' => $this->get_auth_id($data['post_author']),
            'tax_input' => '', // ignoring right now, as dont have taxnomy in fb feed
            'post_parent' => $opt_cat
        );
        
        $id = false; // default false incase any exception occured in insertion
        
        // if all valid
        if ($valid_type && $valid_data) {
            // Setup tags if available
            if (isset($data['post_tags']))
                $new_post['tags_input'] = $data['post_tags'];
            
            // Setup categories before inserting
            if (isset($data['post_categories'])) {
                $cats                      = $this->create_or_get_categories($data['post_categories'], $opt_cat);
                $new_post['post_category'] = $cats;
            }
            
            
            // create!
            $id = wp_insert_post($new_post);
            
            // Add image to the post, might not work because of the fb security
            if ($id && !empty($data['post_categories']))
                media_sideload_image($data['post_categories'], $if, $new_post['caption']);
        }
        return $id;
    }
    
    /**
     * Return an array of category ids for a post.
     *
     * @param string $data array
     *        	json_post_categories cell contents
     * @param integer $common_parent_id
     *        	common parent id for all categories
     * @return array category ids
     */
    function create_or_get_categories($data, $common_parent_id)
    {
        $ids        = array();
        $categories = array_map('trim', explode(',', $data));
        foreach ($categories as $category) {
            if ($category) {
                $term = $this->term_exists($category, 'category', $parent_id);
                if ($term) {
                    $term_id = $term['term_id'];
                } else {
                    $term_id = wp_insert_category(array(
                        'cat_name' => $category,
                        'category_parent' => $common_parent_id
                    ));
                }
            }
            $ids[] = $term_id;
        }
        return $ids;
    }
    
    
    /**
     * Try to split lines of text correctly regardless of the platform the text
     * is coming from.
     * @param string The text need to be cleaned
     * @return string Cleaned text
     */
    function split_lines($text)
    {
        $lines = preg_split("/(\r\n|\n|\r)/", $text);
        return $lines;
    }
    
    /**
     * Process JSON comments and add to wp post 
     * @param	integer $post_id The Post where comments need to be added
     * @param	array	Comments data
     * @return 	integer Number of comments been added	
     */
    function add_comments($post_id, $data)
    {
        
        /* example of json fb comments will remove it
         *  {
         "id": "1067053453322027_1067053696655336",
         "from": {
         "id": "10152273534113020",
         "name": "Cheryl Springfield"
         },
         "message": "Oh people need to drink a cup of cement and harden up.",
         "can_remove": false,
         "created_time": "2015-05-08T11:01:53+0000",
         "like_count": 21,
         "user_likes": false
         },
         */
        
        // Now go through each comment and insert it. More fields are possible
        // in principle as per documentation of wp_insert_comment
        $count = 0;
        
        foreach ($data as $comment) {
        	//print_r($comment);echo '<br>new comment<br><br>';
        	 
            $new_comment = array(
                'comment_post_ID' => $post_id,
                'comment_approved' => 1,
                'comment_author_url' => 'https://www.facebook.com/' . $comment->id, // this will take you to the FB comment page
                'comment_author' => $comment->from->name,
                'comment_content' => convert_chars($comment->message),
                'comment_date' => $this->parse_date($comment->created_time) 
            );
            
            $id = wp_insert_comment($new_comment);
            if ($id) {
                $count++;
            } else {
                $this->log['error'][] = "Could not add comment from " . $comment->name;
            }
        }
   
        return $count;
    }
    /**
     * Add custom fields into wordpress post
     * @param integer $post_id The wordpress post id
     * @param array	 $data Associate array contain field name and value
     */
    function create_custom_fields($post_id, $data)
    {
        foreach ($data as $k => $v) {
            add_post_meta($post_id, $k, $v);
        }
    }
    function get_auth_id($author)
    {
        if (is_numeric($author)) {
            return $author;
        }
        
        // get_userdatabylogin is deprecated as of 3.3.0
        if (function_exists('get_user_by')) {
            $author_data = get_user_by('login', $author);
        } else {
            $author_data = get_userdatabylogin($author);
        }
        
        return ($author_data) ? $author_data->ID : 0;
    }
    
    /**
     * Convert date in JSON file to 1999-12-31 23:52:00 format
     *
     * @param string $data        	
     * @return string
     */
    function parse_date($data)
    {
        $timestamp = strtotime($data);
        if (false === $timestamp) {
            return '';
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
    }
    /**
     * Compatibility wrapper for WordPress term lookup.
     */
    function term_exists($term, $taxonomy = '', $parent = 0) {
    	if (function_exists('term_exists')) { // 3.0 or later
    		return term_exists($term, $taxonomy, $parent);
    	} else {
    		return is_term($term, $taxonomy, $parent);
    	}
    }
}
    /**
     * To show menu in wordpress Admin
     */
    function importer_admin_menu()
    {
        require_once ABSPATH . '/wp-admin/admin.php';
        $plugin = new JSONImporterPlugin();
        add_management_page('edit.php', 'JSON Importer', 'manage_options', __FILE__, array(
            $plugin,
            'form'
        ));
    }

add_action('admin_menu', 'importer_admin_menu');

?>
