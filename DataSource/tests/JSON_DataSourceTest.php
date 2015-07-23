<?php
// main json class to test
require_once str_replace("tests","\JSON_Data.php",dirname(__FILE__));
/**
 * Test class for JSON_DataSourceTest.
 * 
 */
class JSON_DataSourceTest extends PHPUnit_Framework_TestCase
{
    protected $json,$file;
    
    protected function setUp()
    {
        $this->json = new JSON_data ();
        $this->file = dirname(__FILE__).'\facebook-data-test.json';
    }

    protected function tearDown()
    {
        $this->json = null;
    }

    public function test_uses_must_load_valid_file()
    {
        // must return true when a file is valid
		$this->assertTrue($this->json->load($this->file), 'File not loaded');
    }


    public function test_post_count_is_correct()
    {
        $this->assertTrue($this->json->load($this->file));
        // we have 25 posts in total
        $expected_count = 25;
        $this->assertEquals($expected_count, $this->json->countPosts());
    }

    public function test_post_fetching_returns_correct_result()
    {
        $this->assertTrue($this->json->load($this->file));
        $expected = 'News/media website';
        $post=$this->json->getPost(8);
        // will match the category data from fb feed
        $this->assertEquals($expected,$post['post_categories'] );
    }

    public function test_post_must_be_empty_array_when_post_does_not_exist()
    {
        $this->assertTrue($this->json->load($this->file));
        // -1 is wrong integer will return empty
        $this->assertEquals(array(), $this->json->getPost(-1));
        // we have total 25 posts so, 100 will return empty
        $this->assertEquals(array(), $this->json->getPost(100));
    }

    

}

?>
