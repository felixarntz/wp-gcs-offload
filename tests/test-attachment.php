<?php

class WPGCSO_Test_Attachment extends WPGCSO_UnitTestCase {
	public function test_get_attachment() {
		$img_name = 'my-image.jpg';

		$attachment_id = $this->create_attachment( $img_name, 'image/jpeg' );

		$attachment = WPGCSOffload\Core\Attachment::get( $attachment_id );
		$this->assertInstanceOf( 'WPGCSOffload\Core\Attachment', $attachment );
		$this->assertEquals( $attachment_id, $attachment->get_id() );
	}

	public function test_is_local_file() {
		$img_name = 'my-image.jpg';

		$attachment = $this->create_and_get_attachment( $img_name, 'image/jpeg' );
		$this->assertTrue( $attachment->is_local_file() );

		$this->delete_attachment_local_file( $attachment->get_id() );
		$this->assertFalse( $attachment->is_local_file() );
	}

	public function test_is_cloud_storage_file() {
		$img_name = 'my-image.jpg';
		$bucket_name = 'my-bucket';
		$dir_name = 'www.example.com';

		$attachment = $this->create_and_get_attachment( $img_name, 'image/jpeg' );
		$this->assertFalse( $attachment->is_cloud_storage_file() );

		$this->upload_attachment_to_gcs( $attachment->get_id(), $bucket_name, $dir_name );
		$this->assertTrue( $attachment->is_cloud_storage_file() );
	}

	public function test_get_cloud_storage_url() {
		$img_name = 'my-image.jpg';
		$bucket_name = 'my-bucket';
		$dir_name = 'www.example.com';

		$yearmonth_dir = $this->get_yearmonth_dir();

		$expected = 'https://storage.googleapis.com/' . $bucket_name . '/' . $dir_name . '/' . $yearmonth_dir . '/' . $img_name;

		$attachment = $this->create_and_get_attachment( $img_name, 'image/jpeg' );
		$this->upload_attachment_to_gcs( $attachment->get_id(), $bucket_name, $dir_name );
		$this->assertEquals( $expected, $attachment->get_cloud_storage_url() );
	}

	public function test_get_cloud_storage_image_downsize() {
		$img_name = 'my-image.jpg';
		$bucket_name = 'my-bucket';
		$dir_name = 'www.example.com';

		$yearmonth_dir = $this->get_yearmonth_dir();

		$expected = 'https://storage.googleapis.com/' . $bucket_name . '/' . $dir_name . '/' . $yearmonth_dir . '/' . $img_name;

		$attachment = $this->create_and_get_attachment( $img_name, 'image/jpeg' );
		$this->upload_attachment_to_gcs( $attachment->get_id(), $bucket_name, $dir_name );

		$result = $attachment->get_cloud_storage_image_downsize( 'full' );
		$this->assertNotFalse( $result );
		$this->assertEquals( $expected, $result[0] );
	}
}
